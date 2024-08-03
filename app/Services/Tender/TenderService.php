<?php

namespace App\Services\Tender;

use App\Models\FavoriteTender;
use App\Models\SystemLog;
use Exception;
use App\Models\Tender;
use Illuminate\Support\Facades\DB;

class TenderService
{
    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $tenders = Tender::with('favorites');
            $auth = auth()->user();

            if($request->has('favorite')){
                $user_id = $auth->id;
                $tenders->whereHas('favorites', function($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                });                
            }

            if ($request->has('modality_ids')) {
                $modality_ids = $request->has('modality_ids');
                foreach ($modality_ids as $indice => $modality_id){
                    if(!$indice) $tenders->where('modality_id', $modality_id);
                    else $tenders->orWhere('modality_id', $modality_id);
                }
            }

            // Status: Aberto, Fechado, Aberto-Fechado, Fechado-Aberto
            if ($request->has('status')) {
                $status = explode(',', $request->input('status'));
                foreach ($status as $indice => $key){
                    $key =  trim($key);
                    if(!$indice) $tenders->where('status', $key);
                    else $tenders->orWhere('status', $key);
                }
            }

            // Tem que consegui buscar por cnpj do orgão. Campo: CNPJ do orgão
            if ($request->has('organ_cnpj')) {
                $tenders->where('organ_cnpj', $request->input('organ_cnpj'));
            }

            // Tem que consegui buscar por nome do orgão. Campo: Orgão
            if ($request->has('organ_name')) {
                $organ_name = $request->input('organ_name');
                $tenders->where('organ_name', 'LIKE', "%$organ_name%");
            }
            
            if ($request->has('uf')) {
                $tenders->where('uf', $request->input('uf'));
            }

            // Só aparece se tiver com a UF selecionada
            if ($request->has('city')) {
                $city = $request->input('city');
                $tenders->where('city', 'LIKE', "%$city%");
            }

            // Campo aberto, será somente um texto, eu vou quebrar o texto por palavras
            if ($request->has('object')) {
                $object = explode(' ' ,$request->input('object'));
                foreach($object as $indice => $key){
                    $key =  trim($key);
                    if(!$indice) $tenders->where('object', 'LIKE', "%$key%");
                    else $tenders->orWhere('object', 'LIKE', "%$key%");
                }
            }

            // Campo aberto de texto. Campo Nº do Processo
            if ($request->has('process')) {
                $process = $request->input('process');
                $tenders->where('process', 'LIKE', "%$process%");
            }

            // Campo aberto de texto. Campo Observação
            if ($request->has('observations')) {
                $observations = $request->input('observations');
                $tenders->where('observations', 'LIKE', "%$observations%");
            }

            if ($request->has('proposal_closing_date_start') && $request->has('proposal_closing_date_end')) {
                $tenders->whereBetween('proposal_closing_date', [$request->input('proposal_closing_date_start'), $request->input('proposal_closing_date_end')]);
            }elseif($request->has('publication_date_start')){
                $tenders->whereDate('proposal_closing_date', '>=', $request->input('proposal_closing_date_start'));
            }elseif($request->has('publication_date_end')){
                $tenders->whereDate('proposal_closing_date', '<=', $request->input('proposal_closing_date_end'));            
            }

            if ($request->has('publication_date_start') && $request->has('publication_date_end')) {
                $tenders->whereBetween('publication_date', [$request->input('publication_date_start'), $request->input('publication_date_end')]);
            }elseif($request->has('publication_date_start')){
                $tenders->whereDate('publication_date', '>=', $request->input('publication_date_start'));
            }elseif($request->has('publication_date_end')){
                $tenders->whereDate('publication_date', '<=', $request->input('publication_date_end'));
            }

            // nome do campo: Data da última atualização
            if ($request->has('update_date_start') && $request->has('update_date_end')) {
                $tenders->whereBetween('update_date', [$request->input('update_date_start'), $request->input('update_date_end')]);
            }elseif($request->has('update_date_start')){
                $tenders->whereDate('update_date', '>=', $request->input('update_date_start'));            
            }elseif($request->has('update_date_end')){
                $tenders->whereDate('update_date', '<=', $request->input('update_date_end'));            
            }

            $tenders = $tenders->paginate($perPage);

            return ['status' => true, 'data' => $tenders];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage()];
        }
    }

    public function favorite($tender_id){
        $favoriteTender = FavoriteTender::where('tender_id', $tender_id)->first();
        $auth = auth()->user();

        if(isset($favoriteTender)){
            $favoriteTender->delete();
        }else{
            FavoriteTender::create([
                'tender_id' => $tender_id,
                'user_id' => $auth->id
            ]);
        }
        return ['status' => true, 'data' => $favoriteTender ?? null];
    }


    public function createAll($tendersData)
    {
        try {
            $tenders = [];
            $batchSize = 20;

            foreach ($tendersData as $tenderData) {
                $tenders[] = [
                    'value' => $tenderData['valorTotalEstimado'] ?? null,
                    'modality' => $tenderData['modalidadeNome'] ?? null,
                    'modality_id' => $tenderData['modalidadeId'] ?? null,
                    'status' => $tenderData['modoDisputaNome'] ?? null,
                    'year_purchase' => $tenderData['anoCompra'] ?? null,
                    'number_purchase' => $tenderData['numeroCompra'] ?? null,
                    'organ_cnpj' => $tenderData['orgaoEntidade']['cnpj'] ?? null,
                    'organ_name' => $tenderData['orgaoEntidade']['razaoSocial'] ?? null,
                    'uf' => $tenderData['unidadeOrgao']['ufSigla'] ?? null,
                    'city' => $tenderData['unidadeOrgao']['municipioNome'] ?? null,
                    'city_code' => $tenderData['unidadeOrgao']['codigoIbge'] ?? null,
                    'description' => $tenderData['amparoLegal']['descricao'] ?? null,
                    'object' => $tenderData['objetoCompra'] ?? null,
                    'instrument_name' => $tenderData['tipoInstrumentoConvocatorioNome'] ?? null,
                    'observations' => $tenderData['informacaoComplementar'] ?? null,
                    'origin_url' => $tenderData['linkSistemaOrigem'] ?? null,
                    'process' => $tenderData['processo'] ?? null,
                    'bid_opening_date' => $tenderData['dataAberturaProposta'] ?? null,
                    'proposal_closing_date' => $tenderData['dataEncerramentoProposta'] ?? null,
                    'publication_date' => $tenderData['dataPublicacaoPncp'] ?? null,
                    'update_date' => $tenderData['dataAtualizacao'] ?? null,
                ];

                if (count($tenders) >= $batchSize) {
                    $this->insertBatch($tenders);
                    $tenders = [];
                }
            }
            
            if (!empty($tenders)) {
                $this->insertBatch($tenders);
            }
            
        } catch (Exception $error) {
            SystemLog::create([
                'action' => 'createAll',
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function insertBatch(array $tenders)
    {
        DB::transaction(function () use ($tenders) {
            foreach ($tenders as $tender) {
                Tender::updateOrCreate(
                    ['process' => $tender['process']],
                    $tender
                );
            }
        });
    }
}   

