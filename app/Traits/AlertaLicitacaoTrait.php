<?php

namespace App\Traits;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;

trait AlertaLicitacaoTrait
{
    public function prepareDataAlerta(){
        $this->baseUrl = "https://alertalicitacao.com.br/api/v1";
        $this->client = new Client();
        $this->token = env('ALERTA_TOKEN');
    }

    public function searchDataAlertaLicitacao($data)
    {
        try {
            $this->prepareDataAlerta();
            
            $queryParams = [
                'uf' => $data['uf'] ?? '',
                'modalidade' => $data['modalidade'] ?? '',
                'data_insercao' => $data['data_insercao'] ?? '',
                'pagina' => $data['pagina'] ?? 1,
                'token' => $this->token,
            ];
        
            $url = $this->baseUrl . '/licitacoesAbertas/?' . http_build_query($queryParams);

            $response = $this->client->request('GET', $url);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode !== 200 || !isset($body['licitacoes']) || !count($body['licitacoes'])) {
                return ['status' => false, 'error' => 'Não foi possível obter os dados.'];
            } 

            return ['status' => true, 'data' => $body['licitacoes'], 'paginaAtual' => $body['paginas']];

        } catch (\Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function getEditalAlerta($tender)
    {
        try {
            $this->prepareDataAlerta();

            if (strpos($tender->number_purchase, 'PNCP') !== false) {
                $numberPurchaseSplit = explode('-', $tender->number_purchase);
                $cnpj = $numberPurchaseSplit[1];
                $yearPurchase = Carbon::now()->year;
                $sequential = $numberPurchaseSplit[3];                
    
                return ['cnpj' => $cnpj, 'year' => $yearPurchase, 'sequential' => $sequential];
            }
        } catch (\Exception $error) {
            return ["status" => false, "error" => $error->getMessage()];
        }
    }
}