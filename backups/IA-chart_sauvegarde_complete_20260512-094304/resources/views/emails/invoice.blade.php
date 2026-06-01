{{-- Cette vue contient le corps du mail envoye avec la facture en piece jointe. --}}
<p>Bonjour {{ $facture->client->name }},</p>
<p>Veuillez trouver ci-joint votre facture {{ $facture->invoice_number }}.</p>
<p>Merci pour votre confiance.</p>
