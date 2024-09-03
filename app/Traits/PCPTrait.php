<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait PCPTrait
{
    public function prepareData(){
        $this->baseUrl = "https://apipcp.portaldecompraspublicas.com.br/publico";
        $this->client = new Client();
        $this->publicKey = env('PUBLIC_KEY');
    }

    public function searchDataPCP($data)
    {
        try {
            $this->prepareData();
            $url = $this->baseUrl . "/processosAbertos";

            $response = $this->client->request('GET', $url, [
                'query' => [
                    'publicKey' => $this->publicKey,
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

    public function getEditalPCP($idLicitacao)
    {    
        try {
            $this->prepareData();
            $url = $this->baseUrl . "/obteranexoslicitacao";

            $response = $this->client->request('GET', $url, [
                'query' => [
                    'publicKey' => $this->publicKey,
                    'idLicitacao' => $idLicitacao,
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $bodyContent = $response->getBody()->getContents();
            $body = json_decode($bodyContent, true);

            if ($statusCode !== 200 || !isset($body['documentosFs']) || !count($body['documentosFs'])) {
                return ['status' => false, 'error' => 'Não foi possível obter os dados.'];
            } 

            return ['status' => true, 'data' => $body['documentosFs']];

        } catch (\Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    public function getItemPCP($idLicitacao)
    {
        try {
            $this->prepareData();
            $url = $this->baseUrl . "/obterItensEmDisputa";

            $response = $this->client->request('GET', $url, [
                'query' => [
                    'publicKey' => $this->publicKey,
                    'idLicitacao' => $idLicitacao,
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $bodyContent = $response->getBody()->getContents();
            $body = json_decode($bodyContent, true);

            if ($statusCode !== 200 || isset($body['success'])) {
                return ['status' => false, 'error' => 'Não foi possível obter os dados.'];
            } 

            return ['status' => true, 'data' => $body];

        } catch (\Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

}
