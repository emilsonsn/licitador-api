<?php

namespace App\Traits;

use App\Models\SystemLog;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

trait AlertaLicitacaoTrait
{
    public function prepareDataAlerta(){
        $this->baseUrl = "https://alertalicitacao.com.br/api/v1";
        $this->client = new Client();
        $this->token = "10c06e68bb556fbf5a06a0892c3565bb";
    }

    public function searchDataAlertaLicitacao($data)
    {
        try {
            $this->prepareDataAlerta();

            if(!isset($this->token)){
                throw new Exception('Token não encontrado.');
            }
            
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
                $error = json_encode($body);
                throw new Exception("searchDataAlertaLicitacao -> $error");
            } 

            return ['status' => true, 'data' => $body['licitacoes'], 'paginaAtual' => $body['paginas']];

        } catch (\Exception $error) {
            Log::error($error->getMessage());
            SystemLog::create([
                'action' => 'searchDataAlertaLicitacao',
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'error' => $error->getMessage(),
            ]);
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function getDataPNCP($tender)
    {
        try {
            $this->prepareDataAlerta();

            if (strpos($tender->number_purchase, 'PNCP') !== false) {
                $numberPurchaseSplit = explode('-', $tender->number_purchase);
                $cnpj = $numberPurchaseSplit[1];
                $yearPurchase = $numberPurchaseSplit[count($numberPurchaseSplit) - 1];
                $sequential = $numberPurchaseSplit[3];                
    
                return ['cnpj' => $cnpj, 'year' => $yearPurchase, 'sequential' => $sequential];
            }
        } catch (Exception $error) {
            return ["status" => false, "error" => $error->getMessage()];
        }
    }
}