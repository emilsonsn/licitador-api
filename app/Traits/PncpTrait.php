<?php

namespace App\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

trait PncpTrait
{
    private function pncpClient(): Client
    {
        $options = [
            'connect_timeout' => config('services.pncp.connect_timeout', 15),
            'timeout' => config('services.pncp.timeout', 45),
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => config('services.pncp.user_agent', 'Licitador API'),
            ],
            'curl' => $this->pncpCurlOptions(),
        ];

        if (config('services.pncp.proxy')) {
            $options['proxy'] = config('services.pncp.proxy');
        }

        return new Client($options);
    }

    private function pncpCurlOptions(): array
    {
        if (! config('services.pncp.force_ipv4', true)
            || ! defined('CURLOPT_IPRESOLVE')
            || ! defined('CURL_IPRESOLVE_V4')) {
            return [];
        }

        return [
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ];
    }

    public function searchDataPNCP($data)
    {

        $client = $this->pncpClient();
        $url = 'https://pncp.gov.br/api/consulta/v1/contratacoes/proposta';

        $query = [
            'dataFinal' => $data['dataFinal'],
            'codigoModalidadeContratacao' => $data['codigoModalidadeContratacao'] ?? null,
            'pagina' => $data['pagina'],
            'tamanhoPagina' => $data['tamanhoPagina'],
        ];

        if (isset($data['uf'])) {
            $query['uf'] = $data['uf'];
        }

        $maxAttempts = (int) config('services.pncp.retries', 3);
        $retrySleepSeconds = (int) config('services.pncp.retry_sleep_seconds', 5);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $client->request('GET', $url, [
                    'query' => $query,
                    'http_errors' => false,
                ]);

                $statusCode = $response->getStatusCode();
                $content = $response->getBody()->getContents();
                $body = json_decode($content, true);

                if ($statusCode !== 200) {
                    $this->logPncpRequestFailure($statusCode, $query, $content, $attempt, $maxAttempts);

                    if ($this->shouldRetryPncpRequest($statusCode, $attempt, $maxAttempts)) {
                        sleep($retrySleepSeconds);
                        continue;
                    }

                    return ['status' => false, 'error' => "PNCP retornou HTTP {$statusCode}."];
                }

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::channel('tender_imports')->warning('PNCP retornou resposta inválida', [
                        'query' => $query,
                        'attempt' => $attempt,
                        'body' => $this->shortenPncpResponseBody($content),
                    ]);

                    return ['status' => false, 'error' => 'PNCP retornou resposta inválida.'];
                }

                if (!isset($body['data']) || !count($body['data'])) {
                    return ['status' => false, 'error' => 'Não foi possível obter os dados.'];
                }

                return ['status' => true, 'data' => $body['data']];

            } catch (RequestException $e) {
                $content = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;

                Log::channel('tender_imports')->warning('Erro de requisição ao buscar dados do PNCP', [
                    'query' => $query,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'error' => $e->getMessage(),
                    'body' => $this->shortenPncpResponseBody($content),
                ]);

                if ($attempt < $maxAttempts) {
                    sleep($retrySleepSeconds);
                    continue;
                }

                return ['status' => false, 'error' => $e->getMessage()];
            } catch (\Exception $e) {
                Log::channel('tender_imports')->error('Erro ao buscar dados do PNCP', [
                    'query' => $query,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'error' => $e->getMessage(),
                ]);
                return ['status' => false, 'error' => $e->getMessage()];
            }
        }

        return ['status' => false, 'error' => 'Não foi possível obter os dados do PNCP.'];
    }

    private function logPncpRequestFailure(int $statusCode, array $query, ?string $body, int $attempt, int $maxAttempts): void
    {
        Log::channel('tender_imports')->warning('PNCP retornou erro HTTP', [
            'status_code' => $statusCode,
            'query' => $query,
            'attempt' => $attempt,
            'max_attempts' => $maxAttempts,
            'body' => $this->shortenPncpResponseBody($body),
        ]);
    }

    private function shouldRetryPncpRequest(int $statusCode, int $attempt, int $maxAttempts): bool
    {
        return $attempt < $maxAttempts && in_array($statusCode, [429, 500, 502, 503, 504], true);
    }

    private function shortenPncpResponseBody(?string $body): ?string
    {
        if ($body === null) {
            return null;
        }

        return substr(trim(strip_tags($body)), 0, 500);
    }

    public function getItemsPNCP($cnpj, $ano, $sequencial)
    {
        $client = $this->pncpClient();
        $url = "https://pncp.gov.br/api/pncp/v1/orgaos/{$cnpj}/compras/{$ano}/{$sequencial}/itens";

        try {
            $response = $client->request('GET', $url, ['query' => []]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode !== 200 || !isset($body) || !count($body)) {
                return ['status' => false, 'error' => 'Não foi possível obter os dados.'];
            } 

            return ['status' => true, 'data' => $body];

        } catch (\Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    public function getEditalPNCP($cnpj, $ano, $sequencial)
    {
        $client = $this->pncpClient();
        $url = "https://pncp.gov.br/api/pncp/v1/orgaos/{$cnpj}/compras/{$ano}/{$sequencial}/arquivos";

        try {
            $response = $client->request('GET', $url, ['query' => []]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode !== 200 || !isset($body) || !count($body)) {
                return ['status' => false, 'error' => 'Não foi possível obter os dados.'];
            } 

            return ['status' => true, 'data' => $body];

        } catch (\Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }
}
