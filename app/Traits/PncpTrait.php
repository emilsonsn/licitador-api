<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait PncpTrait
{

    public function searchDataPNCP($data)
    {

        $client = new Client();
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
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    public function getEditalPNCP($cnpj, $ano, $sequencial)
    {
        $client = new Client();
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
