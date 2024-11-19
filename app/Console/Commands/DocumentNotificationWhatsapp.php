<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\User;
use App\Traits\EvolutionTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DocumentNotificationWhatsapp extends Command
{
    use EvolutionTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:document-notification-whatsapp';

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
        $users = User::whereNotNull('phone')
            ->where('has_notification', true)
            ->get();

        foreach($users as $user){
            $dueDocuments = File::where('user_id', $user->id)
                ->whereDate('expiration_date', Carbon::now())
                ->get();

            if(!isset($dueDocuments) || !count($dueDocuments)) continue;
            
            foreach($dueDocuments as $document){
                $phone = $this->preparePhone($user->phone);
                $this->sendFileNotification($phone, $document->description);
            }

        }
        return Command::SUCCESS;
    }

    private function preparePhone($phone)
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (substr($phone, 0, 2) !== '55') {
            $phone = '55' . $phone;
        }

        return trim($phone);
    }
}
