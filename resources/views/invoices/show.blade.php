@extends('layouts.app')
{{-- Cette vue affiche une facture a l ecran avec une presentation proche du PDF final. --}}

@section('page-title', 'Voir la facture')
@section('page-description', 'Affichage complet de la facture avec le meme esprit visuel que le PDF')
@section('content')
@php
    // Cette preparation calcule les valeurs utiles a l affichage detaille de la facture.
    $normalizeColor = function (?string $color, string $fallback, float $maxLuminance = 0.74): string {
        $value = strtoupper(trim((string) $color));

        if (! preg_match('/^#?[0-9A-F]{6}$/', $value)) {
            return $fallback;
        }

        $value = ltrim($value, '#');
        $red = hexdec(substr($value, 0, 2));
        $green = hexdec(substr($value, 2, 2));
        $blue = hexdec(substr($value, 4, 2));
        $luminance = (0.2126 * $red + 0.7152 * $green + 0.0722 * $blue) / 255;

        return $luminance > $maxLuminance ? $fallback : '#' . $value;
    };
    $logoUrl = $settings->logo_path ? asset('storage/' . $settings->logo_path) . '?v=' . ($settings->updated_at?->timestamp ?? time()) : null;
    $backgroundUrl = $settings->invoice_background_path ? asset('storage/' . $settings->invoice_background_path) . '?v=' . ($settings->updated_at?->timestamp ?? time()) : null;
    $primaryColor = $normalizeColor($settings->invoice_primary_color, '#111827');
    $secondaryColor = $normalizeColor($settings->invoice_secondary_color, '#f3f4f6', 0.9);
    $clientFullName = trim($invoice->client->name . ' ' . ($invoice->client->post_name ?? ''));
    $invoiceCurrency = $invoice->invoiceCurrency();
@endphp

<style>
    /* Ce bloc CSS controle toute la presentation visuelle de la facture a l ecran. */
    .invoice-show-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 280px;
        gap: 24px;
        align-items: start;
    }
    .invoice-view-shell {
        position: relative;
        overflow: hidden;
        border: 1px solid #d7e1e9;
        border-radius: 26px;
        background: #ffffff;
        padding: 24px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    }
    .invoice-view-watermark {
        position: absolute;
        inset: 0;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        opacity: 0.05;
        pointer-events: none;
    }
    .invoice-view-body {
        position: relative;
        z-index: 1;
    }
    .invoice-view-header {
        display: grid;
        grid-template-columns: 130px 1fr 260px;
        gap: 18px;
        align-items: start;
    }
    .invoice-view-logo {
        width: 96px;
        height: 96px;
        border-radius: 20px;
        background: #f3f6fa;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        color: #0f172a;
        font-weight: 800;
    }
    .invoice-view-logo img {
        width: auto;
        height: auto;
        max-width: 100%;
        max-height: 100%;
        display: block;
        object-fit: contain;
    }
    .invoice-view-title {
        margin: 0;
        color: {{ $primaryColor }};
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .invoice-view-company {
        margin-top: 12px;
        color: #64748b;
        line-height: 1.65;
    }
    .invoice-view-meta {
        border: 1px solid #dbe4ec;
        border-radius: 18px;
        overflow: hidden;
    }
    .invoice-view-meta-row {
        display: grid;
        grid-template-columns: 110px 1fr;
    }
    .invoice-view-meta-row span,
    .invoice-view-meta-row strong {
        padding: 10px 12px;
        border-bottom: 1px solid #e4ebf1;
        font-size: 0.92rem;
    }
    .invoice-view-meta-row:last-child span,
    .invoice-view-meta-row:last-child strong {
        border-bottom: none;
    }
    .invoice-view-meta-row span {
        background: #f8fafc;
        color: #64748b;
        text-transform: uppercase;
        font-size: 0.72rem;
        letter-spacing: 0.05em;
    }
    .invoice-view-party-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
        margin-top: 24px;
    }
    .invoice-view-party-card {
        border: 1px solid #dbe4ec;
        border-radius: 18px;
        overflow: hidden;
        background: #ffffff;
    }
    .invoice-view-party-head {
        padding: 10px 14px;
        background: {{ $primaryColor }};
        color: #ffffff;
        text-transform: uppercase;
        font-size: 0.74rem;
        letter-spacing: 0.08em;
        font-weight: 700;
    }
    .invoice-view-party-body {
        padding: 14px;
        min-height: 118px;
        color: #475569;
        line-height: 1.7;
    }
    .invoice-view-party-body strong {
        color: #0f172a;
    }
    .invoice-view-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 22px;
    }
    .invoice-view-table th,
    .invoice-view-table td {
        border: 1px solid #dbe4ec;
        padding: 11px 12px;
    }
    .invoice-view-table th {
        background: {{ $primaryColor }};
        color: #ffffff;
        text-transform: uppercase;
        font-size: 0.76rem;
        letter-spacing: 0.06em;
    }
    .invoice-view-table tbody tr:nth-child(even) td {
        background: #f8fafc;
    }
    .invoice-view-right {
        text-align: right;
    }
    .invoice-view-summary {
        display: flex;
        justify-content: flex-end;
        margin-top: 18px;
    }
    .invoice-view-summary table {
        width: 320px;
        border-collapse: collapse;
    }
    .invoice-view-summary td {
        border: 1px solid #dbe4ec;
        padding: 10px 12px;
    }
    .invoice-view-summary td:first-child {
        background: #f8fafc;
        color: #64748b;
        text-transform: uppercase;
        font-size: 0.74rem;
        letter-spacing: 0.05em;
    }
    .invoice-view-summary .grand td {
        background: {{ $primaryColor }};
        color: #ffffff;
        border-color: {{ $primaryColor }};
        font-weight: 700;
    }
    .invoice-view-footer {
        display: grid;
        grid-template-columns: 1.3fr 0.8fr;
        gap: 24px;
        margin-top: 24px;
    }
    .invoice-view-section-title {
        margin-bottom: 10px;
        color: {{ $primaryColor }};
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 800;
    }
    .invoice-view-note {
        color: #475569;
        line-height: 1.7;
    }
    .invoice-view-signature {
        text-align: right;
    }
    .invoice-view-signature-mark {
        margin-top: 28px;
        font-size: 1.7rem;
        font-style: italic;
        color: #94a3b8;
    }
    .invoice-view-signature-line {
        display: inline-block;
        min-width: 190px;
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px solid #94a3b8;
        color: #64748b;
        text-transform: uppercase;
        font-size: 0.76rem;
    }
    .invoice-view-bank {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
        margin-top: 24px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
    }
    .invoice-view-bank-card {
        color: #475569;
        line-height: 1.7;
    }
    .invoice-view-chip {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 999px;
        margin-bottom: 8px;
        background: {{ $secondaryColor }};
        color: {{ $primaryColor }};
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .invoice-view-footnote {
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid #e2e8f0;
        text-align: center;
        color: #64748b;
        font-size: 0.84rem;
    }
    .invoice-action-card {
        position: sticky;
        top: 24px;
    }
    @media (max-width: 1180px) {
        .invoice-show-layout {
            grid-template-columns: 1fr;
        }
        .invoice-action-card {
            position: static;
        }
    }
    @media (max-width: 900px) {
        .invoice-view-header,
        .invoice-view-party-grid,
        .invoice-view-footer,
        .invoice-view-bank {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="invoice-show-layout">
    {{-- Cette grande zone affiche la facture complete avec le meme esprit visuel que le PDF. --}}
    <section class="invoice-view-shell">
        @if($backgroundUrl)
            {{-- Cette image sert de fond decoratif de la facture quand un fond a ete configure. --}}
            <div class="invoice-view-watermark" style="background-image: url('{{ $backgroundUrl }}');"></div>
        @endif

        <div class="invoice-view-body">
            {{-- Cette entete affiche le logo, les informations entreprise et le bloc numero/date. --}}
            <div class="invoice-view-header">
                <div>
                    <div class="invoice-view-logo">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Logo">
                        @else
                            GA
                        @endif
                    </div>
                </div>

                <div>
                    <h2 class="invoice-view-title">{{ $settings->invoice_title ?: 'FACTURE' }}</h2>
                    <div class="invoice-view-company">
                        {{ $settings->company_name }}<br>
                        {{ $settings->company_address ?: 'Adresse entreprise' }}<br>
                        {{ $settings->company_email ?: 'Email entreprise' }}<br>
                        {{ $settings->company_phone ?: 'Telephone entreprise' }}
                    </div>
                </div>

                <div class="invoice-view-meta">
                    <div class="invoice-view-meta-row">
                        <span>Numero</span>
                        <strong>{{ $invoice->invoice_number }}</strong>
                    </div>
                    <div class="invoice-view-meta-row">
                        <span>Date</span>
                        <strong>{{ $invoice->invoice_date->format('d/m/Y') }}</strong>
                    </div>
                    <div class="invoice-view-meta-row">
                        <span>Echeance</span>
                        <strong>{{ $invoice->due_date?->format('d/m/Y') ?: 'A definir' }}</strong>
                    </div>
                    <div class="invoice-view-meta-row">
                        <span>Commercial</span>
                        <strong>{{ $invoice->user->name }}</strong>
                    </div>
                    <div class="invoice-view-meta-row">
                        <span>Devise</span>
                        <strong>{{ $invoiceCurrency }}</strong>
                    </div>
                </div>
            </div>

            {{-- Ces deux blocs affichent seulement l emetteur et le client pour eviter les repetitions. --}}
            <div class="invoice-view-party-grid">
                <div class="invoice-view-party-card">
                    <div class="invoice-view-party-head">{{ $settings->invoice_bill_from_label ?: 'Emetteur' }}</div>
                    <div class="invoice-view-party-body">
                        <strong>{{ $settings->company_name }}</strong><br>
                        {{ $settings->company_address ?: 'Adresse entreprise' }}<br>
                        {{ $settings->company_email ?: 'Email entreprise' }}<br>
                        {{ $settings->company_phone ?: 'Telephone entreprise' }}
                    </div>
                </div>
                <div class="invoice-view-party-card">
                    <div class="invoice-view-party-head">{{ $settings->invoice_bill_to_label ?: 'Facturer a' }}</div>
                    <div class="invoice-view-party-body">
                        <strong>{{ $clientFullName }}</strong><br>
                        {{ $invoice->client->company ?: 'Entreprise client' }}<br>
                        {{ $invoice->client->address ?: 'Adresse client' }}<br>
                        {{ $invoice->client->email ?: 'Email client' }}<br>
                        {{ $invoice->client->phone ?: 'Telephone client' }}
                    </div>
                </div>
            </div>

            {{-- Ce tableau contient le detail complet des lignes facturees. --}}
            <table class="invoice-view-table">
                <thead>
                    <tr>
                        <th style="width: 46%;">{{ $settings->invoice_description_label ?: 'Description' }}</th>
                        <th style="width: 12%;" class="invoice-view-right">{{ $settings->invoice_quantity_label ?: 'Qte' }}</th>
                        <th style="width: 20%;" class="invoice-view-right">{{ $settings->invoice_unit_price_label ?: 'Prix unitaire HT' }}</th>
                        <th style="width: 22%;" class="invoice-view-right">{{ $settings->invoice_amount_label ?: 'Montant HT' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->details as $detail)
                        <tr>
                            <td>{{ $detail->description }}</td>
                            <td class="invoice-view-right">{{ $detail->quantity }}</td>
                            <td class="invoice-view-right">{{ number_format($invoice->convertAmountForInvoice((float) $detail->unit_price_ht), 2) }} {{ $invoiceCurrency }}</td>
                            <td class="invoice-view-right">{{ number_format($invoice->convertAmountForInvoice((float) $detail->line_total_ht), 2) }} {{ $invoiceCurrency }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Cette zone affiche les totaux HT, TVA et total TTC. --}}
            <div class="invoice-view-summary">
                <table>
                    <tr>
                        <td>Total HT</td>
                        <td class="invoice-view-right">{{ number_format($invoice->convertAmountForInvoice((float) $invoice->total_ht), 2) }} {{ $invoiceCurrency }}</td>
                    </tr>
                    <tr>
                        <td>TVA {{ number_format($invoice->tax_rate, 2) }}%</td>
                        <td class="invoice-view-right">{{ number_format($invoice->convertAmountForInvoice((float) $invoice->tax_amount), 2) }} {{ $invoiceCurrency }}</td>
                    </tr>
                    <tr class="grand">
                        <td>Total TTC</td>
                        <td class="invoice-view-right">{{ number_format($invoice->convertAmountForInvoice((float) $invoice->total_ttc), 2) }} {{ $invoiceCurrency }}</td>
                    </tr>
                </table>
            </div>

            {{-- Cette zone affiche les conditions de paiement et la signature. --}}
            <div class="invoice-view-footer">
                <div>
                    <div class="invoice-view-section-title">{{ $settings->invoice_payment_label ?: 'Conditions de paiement' }}</div>
                    <div class="invoice-view-note">{{ $settings->invoice_terms ?: 'Aucune condition particuliere renseignee.' }}</div>
                    <div class="invoice-view-section-title" style="margin-top: 18px;">Note de facture</div>
                    <div class="invoice-view-note">{{ $invoice->notes ?: 'Aucune note ajoutee sur cette facture.' }}</div>
                </div>
                <div class="invoice-view-signature">
                    @if($settings->invoice_show_signature ?? true)
                        <div class="invoice-view-signature-mark">Signature</div>
                        <div class="invoice-view-signature-line">{{ $settings->invoice_signature_label ?: 'Signature autorisee' }}</div>
                    @endif
                </div>
            </div>

            {{-- Cette ligne affiche les informations bancaires et de contact visibles en bas de la facture. --}}
            <div class="invoice-view-bank">
                <div class="invoice-view-bank-card">
                    <span class="invoice-view-chip">{{ $settings->invoice_footer_bank_label ?: 'Banque' }}</span><br>
                    {{ $settings->bank_name ?: 'Non renseigne' }}<br>
                    {{ $settings->bank_account_name ?: 'Nom du compte non renseigne' }}
                </div>
                <div class="invoice-view-bank-card">
                    <span class="invoice-view-chip">{{ $settings->invoice_footer_account_label ?: 'Compte' }}</span><br>
                    {{ $settings->bank_account_number ?: 'Numero non renseigne' }}<br>
                    SWIFT : {{ $settings->bank_swift ?: 'Non renseigne' }}
                </div>
                <div class="invoice-view-bank-card">
                    <span class="invoice-view-chip">{{ $settings->invoice_footer_contact_label ?: 'Contact' }}</span><br>
                    {{ $settings->company_email ?: 'Email non renseigne' }}<br>
                    {{ $settings->company_phone ?: 'Telephone non renseigne' }}
                </div>
            </div>

            @if($settings->invoice_footer_note)
                <div class="invoice-view-footnote">{{ $settings->invoice_footer_note }}</div>
            @endif
        </div>
    </section>

    {{-- Cette colonne de droite contient les actions disponibles sur la facture. --}}
    <aside class="card stack invoice-action-card">
        <div class="card-head">
            <div>
                <h3>Actions facture</h3>
                <p class="muted">Cette vue reprend la presentation du PDF avant telechargement.</p>
            </div>
        </div>
        <div class="note">
            <strong>{{ $invoice->invoice_number }}</strong>
            <div class="muted">{{ ucfirst($invoice->status) }}</div>
        </div>
        {{-- Ce bouton telecharge la facture au format PDF. --}}
        <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-secondary">Telecharger PDF</a>
        @if($invoice->client->email)
            {{-- Ce formulaire envoie la facture par email au client si une adresse existe. --}}
            <form method="POST" action="{{ route('invoices.email', $invoice) }}">
                @csrf
                <button class="btn btn-primary btn-block" type="submit">Envoyer par email</button>
            </form>
        @endif
        @if(auth()->user()->isAdministrator())
            {{-- Ce bouton de suppression est reserve a l administrateur. --}}
            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" onsubmit="return confirm('Supprimer cette facture et remettre le stock a jour ?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger btn-block" type="submit">Supprimer la facture</button>
            </form>
        @endif
    </aside>
</div>
@endsection
