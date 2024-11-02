<?php

namespace App\Services\Routines;

use App\Models\Item;
use App\Models\Items;
use App\Models\SystemLog;
use App\Models\Tender;
use App\Models\User;
use App\Services\Tender\TenderService;
use App\Traits\AlertaLicitacaoTrait;
use App\Traits\PCPTrait;
use Exception;
use App\Traits\PncpTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RoutinesService
{

    private $tenderService;

    public function __construct(TenderService $tenderService) {
        $this->tenderService = $tenderService;
    }

    use PncpTrait, PCPTrait, AlertaLicitacaoTrait;

    public function populate_database()
    {
        try {
            Log::info('Iniciando PCNP');

            $modalitys = $this->getModality();
            $ufs = $this->getUfs();

            foreach($ufs as $uf){
                foreach($modalitys as $modality ){
                    $pagina = 1;
                    while (true){
                        $data = [
                            'dataFinal' => Carbon::now()->addYear()->format('Ymd'),
                            'pagina' => $pagina,
                            'tamanhoPagina' => 20,
                            'uf' => $uf,
                            'codigoModalidadeContratacao' => $modality
                        ];
    
                        $result = $this->searchDataPNCP($data);
    
                        if(!$result['status'] || !isset($result['data']) || !count($result['data'])){
                            Log::error('Data vázia: PNCP');
                            break;
                        }
    
                        $this->tenderService->createAll($result['data']);                    
                        $pagina+=1;
                    }
                }
            }
                                                                                                
        } catch (Exception $error) {
            Log::error($error->getMessage());
            SystemLog::create([
                'action' => 'populate_database',
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'error' => $error->getMessage(),
            ]);
        }
    }

    public function populate_items_pncp()
    {
        try {
            Log::info('Iniciando items no PNCP');

            Tender::doesntHave('items')
                ->where('api_origin', 'ALERTALICITACAO')
                ->where('number_purchase', 'LIKE', '%PNCP%')
                ->chunk(100, function($tenders) {
                    foreach ($tenders as $tender) {
                        $data = $this->getDataPNCP($tender);
                        $cnpj = $data['cnpj'];
                        $sequencial = $data['sequential'];
                        $ano = $data['year'];

                        $result = $this->getItemsPNCP($cnpj, $ano, $sequencial);

                        if (!isset($result['status']) || !$result['status']) {
                            Log::error('Items não encontrados: PNCP - Tender ID: ' . $tender->id);
                            sleep(1);
                            continue;
                        }

                        $itemsToInsert = [];
                        foreach ($result['data'] as $item) {
                            $itemsToInsert[] = [
                                'tender_id' => $tender->id,
                                'description' => $item['descricao'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        if (!empty($itemsToInsert)) {
                            Item::insert($itemsToInsert);
                        }
                    }
                });

        } catch (Exception $error) {
            Log::error($error->getMessage());
            SystemLog::create([
                'action' => 'populate_database',
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'error' => $error->getMessage(),
            ]);
        }
    }


    public function populate_database_pcp()
    {
        try {
            Log::info('Iniciando PCP');
            $pagina = 1;
            $first = true;

            while (true){
                
                $data = [
                    'pagina' => $pagina,
                ];
                $result = $this->searchDataPCP($data);
                
                if($result['status'] && !isset($result['data']) || !count($result['data'])){
                    Log::error('Data vázia: PCP');
                    SystemLog::create([
                        'action' => 'data not found PCP',
                        'file' => '',
                        'line' => 0,
                        'error' => $result['data'],
                    ]);
                    sleep(60);
                    return;
                }

                if($result['paginaAtual'] === 1 and !$first) return;
                
                Log::info('Criando registros: PCP');
                $this->tenderService->createAllPCP($result['data']);      
                $pagina+=1;
                $first = false;
                sleep(3);
            }                                                                                                                                                                                              
        } catch (Exception $error) {
            Log::info($error->getMessage());
            SystemLog::create([
                'action' => 'populate_database',
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'error' => $error->getMessage(),
            ]);
        }
    }

    public function populate_items_pcp()
    {
        try {
            Log::info('Iniciando busca de itens PCP');

            $tenders = Tender::doesntHave('items')
                ->where('api_origin', 'PCP')
                ->orderBy('proposal_closing_date', 'desc')
                ->get();

            foreach($tenders as $tender){
                $result = $this->getItemPCP($tender->id_licitacao);
                
                if(!isset($result['status']) || !$result['status']){
                    Log::error('Items não encontrados PCP');
                    SystemLog::create([
                        'action' => 'Items not found',
                        'file' => '',
                        'line' => 0,
                        'error' => $result['error'] ?? null,
                    ]);
                    sleep(1);
                    continue;
                }
                foreach($result as $item){
                    Item::created([
                        'tender_id' => $tender->id,
                        'descriptions' => $item->description
                    ]);
                }
            }                                                                                                                                                                                                                                     
        } catch (Exception $error) {
            Log::info($error->getMessage());
            SystemLog::create([
                'action' => 'Items not found',
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'error' => $error->getMessage(),
            ]);
        }
    }

    public function populate_database_alerta_licitacao(){
        try {
            Log::info('Iniciando busca alerta licitação');

            // $modalitys = $this->getModality();
            $modalitys = [5, 6];
            $ufs = $this->getUfs();
            $dates = [];
            
            for($day =0; $day < 2; $day++) {
                $dates[] = Carbon::now()->subDays($day)->format('Y-m-d');
            }

            // $dates = [Carbon::now()->format('Y-m-d')];
            
            foreach($ufs as $uf){
                foreach($modalitys as $modality ){
                    foreach($dates as $data_insercao){
                        $pagina = 1;
                        while (true){
                            $data = [
                                'uf' => $uf,
                                'modalidade' => $modality,
                                'pagina' => $pagina,
                                'data_insercao' => $data_insercao
                            ];
        
                            $result = $this->searchDataAlertaLicitacao($data);
        
                            if(!$result['status'] || !isset($result['data']) || !count($result['data'])){
                                Log::error('Data vázia: ALERTALICITACAO');
                                sleep(10);
                                break;
                            }
                                    
                            $this->tenderService->createAllAlerta($result['data']);                    
                            $pagina+=1;

                            sleep(2);
                        }
                    }
                }
            }
                                                                                                
        } catch (Exception $error) {
            Log::error($error->getMessage());
            SystemLog::create([
                'action' => 'alerta_populate_database',
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'error' => $error->getMessage(),
            ]);
        }
    }

    public function automation_alerta_licitacao($state, $city){
        try {
            Log::info('Iniciando busca auomática alerta licitação');

            // $modalitys = $this->getModality();
            $modalitys = [5, 6];
            $ufs = [$state];
            $dates = [];
            
            for($day =0; $day < 3; $day++) {
                $dates[] = Carbon::now()->subDays($day)->format('Y-m-d');
            }

            foreach($ufs as $uf){
                foreach($modalitys as $modality ){
                    foreach($dates as $data_insercao){
                        $pagina = 1;
                        while (true){
                            $data = [
                                'uf' => $uf,
                                'modalidade' => $modality,
                                'pagina' => $pagina,
                                'data_insercao' => $data_insercao
                            ];
        
                            $result = $this->searchDataAlertaLicitacao($data);
        
                            if(!$result['status'] || !isset($result['data']) || !count($result['data'])){
                                Log::error('Data vázia: ALERTALICITACAO');
                                sleep(2);
                                break;
                            }
                            
                            sleep(2);
        
                            $this->tenderService->createAllAlerta($result['data']);                    
                            $pagina+=1;
                        }
                    }
                }
            }
                                                                                                
        } catch (Exception $error) {
            Log::error($error->getMessage());
            SystemLog::create([
                'action' => 'automation_populate_database',
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function getModality() : array {
        $modalitys = [
            5, //Pregão eletrônico
            6, //Dispensas e dispensas eletrônicas
            1, //Convite
            2, //Concorrência
            3, //Leilão
            4, //Tomada de preços
            8, //Pregão presencial
            1, //Chamada/Chamamento público
        ];
        return $modalitys;
    }    

    private function getUfs() : array {
        $ufs = [
            'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
            'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
            'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
        ];
        shuffle($ufs);
        return $ufs;
    }

}


//{ value: '1', label: 'Leilão - Eletrônico' },  1 -> 3
//{ value: '13', label: 'Leilão - Presencial' }, 13 -> 3
//{ value: '7', label: 'Pregão - Presencial' }, 7 -> 8

//{ value: '2', label: 'Diálogo Competitivo' },
//{ value: '3', label: 'Concurso' }, 3 -> 1
//{ value: '4', label: 'Concorrência - Eletrônica' }, 4 -> 2
//{ value: '5', label: 'Concorrência - Presencial' }, 5 -> 2
//{ value: '6', label: 'Pregão - Eletrônico' }, 6 -> 5
//{ value: '8', label: 'Dispensa de Licitação' }, 8 -> 6

//{ value: '12', label: 'Credenciamento' },
//{ value: '10', label: 'Manifestação de Interesse' },
//{ value: '11', label: 'Pré-qualificação' },
//{ value: '9', label: 'Inexigibilidade' },