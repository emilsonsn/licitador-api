<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenderNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tendersCount;
    public $state;

    /**
     * Create a new message instance.
     */
    public function __construct($tendersCount, $state)
    {
        $this->tendersCount = $tendersCount;
        $this->state = $state;
    }

    /**
     * Get the message envelope.
     */
    public function build()
    {
        return $this->view('emails.tender_notification')
            ->with([
                'tendersCount' => $this->tendersCount,
                'state' => $this->state,
            ])
            ->subject('Novas licitações disponíveis');
    }
}
