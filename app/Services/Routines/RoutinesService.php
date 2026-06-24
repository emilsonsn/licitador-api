<?php

namespace App\Services\Routines;

use App\Models\SystemLog;
use App\Models\Tender;
use App\Services\Tender\TenderService;
use App\Traits\AlertaLicitacaoTrait;
use App\Traits\ComprasApiTrait;
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

    use PncpTrait, PCPTrait, AlertaLicitacaoTrait, ComprasApiTrait;

    public function populate_database()
    {
        try {
            Log::channel('tender_imports')->info('Iniciando PNCP');

            $modalitys = $this->getModality();
            $ufs = $this->getUfs();
            $total = $this->emptyImportStats();

            foreach($ufs as $uf){
                foreach($modalitys as $modality ){
                    $pagina = 1;
                    while ($pagina < 20){
                        $data = [
                            'dataFinal' => Carbon::now()->addYear()->format('Ymd'),
                            'pagina' => $pagina,
                            'tamanhoPagina' => 30,
                            'uf' => $uf,
                            'codigoModalidadeContratacao' => $modality
                        ];
    
                        $result = $this->searchDataPNCP($data);
    
                        if(!$result['status'] || !isset($result['data']) || !count($result['data'])){
                            Log::channel('tender_imports')->warning('PNCP sem dados para os filtros', [
                                'uf' => $uf,
                                'modalidade' => $modality,
                                'pagina' => $pagina,
                            ]);
                            SystemLog::create([
                                'action' => 'data not found PNCP',
                                'file' => '',
                                'line' => 0,
                                'error' => $result['error'] ?? null,
                            ]);
                            break;
                        }

                        $stats = $this->tenderService->createAll($result['data']);
                        $total = $this->mergeImportStats($total, $stats);

                        Log::channel('tender_imports')->info('PNCP: página importada', [
                            'uf' => $uf,
                            'modalidade' => $modality,
                            'pagina' => $pagina,
                            'recebidas_api' => $stats['received'],
                            'criadas' => $stats['created'],
                            'atualizadas' => $stats['updated'],
                            'total_recebidas_api' => $total['received'],
                            'total_criadas' => $total['created'],
                            'total_atualizadas' => $total['updated'],
                        ]);

                        $pagina+=1;
                        sleep(1);
                    }
                }
            }

            Log::channel('tender_imports')->info('PNCP: importação finalizada', [
                'total_recebidas_api' => $total['received'],
                'total_criadas' => $total['created'],
                'total_atualizadas' => $total['updated'],
            ]);
        } catch (Exception $error) {
            Log::channel('tender_imports')->error('Erro ao importar PNCP', [
                'error' => $error->getMessage(),
            ]);
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
            Log::channel('tender_imports')->info('Iniciando PCP');
            $pagina = 1;
            $first = true;
            $total = $this->emptyImportStats();

            while (true){
                
                $data = [
                    'pagina' => $pagina,
                ];
                $result = $this->searchDataPCP($data);
                
                if(!$result['status'] || !isset($result['data']) || !count($result['data'])){
                    Log::channel('tender_imports')->warning('PCP sem dados para os filtros', [
                        'pagina' => $pagina,
                    ]);
                    SystemLog::create([
                        'action' => 'data not found PCP',
                        'file' => '',
                        'line' => 0,
                        'error' => $result['error'] ?? null,
                    ]);
                    sleep(60);
                    return;
                }

                if($result['paginaAtual'] === 1 and !$first) {
                    Log::channel('tender_imports')->info('PCP: importação finalizada', [
                        'total_recebidas_api' => $total['received'],
                        'total_criadas' => $total['created'],
                        'total_atualizadas' => $total['updated'],
                    ]);
                    return;
                }
                
                $stats = $this->tenderService->createAllPCP($result['data']);
                $total = $this->mergeImportStats($total, $stats);

                Log::channel('tender_imports')->info('PCP: página importada', [
                    'pagina' => $pagina,
                    'recebidas_api' => $stats['received'],
                    'criadas' => $stats['created'],
                    'atualizadas' => $stats['updated'],
                    'total_recebidas_api' => $total['received'],
                    'total_criadas' => $total['created'],
                    'total_atualizadas' => $total['updated'],
                ]);

                $pagina+=1;
                $first = false;
                sleep(3);
            }                                                                                                                                                                                              
        } catch (Exception $error) {
            Log::channel('tender_imports')->error('Erro ao importar PCP', [
                'error' => $error->getMessage(),
            ]);
            SystemLog::create([
                'action' => 'populate_database',
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'error' => $error->getMessage(),
            ]);
        }
    }

    public function populate_compras_imminence_desert()
    {
        try {
            Log::channel('tender_imports')->info('Iniciando Compras API: iminência de deserto');
            $total = $this->emptyImportStats();

            
            for($page = 1; $page < 10; $page++){    
                    $result = $this->getTenderImminenceDesert($page);
    
                    if(!$result['status'] || !isset($result['data']) || !count($result['data'])){
                        Log::channel('tender_imports')->warning('Compras API sem dados para os filtros', [
                            'pagina' => $page,
                        ]);
                        break;
                    }
    
                    $stats = $this->tenderService->createComprasAPI($result['data']);
                    $total = $this->mergeImportStats($total, $stats);

                    Log::channel('tender_imports')->info('Compras API: página importada', [
                        'pagina' => $page,
                        'recebidas_api' => $stats['received'],
                        'criadas' => $stats['created'],
                        'atualizadas' => $stats['updated'],
                        'total_recebidas_api' => $total['received'],
                        'total_criadas' => $total['created'],
                        'total_atualizadas' => $total['updated'],
                    ]);
            }

            Log::channel('tender_imports')->info('Compras API: importação finalizada', [
                'total_recebidas_api' => $total['received'],
                'total_criadas' => $total['created'],
                'total_atualizadas' => $total['updated'],
            ]);

        } catch (Exception $error) {
            Log::channel('tender_imports')->error('Erro ao importar Compras API', [
                'error' => $error->getMessage(),
            ]);
            SystemLog::create([
                'action' => 'populate_database',
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'error' => $error->getMessage(),
            ]);
        }
    }

    public function populate_database_alerta_licitacao(){
        try {
            Log::channel('tender_imports')->info('Iniciando busca alerta licitação');

            $modalitys = [2, 5, 6, 8];
            $ufs = $this->getUfs();
            $total = $this->emptyImportStats();

            $dates = [Carbon::now()->format('Y-m-d')];
            
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
                                Log::channel('tender_imports')->warning('Alerta Licitação sem dados para os filtros', [
                                    'uf' => $uf,
                                    'modalidade' => $modality,
                                    'pagina' => $pagina,
                                    'data_insercao' => $data_insercao,
                                ]);
                                sleep(10);
                                break;
                            }
                                    
                            $stats = $this->tenderService->createAllAlerta($result['data']);
                            $total = $this->mergeImportStats($total, $stats);

                            Log::channel('tender_imports')->info('Alerta Licitação: página importada', [
                                'uf' => $uf,
                                'modalidade' => $modality,
                                'pagina' => $pagina,
                                'data_insercao' => $data_insercao,
                                'recebidas_api' => $stats['received'],
                                'criadas' => $stats['created'],
                                'atualizadas' => $stats['updated'],
                                'total_recebidas_api' => $total['received'],
                                'total_criadas' => $total['created'],
                                'total_atualizadas' => $total['updated'],
                            ]);

                            $pagina+=1;

                            sleep(2);
                        }
                    }
                }
            }

            Log::channel('tender_imports')->info('Alerta Licitação: importação finalizada', [
                'total_recebidas_api' => $total['received'],
                'total_criadas' => $total['created'],
                'total_atualizadas' => $total['updated'],
            ]);
                                                                                                
        } catch (Exception $error) {
            Log::channel('tender_imports')->error('Erro ao importar Alerta Licitação', [
                'error' => $error->getMessage(),
            ]);
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
            Log::channel('tender_imports')->info('Iniciando busca automática alerta licitação', [
                'uf' => $state,
                'cidade' => $city,
            ]);

            // $modalitys = $this->getModality();
            $modalitys = [5, 6];
            $ufs = [$state];
            $dates = [];
            $total = $this->emptyImportStats();
            
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
                                Log::channel('tender_imports')->warning('Automação Alerta Licitação sem dados para os filtros', [
                                    'uf' => $uf,
                                    'cidade' => $city,
                                    'modalidade' => $modality,
                                    'pagina' => $pagina,
                                    'data_insercao' => $data_insercao,
                                ]);
                                sleep(2);
                                break;
                            }
                            
                            sleep(2);
        
                            $stats = $this->tenderService->createAllAlerta($result['data']);
                            $total = $this->mergeImportStats($total, $stats);

                            Log::channel('tender_imports')->info('Automação Alerta Licitação: página importada', [
                                'uf' => $uf,
                                'cidade' => $city,
                                'modalidade' => $modality,
                                'pagina' => $pagina,
                                'data_insercao' => $data_insercao,
                                'recebidas_api' => $stats['received'],
                                'criadas' => $stats['created'],
                                'atualizadas' => $stats['updated'],
                                'total_recebidas_api' => $total['received'],
                                'total_criadas' => $total['created'],
                                'total_atualizadas' => $total['updated'],
                            ]);

                            $pagina+=1;
                        }
                    }
                }
            }

            Log::channel('tender_imports')->info('Automação Alerta Licitação: importação finalizada', [
                'uf' => $state,
                'cidade' => $city,
                'total_recebidas_api' => $total['received'],
                'total_criadas' => $total['created'],
                'total_atualizadas' => $total['updated'],
            ]);
                                                                                                
        } catch (Exception $error) {
            Log::channel('tender_imports')->error('Erro ao importar automação Alerta Licitação', [
                'uf' => $state,
                'cidade' => $city,
                'error' => $error->getMessage(),
            ]);
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
            8,
            4,
            7,
            9,
            3,
            5,
            12,
            13,

            // 1, // Leião - Eletrônico            
            // 2,
            // 3, // Concurso
            // 4,
            // 5,
            // 6, // Pregão - Eletrônico
            // 7, // Pregão - Presencial
            // 8, // Dispensa de Licitação
            // 9,
            // 10,
            // 11,
            // 12,
            // 13, // Leilão - Presencial
            // 14,
            // 15,
            // 16,
            // 17,
            // 18,
            // 19
        ];
        return $modalitys;
    }    

    private function getUfs() : array {
        $ufs = [
            'SP', 'MG', 'RJ', 'PR', 'RS', 'SC', 'BA', 'PE', 'GO', 'DF',
            'CE', 'ES', 'PA', 'MT', 'MS', 'MA', 'PB', 'RN', 'AL', 'PI',
            'RO', 'AM', 'SE', 'TO', 'AC', 'AP', 'RR'
        ];
        return $ufs;
    }

    private function emptyImportStats(): array
    {
        return [
            'received' => 0,
            'created' => 0,
            'updated' => 0,
        ];
    }

    private function mergeImportStats(array $current, array $batch): array
    {
        $current['received'] += $batch['received'];
        $current['created'] += $batch['created'];
        $current['updated'] += $batch['updated'];

        return $current;
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
