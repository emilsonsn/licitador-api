<?php

namespace App\Console\Commands;

use App\Mail\DocumentExpirationNotificationMail;
use App\Models\File;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class DocumentNotificationEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:document-notification-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia notificações por e-mail para documentos que expiram hoje';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::whereNotNull('email')
            ->where('has_notification', true)
            ->get();

        foreach ($users as $user) {
            $dueDocuments = File::where('user_id', $user->id)
                ->whereDate('expiration_date', Carbon::today())
                ->get();

            if (! count($dueDocuments)) {
                continue;
            }

            foreach ($dueDocuments as $document) {
                Mail::to($user->email)->send(
                    new DocumentExpirationNotificationMail($document->description)
                );
            }
        }

        return Command::SUCCESS;
    }
}
