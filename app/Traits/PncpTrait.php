<?php

namespace App\Traits;

use GuzzleHttp\Client;
use Log;

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

        try {
            $response = $client->request('GET', $url, [
                'query' => [
                    'dataFinal' => $data['dataFinal'],
                    'codigoModalidadeContratacao' => $data['codigoModalidadeContratacao'] ?? null,
                    'pagina' => $data['pagina'],
                    'tamanhoPagina' => $data['tamanhoPagina'],
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode !== 200 || !isset($body['data']) || !count($body['data'])) {
                return ['status' => false, 'error' => 'Não foi possível obter os dados.'];
            } 

            return ['status' => true, 'data' => $body['data']];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar dados do PNCP: {}', ['error' => $e->getMessage()]);
            return ['status' => false, 'error' => $e->getMessage()];
        }
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
