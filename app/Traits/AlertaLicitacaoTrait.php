<?php

namespace App\Traits;

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
                $yearPurchase = $tender->year_purchase;
                $numberSplit = explode('0', $numberPurchaseSplit[2]);
                $tenderNumber = end($numberSplit);
    
                $urls = [
                    "http://pncp.gov.br/pncp-api/v1/orgaos/$cnpj/compras/$yearPurchase/$tenderNumber/arquivos/1",
                    "http://pncp.gov.br/pncp-api/v1/orgaos/$cnpj/compras/$yearPurchase/$tenderNumber/arquivos/2",
                    "http://pncp.gov.br/pncp-api/v1/orgaos/$cnpj/compras/$yearPurchase/$tenderNumber/arquivos/3",
                    "http://pncp.gov.br/pncp-api/v1/orgaos/$cnpj/compras/$yearPurchase/$tenderNumber/arquivos/4",
                ];
    
                $existingUrls = [];
    
                foreach ($urls as $url) {
                    $response = $this->client->request('HEAD', $url);
    
                    if ($response->getStatusCode() === 200) {
                        $existingUrls[] = ["url" => $url];
                    }
                }
    
                if (empty($existingUrls)) {
                    throw new Exception ('Edital não encontrado');
                } 
            
                return ["status" => true, "data" => $existingUrls];
            }
        } catch (\Exception $error) {
            return ["status" => false, "error" => $error->getMessage()];
        }
    }
}