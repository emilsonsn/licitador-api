<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait PCPTrait
{

    public function searchDataPCP($data)
    {
        $client = new Client();
        $publicKey = env('PUBLIC_KEY');
        $url = "https://apipcp.portaldecompraspublicas.com.br/publico/processosAbertos";
        
        try {
            $response = $client->request('GET', $url, [
                'query' => [
                    'publicKey' => $publicKey,
                    'pagina' => $data['pagina'],
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode !== 200 || !isset($body['dadosLicitacoes']) || !count($body['dadosLicitacoes'])) {
                return ['status' => false, 'error' => 'Não foi possível obter os dados.'];
            } 

            return ['status' => true, 'data' => $body['dadosLicitacoes'], 'paginaAtual' => $body['paginaAtual']];

        } catch (\Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }
}
