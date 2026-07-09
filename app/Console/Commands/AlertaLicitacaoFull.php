<?php

namespace App\Console\Commands;

use App\Services\Routines\RoutinesService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AlertaLicitacaoFull extends Command
{

    private $routineService;

    public function __construct(RoutinesService $routineService) {
        parent::__construct(); 
        $this->routineService = $routineService;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:alerta-licitacao-full';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->routineService->populate_database_alerta_licitacao([
            'modalidade' => '2,5,6,8',
            'pagina' => 1,
        ]);
    }
}
