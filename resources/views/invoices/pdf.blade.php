@php
    // Cette fonction prepare les images pour assurer leur affichage dans le PDF DomPDF.
    $embedImage = function (?string $relativePath): ?string {
        if (! $relativePath) {
            return null;
        }

        $absolutePath = storage_path('app/public/' . $relativePath);

        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return null;
        }

        if (filesize($absolutePath) > 2 * 1024 * 1024) {
            return null;
        }

        $mime = mime_content_type($absolutePath) ?: null;
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

        if (! in_array($mime, $allowedMimes, true)) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absolutePath));
    };

    $logoData = $embedImage($settings->logo_path);
    $backgroundData = $embedImage($settings->invoice_background_path);
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
    $primaryColor = $normalizeColor($settings->invoice_primary_color, '#111827');
    $secondaryColor = $normalizeColor($settings->invoice_secondary_color, '#f3f4f6', 0.9);
    $clientFullName = trim($invoice->client->name . ' ' . ($invoice->client->post_name ?? ''));
    $companyAddress = $settings->company_address ?: 'Adresse entreprise';
    $companyEmail = $settings->company_email ?: 'Email entreprise';
    $companyPhone = $settings->company_phone ?: 'Telephone entreprise';
    $clientCompany = $invoice->client->company ?: 'Entreprise client';
    $clientAddress = $invoice->client->address ?: 'Adresse client';
    $clientEmail = $invoice->client->email ?: 'Email client';
    $clientPhone = $invoice->client->phone ?: 'Telephone client';
    $invoiceCurrency = $invoice->invoiceCurrency();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 18px; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #22313f;
            font-size: 11px;
        }
        .document {
            position: relative;
            border: 1px solid #d8e2ea;
            background: #ffffff;
            padding: 18px;
        }
        .background-mark {
            position: absolute;
            top: 120px;
            left: 50%;
            width: 360px;
            margin-left: -180px;
            opacity: 0.05;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header td {
            vertical-align: top;
        }
        .logo-col {
            width: 18%;
        }
        .title-col {
            width: 42%;
        }
        .meta-col {
            width: 40%;
            text-align: right;
        }
        .logo-box {
            min-height: 82px;
        }
        .logo-box img {
            display: block;
            width: auto;
            height: auto;
            max-width: 92px;
            max-height: 78px;
        }
        .company-mini {
            margin-top: 8px;
            line-height: 1.55;
            color: #617282;
            font-size: 9px;
        }
        .doc-title {
            margin: 0;
            color: {{ $primaryColor }};
            font-size: 28px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .doc-subtitle {
            margin-top: 8px;
            color: #5d7081;
            line-height: 1.5;
            font-size: 9px;
        }
        .meta-table {
            margin-left: auto;
            width: 240px;
            border: 1px solid #cfdbe4;
        }
        .meta-table td {
            padding: 7px 9px;
            border: 1px solid #d9e2e9;
            font-size: 9px;
        }
        .meta-label {
            width: 42%;
            background: #f5f8fb;
            color: #66798a;
            text-transform: uppercase;
        }
        .party-table {
            margin-top: 18px;
        }
        .party-table td {
            vertical-align: top;
        }
        .party-box {
            border: 1px solid #d6e0e8;
            background: #ffffff;
        }
        .party-head {
            padding: 8px 10px;
            background: {{ $primaryColor }};
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .party-body {
            padding: 10px;
            min-height: 90px;
            line-height: 1.65;
            color: #3d4f60;
            font-size: 9px;
        }
        .party-body strong {
            color: #1d2d3d;
            font-size: 10px;
        }
        .spacer {
            width: 14px;
        }
        .line-table {
            margin-top: 18px;
        }
        .line-table th {
            background: {{ $primaryColor }};
            border: 1px solid {{ $primaryColor }};
            color: #ffffff;
            padding: 8px 7px;
            font-size: 9px;
            text-transform: uppercase;
        }
        .line-table td {
            border: 1px solid #dbe3ea;
            padding: 8px 7px;
            font-size: 9px;
        }
        .line-table tbody tr:nth-child(even) td {
            background: #f8fbfd;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-top: 14px;
        }
        .totals table {
            width: 270px;
            margin-left: auto;
        }
        .totals td {
            border: 1px solid #dbe3ea;
            padding: 8px 9px;
            font-size: 9px;
        }
        .totals-label {
            background: #f7fafc;
            color: #66788a;
            text-transform: uppercase;
            width: 58%;
        }
        .grand-total td {
            background: {{ $primaryColor }};
            color: #ffffff;
            border-color: {{ $primaryColor }};
            font-weight: 700;
        }
        .bottom-table {
            margin-top: 20px;
        }
        .bottom-table td {
            vertical-align: top;
        }
        .section-title {
            margin-bottom: 8px;
            color: {{ $primaryColor }};
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .text-block {
            line-height: 1.65;
            color: #5e7081;
            font-size: 9px;
        }
        .signature-box {
            text-align: right;
        }
        .signature-script {
            margin-top: 26px;
            font-size: 21px;
            font-style: italic;
            color: #7d8b98;
        }
        .signature-line {
            display: inline-block;
            min-width: 170px;
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #9eaebb;
            font-size: 8px;
            color: #67798a;
            text-transform: uppercase;
        }
        .footer-table {
            margin-top: 18px;
            border-top: 1px solid #d8e2ea;
            padding-top: 10px;
        }
        .footer-table td {
            width: 33.33%;
            vertical-align: top;
            color: #5f7384;
            font-size: 8.5px;
            line-height: 1.6;
        }
        .footer-mid {
            padding: 0 12px;
        }
        .footer-chip {
            display: inline-block;
            padding: 4px 8px;
            margin-bottom: 6px;
            background: {{ $secondaryColor }};
            color: {{ $primaryColor }};
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .footer-note {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #e2e8ee;
            text-align: center;
            color: #6c7d8d;
            font-size: 8px;
        }
    </style>
</head>
<body>
    <div class="document">
        @if($backgroundData)
            <img src="{{ $backgroundData }}" alt="" class="background-mark">
        @endif

        <table class="header">
            <tr>
                <td class="logo-col">
                    <div class="logo-box">
                        @if($logoData)
                            <img src="{{ $logoData }}" alt="Logo entreprise">
                        @endif
                    </div>
                </td>
                <td class="title-col">
                    <h1 class="doc-title">{{ $settings->invoice_title ?: 'FACTURE' }}</h1>
                    <div class="doc-subtitle">
                        {{ $settings->company_name }}<br>
                        {{ $companyAddress }}<br>
                        {{ $companyEmail }}<br>
                        {{ $companyPhone }}
                    </div>
                </td>
                <td class="meta-col">
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">Numero</td>
                            <td>{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Date</td>
                            <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Echeance</td>
                            <td>{{ $invoice->due_date?->format('d/m/Y') ?: 'A definir' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Commercial</td>
                            <td>{{ $invoice->user->name }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Devise</td>
                            <td>{{ $invoiceCurrency }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="party-table">
            <tr>
                <td>
                    <div class="party-box">
                        <div class="party-head">{{ $settings->invoice_bill_from_label ?: 'Emetteur' }}</div>
                        <div class="party-body">
                            <strong>{{ $settings->company_name }}</strong><br>
                            {{ $companyAddress }}<br>
                            {{ $companyEmail }}<br>
                            {{ $companyPhone }}
                        </div>
                    </div>
                </td>
                <td class="spacer"></td>
                <td>
                    <div class="party-box">
                        <div class="party-head">{{ $settings->invoice_bill_to_label ?: 'Facturer a' }}</div>
                        <div class="party-body">
                            <strong>{{ $clientFullName }}</strong><br>
                            {{ $clientCompany }}<br>
                            {{ $clientAddress }}<br>
                            {{ $clientEmail }}<br>
                            {{ $clientPhone }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="line-table">
            <thead>
                <tr>
                    <th style="width: 46%;">{{ $settings->invoice_description_label ?: 'Description' }}</th>
                    <th style="width: 12%;" class="text-right">{{ $settings->invoice_quantity_label ?: 'Qte' }}</th>
                    <th style="width: 20%;" class="text-right">{{ $settings->invoice_unit_price_label ?: 'Prix unitaire HT' }}</th>
                    <th style="width: 22%;" class="text-right">{{ $settings->invoice_amount_label ?: 'Montant HT' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->details as $detail)
                    <tr>
                        <td>{{ $detail->description }}</td>
                        <td class="text-right">{{ $detail->quantity }}</td>
                        <td class="text-right">{{ number_format($invoice->convertAmountForInvoice((float) $detail->unit_price_ht), 2) }} {{ $invoiceCurrency }}</td>
                        <td class="text-right">{{ number_format($invoice->convertAmountForInvoice((float) $detail->line_total_ht), 2) }} {{ $invoiceCurrency }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td class="totals-label">Total HT</td>
                    <td class="text-right">{{ number_format($invoice->convertAmountForInvoice((float) $invoice->total_ht), 2) }} {{ $invoiceCurrency }}</td>
                </tr>
                <tr>
                    <td class="totals-label">TVA {{ number_format($invoice->tax_rate, 2) }}%</td>
                    <td class="text-right">{{ number_format($invoice->convertAmountForInvoice((float) $invoice->tax_amount), 2) }} {{ $invoiceCurrency }}</td>
                </tr>
                <tr class="grand-total">
                    <td>Total TTC</td>
                    <td class="text-right">{{ number_format($invoice->convertAmountForInvoice((float) $invoice->total_ttc), 2) }} {{ $invoiceCurrency }}</td>
                </tr>
            </table>
        </div>

        <table class="bottom-table">
            <tr>
                <td style="width: 55%; padding-right: 18px;">
                    <div class="section-title">{{ $settings->invoice_payment_label ?: 'Conditions de paiement' }}</div>
                    <div class="text-block">{{ $settings->invoice_terms ?: 'Aucune condition particuliere renseignee.' }}</div>
                    <div class="section-title" style="margin-top: 14px;">Note de facture</div>
                    <div class="text-block">{{ $invoice->notes ?: 'Aucune note ajoutee sur cette facture.' }}</div>
                </td>
                <td style="width: 45%;" class="signature-box">
                    @if($settings->invoice_show_signature ?? true)
                        <div class="signature-script">Signature</div>
                        <div class="signature-line">{{ $settings->invoice_signature_label ?: 'Signature autorisee' }}</div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="footer-table">
            <tr>
                <td>
                    <span class="footer-chip">{{ $settings->invoice_footer_bank_label ?: 'Banque' }}</span><br>
                    {{ $settings->bank_name ?: 'Non renseigne' }}<br>
                    {{ $settings->bank_account_name ?: 'Nom du compte non renseigne' }}
                </td>
                <td class="footer-mid">
                    <span class="footer-chip">{{ $settings->invoice_footer_account_label ?: 'Compte' }}</span><br>
                    {{ $settings->bank_account_number ?: 'Numero non renseigne' }}<br>
                    SWIFT : {{ $settings->bank_swift ?: 'Non renseigne' }}
                </td>
                <td>
                    <span class="footer-chip">{{ $settings->invoice_footer_contact_label ?: 'Contact' }}</span><br>
                    {{ $companyEmail }}<br>
                    {{ $companyPhone }}
                </td>
            </tr>
        </table>

        @if($settings->invoice_footer_note)
            <div class="footer-note">{{ $settings->invoice_footer_note }}</div>
        @endif
    </div>
</body>
</html>
