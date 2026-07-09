<?php

namespace Tests\Unit;

use App\Traits\PncpTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class PncpTraitTest extends TestCase
{
    public function test_get_items_pncp_fetches_all_pages(): void
    {
        $history = [];
        $client = $this->fakePncpClient([
            new Response(200, [], json_encode($this->itemsRange(1, 100))),
            new Response(200, [], json_encode($this->itemsRange(101, 135))),
        ], $history);

        $service = new class ($client) {
            use PncpTrait;

            public function __construct(private Client $client)
            {
            }

            protected function pncpClient(): Client
            {
                return $this->client;
            }
        };

        $result = $service->getItemsPNCP('12345678000190', 2026, 44);

        $this->assertTrue($result['status']);
        $this->assertCount(135, $result['data']);
        $this->assertSame(1, $result['data'][0]['numeroItem']);
        $this->assertSame(135, $result['data'][134]['numeroItem']);
        $this->assertCount(2, $history);
        $this->assertSame('pagina=1&tamanhoPagina=100', $history[0]['request']->getUri()->getQuery());
        $this->assertSame('pagina=2&tamanhoPagina=100', $history[1]['request']->getUri()->getQuery());
    }

    public function test_get_items_pncp_keeps_fetching_when_page_is_full(): void
    {
        $history = [];
        $client = $this->fakePncpClient([
            new Response(200, [], json_encode($this->itemsRange(1, 100))),
            new Response(200, [], json_encode($this->itemsRange(101, 200))),
            new Response(200, [], json_encode([])),
        ], $history);

        $service = new class ($client) {
            use PncpTrait;

            public function __construct(private Client $client)
            {
            }

            protected function pncpClient(): Client
            {
                return $this->client;
            }
        };

        $result = $service->getItemsPNCP('12345678000190', 2026, 44);

        $this->assertTrue($result['status']);
        $this->assertCount(200, $result['data']);
        $this->assertCount(3, $history);
        $this->assertSame('pagina=3&tamanhoPagina=100', $history[2]['request']->getUri()->getQuery());
    }

    private function fakePncpClient(array $responses, array &$history): Client
    {
        $handlerStack = HandlerStack::create(new MockHandler($responses));
        $handlerStack->push(Middleware::history($history));

        return new Client(['handler' => $handlerStack]);
    }

    private function itemsRange(int $start, int $end): array
    {
        $items = [];

        for ($item = $start; $item <= $end; $item++) {
            $items[] = ['numeroItem' => $item];
        }

        return $items;
    }
}
