<?php

namespace Tests\Unit;

use App\Services\LocalizadorEditais\LocalizadorEditaisBrowserAuthenticator;
use App\Services\LocalizadorEditais\LocalizadorEditaisSessionStore;
use App\Traits\LocalizadorEditaisTrait;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class LocalizadorEditaisTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Carbon::setTestNow('2026-07-20 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_reuses_persisted_session_across_paginated_requests(): void
    {
        config([
            'services.localizador_editais.base_url' => 'https://painel.example.test',
        ]);
        app(LocalizadorEditaisSessionStore::class)->save('wordpress_cookie=session', 'abc123');

        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode([
                'total' => 51,
                'page' => 1,
                'per_page' => 50,
                'rows' => [['id' => '1106656']],
            ])),
            new Response(200, [], json_encode([
                'total' => 51,
                'page' => 2,
                'per_page' => 50,
                'rows' => [['id' => '1106657']],
            ])),
        ]));
        $handler->push(Middleware::history($history));
        $client = new Client(['handler' => $handler]);

        $service = new class($client)
        {
            use LocalizadorEditaisTrait;

            public function __construct(private Client $fakeClient) {}

            protected function localizadorEditaisClient(CookieJar $cookies, ?string $sessionCookie = null): Client
            {
                return $this->fakeClient;
            }
        };

        $first = $service->searchDataLocalizadorEditais(1, 50);
        $second = $service->searchDataLocalizadorEditais(2, 50);

        $this->assertTrue($first['status']);
        $this->assertSame('1106656', $first['data'][0]['id']);
        $this->assertTrue($second['status']);
        $this->assertCount(2, $history);
        $this->assertSame('abc123', $history[0]['request']->getHeaderLine('X-WP-Nonce'));
        $this->assertSame('data_prazo_ini=2026-07-20&geo_mode=uf_city&iminencia_deserto=0&page=1&per_page=50&somente_favoritos=0', $this->sortedQuery($history[0]['request']->getUri()->getQuery()));
        $this->assertSame('data_prazo_ini=2026-07-20&geo_mode=uf_city&iminencia_deserto=0&page=2&per_page=50&somente_favoritos=0', $this->sortedQuery($history[1]['request']->getUri()->getQuery()));
    }

    public function test_reports_browser_authentication_failure_when_session_is_missing(): void
    {
        $authenticator = Mockery::mock(LocalizadorEditaisBrowserAuthenticator::class);
        $authenticator->shouldReceive('authenticate')
            ->once()
            ->andThrow(new RuntimeException('Falha no login pelo navegador'));
        $this->app->instance(LocalizadorEditaisBrowserAuthenticator::class, $authenticator);

        $service = new class
        {
            use LocalizadorEditaisTrait;
        };

        $result = $service->searchDataLocalizadorEditais();

        $this->assertFalse($result['status']);
        $this->assertStringContainsString('Falha no login pelo navegador', $result['error']);
    }

    public function test_uses_configured_wordpress_session_without_logging_in(): void
    {
        config([
            'services.localizador_editais.base_url' => 'https://painel.example.test',
        ]);

        app(LocalizadorEditaisSessionStore::class)->save(
            'wordpress_logged_in_test=session',
            'nonce123'
        );

        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode([
                'total' => 1,
                'page' => 1,
                'per_page' => 1,
                'rows' => [['id' => '1106656']],
            ])),
        ]));
        $handler->push(Middleware::history($history));
        $client = new Client(['handler' => $handler]);

        $service = new class($client)
        {
            use LocalizadorEditaisTrait;

            public function __construct(private Client $fakeClient) {}

            protected function localizadorEditaisClient(CookieJar $cookies, ?string $sessionCookie = null): Client
            {
                return $this->fakeClient;
            }
        };

        $result = $service->searchDataLocalizadorEditais(1, 1);

        $this->assertTrue($result['status']);
        $this->assertCount(1, $history);
        $this->assertSame('nonce123', $history[0]['request']->getHeaderLine('X-WP-Nonce'));
    }

    public function test_renews_nonce_and_keeps_persisted_session(): void
    {
        config(['services.localizador_editais.base_url' => 'https://painel.example.test']);
        app(LocalizadorEditaisSessionStore::class)->save('wordpress_cookie=session', 'expired');

        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(403, [], json_encode(['code' => 'rest_cookie_invalid_nonce'])),
            new Response(200, [], 'renewed123'),
            new Response(200, [], json_encode([
                'total' => 1,
                'page' => 1,
                'per_page' => 1,
                'rows' => [['id' => '1106656']],
            ])),
        ]));
        $handler->push(Middleware::history($history));
        $client = new Client(['handler' => $handler]);

        $service = new class($client)
        {
            use LocalizadorEditaisTrait;

            public function __construct(private Client $fakeClient) {}

            protected function localizadorEditaisClient(CookieJar $cookies, ?string $sessionCookie = null): Client
            {
                return $this->fakeClient;
            }
        };

        $result = $service->searchDataLocalizadorEditais(1, 1);

        $this->assertTrue($result['status']);
        $this->assertSame('action=rest-nonce', $history[1]['request']->getUri()->getQuery());
        $this->assertSame('renewed123', $history[2]['request']->getHeaderLine('X-WP-Nonce'));
        $this->assertSame('renewed123', app(LocalizadorEditaisSessionStore::class)->load()['nonce']);
    }

    public function test_logs_in_with_browser_when_persisted_cookie_expires(): void
    {
        config(['services.localizador_editais.base_url' => 'https://painel.example.test']);
        app(LocalizadorEditaisSessionStore::class)->save('expired_cookie=session', 'expired');

        $authenticator = Mockery::mock(LocalizadorEditaisBrowserAuthenticator::class);
        $authenticator->shouldReceive('authenticate')->once()->andReturn([
            'cookie' => 'renewed_cookie=session',
            'nonce' => 'browser123',
        ]);
        $this->app->instance(LocalizadorEditaisBrowserAuthenticator::class, $authenticator);

        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(403, [], json_encode(['code' => 'rest_cookie_invalid_nonce'])),
            new Response(400, [], '0'),
            new Response(200, [], json_encode([
                'total' => 1,
                'page' => 1,
                'per_page' => 1,
                'rows' => [['id' => '1106656']],
            ])),
        ]));
        $handler->push(Middleware::history($history));
        $client = new Client(['handler' => $handler]);

        $service = new class($client)
        {
            use LocalizadorEditaisTrait;

            public function __construct(private Client $fakeClient) {}

            protected function localizadorEditaisClient(CookieJar $cookies, ?string $sessionCookie = null): Client
            {
                return $this->fakeClient;
            }
        };

        $result = $service->searchDataLocalizadorEditais(1, 1);

        $this->assertTrue($result['status']);
        $this->assertSame('browser123', $history[2]['request']->getHeaderLine('X-WP-Nonce'));
        $this->assertSame('renewed_cookie=session', app(LocalizadorEditaisSessionStore::class)->load()['cookie']);
    }

    private function sortedQuery(string $query): string
    {
        parse_str($query, $values);
        ksort($values);

        return http_build_query($values);
    }
}
