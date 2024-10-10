<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait EvolutionTrait
{
    public function prepareDataEvolution(){
        $this->baseUrl = "https://evo.localizadordeeditais.com.br";
        $this->apiKey = env('EVO_API_KEY');
        $this->accountName = env('EVO_ACCOUNT_NAME');
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
}