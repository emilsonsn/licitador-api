<?php

namespace App\Console\Commands;

use App\Services\Routines\RoutinesService;
use Illuminate\Console\Command;

class PncpSearchLastTenDays extends Command
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
    protected $signature = 'app:pncp-search-last-ten-days';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Busca licitações no PNCP dos últimos 10 dias até hoje';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->routineService->populate_database_last_ten_days();
    }
}
