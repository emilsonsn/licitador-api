<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait EvolutionTrait
{
    public function prepareDataEvolution(){
        $this->baseUrl = "https://evo.localizadordeeditais.com.br";
        $this->apiKey = "d06f98af803db7b83ec744e3ae41ecd8";
        $this->accountName = "Disparadordenotificacoes";        
        $this->client = new Client();
    }

    public function sendTenderNotification($number, $tendersCount, $state)
    {
        try {
            $this->prepareDataEvolution();
            $url = $this->baseUrl . "/message/sendText/{$this->accountName}";
    
            $text = "🚀 Vamos fazer dinheiro?\n\n";
            $text.="O que você estava esperando aconteceu!\n\n";
            $text.="Chegaram *$tendersCount* licitações novas no seu estado {$state}\n\n";
            $text.="Confirar agora: https://app.localizadordeeditais.com.br/\n";

            $data = [
                'headers' => [
                    'apiKey' => $this->apiKey,
                ],
                'json' => [ 
                    'number' => $number,
                    'text' => $text,
                ]
            ];
    
            $response = $this->client->request('POST', $url, $data);
    
            $statusCode = $response->getStatusCode();
            $bodyContent = $response->getBody()->getContents();
            
            return ['status' => true, 'data' => ['statusCode' => $statusCode, 'bodyContent' => $bodyContent]];
    
        } catch (\Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendFileNotification($number, $documentName)
    {
        try {
            $this->prepareDataEvolution();
            $url = $this->baseUrl . "/message/sendText/{$this->accountName}";
    
            $text = "⚠️ Alerta de expiração de documento\n\n";
            $text.="O documento $documentName expira hoje!";

            $data = [
                'headers' => [
                    'apiKey' => $this->apiKey,
                ],
                'json' => [ 
                    'number' => $number,
                    'text' => $text,
                ]
            ];
    
            $response = $this->client->request('POST', $url, $data);
    
            $statusCode = $response->getStatusCode();
            $bodyContent = $response->getBody()->getContents();
            
            return ['status' => true, 'data' => ['statusCode' => $statusCode, 'bodyContent' => $bodyContent]];
    
        } catch (\Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }
}