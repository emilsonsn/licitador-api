<?php

namespace App\Console\Commands;

use App\Services\Routines\RoutinesService;
use Illuminate\Console\Command;

class PcpGetItems extends Command
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
    protected $signature = 'app:pcp-get-items';

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
        $this->routineService->populate_items_pcp();
    }
}
