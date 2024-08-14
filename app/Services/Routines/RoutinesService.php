<?php

namespace App\Services\Routines;

use App\Models\SystemLog;
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

            $modalitys = [
                1,  // Leilão - Eletrônico
                6,  // Pregão - Eletrônico
                7,  // Pregão - Presencial
                13, //Leilão - Presencial
                2,  // Diálogo Competitivo
                3,  // Concurso
                4,  // Concorrência - Eletrônica
                5,  // Concorrência - Presencial
                8,  // Dispensa de Licitação
                9,  // Inexigibilidade
                10, //M anifestação de Interesse
                11, //P ré-qualificação
                12, //Credenciamento
            ];

            shuffle($modalitys);

            foreach($modalitys as $modality ){
                $pagina = 1;
                while (true){
                    $data = [
                        'dataFinal' => Carbon::now()->addYear()->format('Ymd'),
                        'pagina' => $pagina,
                        'tamanhoPagina' => 20,
                        'codigoModalidadeContratacao' => $modality
                    ];

                    $result = $this->searchDataPNCP($data);

                    if(!$result['status'] || !isset($result['data']) || !count($result['data'])){
                        Log::error('Data vázia: PNCP');
                        SystemLog::create([
                            'action' => 'data not found PNCP',
                            'file' => '',
                            'line' => 0,
                            'error' => $result['data'],
                        ]);
                        sleep(60);
                        break;
                    }

                    $this->tenderService->createAll($result['data']);                    
                    $pagina+=1;
                    sleep(3);
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

}
