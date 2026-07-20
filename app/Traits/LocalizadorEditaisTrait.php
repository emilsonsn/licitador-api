<?php

namespace App\Traits;

use App\Services\LocalizadorEditais\LocalizadorEditaisBrowserAuthenticator;
use App\Services\LocalizadorEditais\LocalizadorEditaisSessionStore;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use RuntimeException;

trait LocalizadorEditaisTrait
{
    private ?Client $localizadorAuthenticatedClient = null;

    private ?string $localizadorNonce = null;

    protected function localizadorEditaisClient(CookieJar $cookies, ?string $sessionCookie = null): Client
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'User-Agent' => config('services.localizador_editais.user_agent', 'Licitador API'),
        ];

        if ($sessionCookie) {
            $headers['Cookie'] = $sessionCookie;
        }

        return new Client([
            'base_uri' => rtrim(config('services.localizador_editais.base_url'), '/').'/',
            'cookies' => $sessionCookie ? false : $cookies,
            'connect_timeout' => config('services.localizador_editais.connect_timeout', 15),
            'timeout' => config('services.localizador_editais.timeout', 45),
            'headers' => $headers,
        ]);
    }

    public function searchDataLocalizadorEditais(int $page = 1, int $perPage = 50): array
    {
        try {
            if (! $this->localizadorAuthenticatedClient || ! $this->localizadorNonce) {
                $this->restoreLocalizadorSession();
            }

            $response = $this->requestLocalizadorEditaisPage($page, $perPage);
            $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            if (! isset($body['rows']) || ! is_array($body['rows'])) {
                throw new RuntimeException('Resposta do Localizador de Editais sem a lista de licitações.');
            }

            return [
                'status' => true,
                'data' => $body['rows'],
                'total' => (int) ($body['total'] ?? count($body['rows'])),
                'page' => (int) ($body['page'] ?? $page),
                'per_page' => (int) ($body['per_page'] ?? $perPage),
            ];
        } catch (\Throwable $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    private function requestLocalizadorEditaisPage(int $page, int $perPage)
    {
        $options = [
            'headers' => [
                'Referer' => rtrim(config('services.localizador_editais.base_url'), '/').'/buscador/',
                'X-WP-Nonce' => $this->localizadorNonce,
            ],
            'query' => [
                'geo_mode' => 'uf_city',
                'iminencia_deserto' => 0,
                'somente_favoritos' => 0,
                'data_prazo_ini' => Carbon::today()->toDateString(),
                'page' => $page,
                'per_page' => min(max($perPage, 1), 50),
            ],
        ];

        try {
            return $this->localizadorAuthenticatedClient->get('wp-json/lc/v1/licitacoes', $options);
        } catch (RequestException $error) {
            if ($error->getResponse()?->getStatusCode() !== 403) {
                throw $error;
            }

            if (! $this->renewLocalizadorNonce()) {
                $this->authenticateLocalizadorWithBrowser();
            }

            $options['headers']['X-WP-Nonce'] = $this->localizadorNonce;

            return $this->localizadorAuthenticatedClient->get('wp-json/lc/v1/licitacoes', $options);
        }
    }

    private function restoreLocalizadorSession(): void
    {
        $store = app(LocalizadorEditaisSessionStore::class);
        $session = $store->load();

        if ($session) {
            $this->localizadorAuthenticatedClient = $this->localizadorEditaisClient(
                new CookieJar(),
                $session['cookie']
            );
            $this->localizadorNonce = $session['nonce'];

            return;
        }

        $this->authenticateLocalizadorWithBrowser();
    }

    private function renewLocalizadorNonce(): bool
    {
        try {
            $response = $this->localizadorAuthenticatedClient->get('wp-admin/admin-ajax.php', [
                'query' => ['action' => 'rest-nonce'],
            ]);
            $nonce = trim((string) $response->getBody());

            if (! preg_match('/^[a-zA-Z0-9]+$/', $nonce)) {
                return false;
            }

            $session = app(LocalizadorEditaisSessionStore::class)->load();
            if (! $session) {
                return false;
            }

            $this->localizadorNonce = $nonce;
            app(LocalizadorEditaisSessionStore::class)->save($session['cookie'], $nonce);

            return true;
        } catch (\Throwable) {
            app(LocalizadorEditaisSessionStore::class)->forget();

            return false;
        }
    }

    private function authenticateLocalizadorWithBrowser(): void
    {
        $session = app(LocalizadorEditaisBrowserAuthenticator::class)->authenticate();
        app(LocalizadorEditaisSessionStore::class)->save($session['cookie'], $session['nonce']);

        $this->localizadorAuthenticatedClient = $this->localizadorEditaisClient(
            new CookieJar(),
            $session['cookie']
        );
        $this->localizadorNonce = $session['nonce'];
    }
}
