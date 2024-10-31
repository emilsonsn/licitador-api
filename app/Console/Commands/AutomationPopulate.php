<?php

namespace App\Console\Commands;

use App\Models\Automation;
use App\Models\SystemLog;
use App\Services\Routines\RoutinesService;
use Exception;
use Illuminate\Console\Command;

class AutomationPopulate extends Command
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
    protected $signature = 'app:automation-populate';

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
        $automations = Automation::where('status', 'Waiting')
            ->get();

        foreach($automations as $automation) {
            try{
                $automation->status = 'Running';
                $automation->save();
                $state = $automation->state;
                $city = $automation->city;
                $this->routineService->automation_alerta_licitacao($state, $city);
                $automation->status = 'Finished';
                $automation->save();
            }catch(Exception $error){
                $automation->status = 'Error';
                $automation->save();
                SystemLog::create([
                    'action' => 'app:automation-populate',
                    'file' => $error->getFile(),
                    'line' => $error->getLine(),
                    'error' => $error->getMessage(),
                ]);
            }
        }
    }
}
