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
        $this->token = "99d70cc04c0cc2d17d2e0c4e615ca065";
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
                'id_portal' => $data['id_portal'] ?? '',
                'data_insercao' => $data['data_insercao'] ?? '',
                'pagina' => $data['pagina'] ?? 1,
                'token' => $this->token,
            ];

            $queryParams = array_filter($queryParams, static function ($value) {
                return $value !== '';
            });
        
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
            Log::channel('tender_imports')->error('Erro ao buscar dados do Alerta Licitação', [
                'error' => $error->getMessage(),
            ]);
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
