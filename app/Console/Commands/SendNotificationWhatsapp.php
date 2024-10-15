<?php

namespace App\Console\Commands;

use App\Models\Tender;
use App\Models\User;
use App\Traits\EvolutionTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendNotificationWhatsapp extends Command
{
    use EvolutionTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-notification-whatsapp';

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
        $users = User::where('has_notification', true)
            ->whereNotNull('phone')
            ->whereNotNull('state')
            ->where(function($query){
                $query->where('is_active', true)
                    ->orWhere('is_admin', true);
            })
            ->get();

        foreach($users as $user){

            $state = $this->getState($user->state);

            $tendersCount = Tender::where('uf', $state)
                ->whereBetween('created_at', [Carbon::yesterday()->format('y-m-d'), Carbon::today()->format('y-m-d')])
                ->count();

            if($tendersCount > 0){
                $phone = $this->preparePhone($user->phone);
                $this->sendTenderNotification($phone, $tendersCount, $state);
            }            
        }
    }

    private function getState($state)
    {
        $states = [
            'acre' => 'AC', 'alagoas' => 'AL', 'amapa' => 'AP', 'amazonas' => 'AM',
            'bahia' => 'BA', 'ceara' => 'CE', 'distrito federal' => 'DF', 'espirito santo' => 'ES',
            'goias' => 'GO', 'maranhao' => 'MA', 'mato grosso' => 'MT', 'mato grosso do sul' => 'MS',
            'minas gerais' => 'MG', 'para' => 'PA', 'paraiba' => 'PB', 'parana' => 'PR',
            'pernambuco' => 'PE', 'piaui' => 'PI', 'rio de janeiro' => 'RJ', 'rio grande do norte' => 'RN',
            'rio grande do sul' => 'RS', 'rondonia' => 'RO', 'roraima' => 'RR', 'santa catarina' => 'SC',
            'sao paulo' => 'SP', 'sergipe' => 'SE', 'tocantins' => 'TO'
        ];
    
        $normalizedState = $this->normalizeString($state);
    
        if (in_array(strtoupper($normalizedState), $states)) {
            return strtoupper($normalizedState);
        }
    
        return $states[$normalizedState] ?? null;
    }

    private function preparePhone($phone)
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (substr($phone, 0, 2) !== '55') {
            $phone = '55' . $phone;
        }

        return trim($phone);
    }

    private function normalizeString($string)
    {
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        return strtolower(trim($string));
    }
    
    
}
