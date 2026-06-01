<?php

namespace App\Jobs;

use App\Mail\InvoiceMail;
use App\Models\Facture;
use App\Services\ParameterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

// Ce job envoie la facture apres la reponse HTTP pour ne pas bloquer l utilisateur.
class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $invoiceId
    ) {
    }

    public function handle(ParameterService $parameterService): void
    {
        $invoice = Facture::with(['client', 'user', 'details.produit'])->find($this->invoiceId);

        if (! $invoice || ! $invoice->client?->email) {
            return;
        }

        $parameterService->applyMailConfiguration();
        $settings = $parameterService->current();

        $pdfContent = Pdf::loadView('invoices.pdf', compact('invoice', 'settings'))
            ->setPaper('a4')
            ->output();

        Mail::to($invoice->client->email)->send(new InvoiceMail($invoice, $pdfContent));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Echec de l envoi email de facture en arriere-plan.', [
            'invoice_id' => $this->invoiceId,
            'message' => $exception->getMessage(),
        ]);
    }
}
