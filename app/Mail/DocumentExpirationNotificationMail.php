<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentExpirationNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $documentName;

    /**
     * Create a new message instance.
     */
    public function __construct($documentName)
    {
        $this->documentName = $documentName;
    }

    /**
     * Get the message envelope.
     */
    public function build()
    {
        return $this->view('emails.document_expiration_notification')
            ->with([
                'documentName' => $this->documentName,
            ])
            ->subject('Alerta de expiração de documento');
    }
}
