<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait ComprasApiTrait
{
    public function prepareDataCompras(){
        $this->baseUrl = "https://compras.api.portaldecompraspublicas.com.br/v2";    
        $this->client = new Client();
    }

    public function getTenderImminenceDesert($page){
        $this->prepareDataCompras();
        $url = $this->baseUrl . "/licitacao/processos?codigoStatus=4&municipio=0&pagina=$page";

        $response = $this->client->request('GET', $url);     
        
        $data = json_decode($response->getBody(), true);

        if(!isset($data['result']) || !count($data['result'])){
            return [
                'status' => false,
                'error' => 'Não foi possível obter os dados.'
            ];        }

        return [
            'status' => true,
            'data' => $data['result']
        ];        
    }

    public function getEditalComprasApi($id){
        $this->prepareDataCompras();
        $url = $this->baseUrl . "/licitacao/$id/documentos/processo";

        $response = $this->client->request('GET', $url);     
        
        $data = json_decode($response->getBody(), true);

        if(!isset($data) || !count($data)){
            return [
                'status' => false,
                'error' => 'Não foi possível obter os editais.'
            ];        }

        return [
            'status' => true,
            'data' => $data
        ];        
    }

    public function getItemsComprasApi($id){
        $this->prepareDataCompras();
        $url = $this->baseUrl . "/licitacao/$id/itens";

        $response = $this->client->request('GET', $url);     
        
        $data = json_decode($response->getBody(), true);

        if(!isset($data['itens']['result']) || !count($data['itens']['result'])){
            return [
                'status' => false,
                'error' => 'Não foi possível obter os itens.'
            ];        }

        return [
            'status' => true,
            'data' => $data['itens']['result']
        ];        
    }
}