<?php

namespace App\Console\Commands;

use App\Mail\TenderNotificationMail;
use App\Models\Tender;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-notification-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia notificações por e-mail sobre novas licitações';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::where('has_notification', true)
            ->whereNotNull('email')
            ->whereNotNull('state')
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('is_admin', true);
            })
            ->get();

        foreach ($users as $user) {
            $state = $this->getState($user->state);

            if (! $state) {
                continue;
            }

            $tendersCount = Tender::where('uf', $state)
                ->whereBetween(
                    'created_at',
                    [
                        Carbon::now()->subDays(2)->startOfDay(),
                        Carbon::now()->endOfDay(),
                    ]
                )
                ->count();

            if ($tendersCount > 0) {
                Mail::to($user->email)->send(
                    new TenderNotificationMail($tendersCount, $state)
                );
            }
        }

        return Command::SUCCESS;
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
            'sao paulo' => 'SP', 'sergipe' => 'SE', 'tocantins' => 'TO',
        ];

        $normalizedState = $this->normalizeString($state);

        if (in_array(strtoupper($normalizedState), $states)) {
            return strtoupper($normalizedState);
        }

        return $states[$normalizedState] ?? null;
    }

    private function normalizeString($string)
    {
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

        return strtolower(trim($string));
    }
}
