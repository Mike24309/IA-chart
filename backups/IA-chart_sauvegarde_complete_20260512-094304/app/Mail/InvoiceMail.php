<?php

namespace App\Mail;

use App\Models\Facture;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// Ce mailable envoie une facture PDF au client.
class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Facture $facture,
        public string $pdfContent
    ) {
    }

    public function build(): self
    {
        return $this->subject("Facture {$this->facture->invoice_number}")
            ->view('emails.invoice')
            ->attachData($this->pdfContent, "{$this->facture->invoice_number}.pdf", [
                'mime' => 'application/pdf',
            ]);
    }
}
