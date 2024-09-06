<?php

namespace App\Services\Routines;

use App\Models\Item;
use App\Models\Items;
use App\Models\SystemLog;
use App\Models\Tender;
use App\Models\User;
use App\Services\Tender\TenderService;
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

    use PncpTrait, PCPTrait;

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
            Log::info('Iniciando items no PCNP');

            $tenders = Tender::doesntHave('items')
                ->where('api_origin', 'PNCP')
                ->orderBy('proposal_closing_date', 'desc')
                ->get();

            foreach($tenders as $tender){
                $ano = $tender->year_purchase;
                $sequencial = $tender->sequential_purchase;
                $cnpj = $tender->organ_cnpj;

                $result = $this->getItemsPNCP($cnpj, $ano, $sequencial);

                if(!isset($result['status']) || !$result['status']){
                    Log::error('Items não encontrados: PNCP');
                    sleep(1);
                    continue;
                }

                foreach($result['data'] as $item){
                    Item::create([
                        'tender_id' => $tender->id,
                        'description' => $item['descricao']
                    ]);
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

    private function getModality() : array {
        $modalitys = [
            1,  // Leilão - Eletrônico
            2,  // Diálogo Competitivo
            3,  // Concurso
            4,  // Concorrência - Eletrônica
            5,  // Concorrência - Presencial
            6,  // Pregão - Eletrônico
            7,  // Pregão - Presencial
            13, //Leilão - Presencial
            8,  // Dispensa de Licitação
            9,  // Inexigibilidade
            10, //M anifestação de Interesse
            11, //P ré-qualificação
            12, //Credenciamento
        ];
        shuffle($modalitys);
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
