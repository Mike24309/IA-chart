@extends('layouts.app')
{{-- Cette vue permet de creer une facture avec un apercu direct du document final. --}}

@section('page-title', 'Creer une facture')
@section('page-description', 'Saisie de facture avec apercu direct du modele final')
@section('content')
@php
    // Cette preparation calcule les informations utilisees dans l apercu de facture.
    $invoicePreviewNumber = str_replace(
        ['{YEAR}', '{SEQ}'],
        [now()->format('Y'), str_pad((string) (\App\Models\Facture::count() + 1), 5, '0', STR_PAD_LEFT)],
        $settings->invoice_number_format
    );
    $previewDueDate = now()->addDays((int) $settings->invoice_due_days)->format('d/m/Y');
    $logoUrl = $settings->logo_path ? asset('storage/' . $settings->logo_path) . '?v=' . ($settings->updated_at?->timestamp ?? time()) : null;
    $backgroundUrl = $settings->invoice_background_path ? asset('storage/' . $settings->invoice_background_path) . '?v=' . ($settings->updated_at?->timestamp ?? time()) : null;
    $invoiceProductsData = $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'stock' => $product->stock,
            'price' => (float) $product->sale_price,
        ];
    })->values();
@endphp

<style>
    .invoice-create-layout {
        display: grid;
        grid-template-columns: minmax(360px, 460px) minmax(420px, 1fr);
        gap: 24px;
        align-items: start;
    }
    .invoice-create-form {
        position: sticky;
        top: 24px;
    }
    .invoice-line-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 120px 110px;
        gap: 12px;
        align-items: end;
        margin-bottom: 12px;
    }
    .invoice-line-toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .invoice-tax-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 14px;
        background: #eef5ff;
        border: 1px solid #d7e4f5;
        color: #1e3a5f;
        font-weight: 700;
        white-space: nowrap;
    }
    .invoice-tax-badge span {
        color: #64748b;
        font-weight: 600;
    }
    .button-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }
    .invoice-preview-shell {
        position: relative;
        overflow: hidden;
        border: 1px solid #d6e0e8;
        border-radius: 24px;
        background: #fff;
        padding: 24px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    }
    .invoice-preview-watermark {
        position: absolute;
        inset: 0;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        opacity: 0.05;
        pointer-events: none;
    }
    .invoice-preview-body {
        position: relative;
        z-index: 1;
    }
    .invoice-preview-header {
        display: grid;
        grid-template-columns: 130px 1fr 260px;
        gap: 18px;
        align-items: start;
    }
    .invoice-preview-logo {
        width: 140px;
        height: 140px;
        border-radius: 20px;
        background: #f3f6fa;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        font-weight: 700;
        color: #0f172a;
    }
    .invoice-preview-logo img {
        width: auto;
        height: auto;
        max-width: 100%;
        max-height: 100%;
        display: block;
        object-fit: contain;
    }
    .invoice-preview-title {
        margin: 0;
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .invoice-preview-subtitle {
        margin-top: 10px;
        color: #64748b;
        line-height: 1.6;
    }
    .invoice-preview-meta {
        border: 1px solid #dbe4ec;
        border-radius: 18px;
        overflow: hidden;
    }
    .invoice-preview-meta-row {
        display: grid;
        grid-template-columns: 110px 1fr;
    }
    .invoice-preview-meta-row span,
    .invoice-preview-meta-row strong {
        padding: 10px 12px;
        border-bottom: 1px solid #e4ebf1;
        font-size: 0.9rem;
    }
    .invoice-preview-meta-row:last-child span,
    .invoice-preview-meta-row:last-child strong {
        border-bottom: none;
    }
    .invoice-preview-meta-row span {
        background: #f8fafc;
        color: #64748b;
        text-transform: uppercase;
        font-size: 0.72rem;
        letter-spacing: 0.05em;
    }
    .invoice-party-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
        margin-top: 24px;
    }
    .invoice-party-card {
        border: 1px solid #dbe4ec;
        border-radius: 18px;
        overflow: hidden;
        background: #fff;
    }
    .invoice-party-head {
        padding: 10px 14px;
        color: #fff;
        text-transform: uppercase;
        font-size: 0.74rem;
        letter-spacing: 0.08em;
        font-weight: 700;
    }
    .invoice-party-body {
        padding: 14px;
        min-height: 118px;
        color: #475569;
        line-height: 1.65;
    }
    .invoice-party-body strong {
        color: #0f172a;
    }
    .invoice-preview-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 22px;
    }
    .invoice-preview-table th,
    .invoice-preview-table td {
        border: 1px solid #dbe4ec;
        padding: 11px 12px;
        font-size: 0.92rem;
    }
    .invoice-preview-table th {
        color: #fff;
        text-transform: uppercase;
        font-size: 0.76rem;
        letter-spacing: 0.06em;
    }
    .invoice-preview-table tbody tr:nth-child(even) td {
        background: #f8fafc;
    }
    .invoice-preview-right {
        text-align: right;
    }
    .invoice-preview-summary {
        display: flex;
        justify-content: flex-end;
        margin-top: 18px;
    }
    .invoice-preview-summary table {
        width: 320px;
        border-collapse: collapse;
    }
    .invoice-preview-summary td {
        border: 1px solid #dbe4ec;
        padding: 10px 12px;
    }
    .invoice-preview-summary td:first-child {
        background: #f8fafc;
        color: #64748b;
        text-transform: uppercase;
        font-size: 0.74rem;
        letter-spacing: 0.05em;
    }
    .invoice-preview-summary .grand td {
        color: #fff;
        font-weight: 700;
    }
    .invoice-preview-footer {
        display: grid;
        grid-template-columns: 1.3fr 0.8fr;
        gap: 24px;
        margin-top: 24px;
    }
    .invoice-preview-section-title {
        margin-bottom: 10px;
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 800;
    }
    .invoice-preview-note {
        color: #475569;
        line-height: 1.7;
    }
    .invoice-preview-signature {
        text-align: right;
    }
    .invoice-preview-signature-mark {
        margin-top: 28px;
        font-size: 1.7rem;
        font-style: italic;
        color: #94a3b8;
    }
    .invoice-preview-signature-line {
        display: inline-block;
        min-width: 190px;
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px solid #94a3b8;
        color: #64748b;
        text-transform: uppercase;
        font-size: 0.76rem;
    }
    .invoice-preview-bank {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
        margin-top: 24px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
    }
    .invoice-preview-bank-card {
        color: #475569;
        line-height: 1.65;
    }
    .invoice-preview-chip {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 999px;
        margin-bottom: 8px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .invoice-preview-footnote {
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid #e2e8f0;
        text-align: center;
        color: #64748b;
        font-size: 0.84rem;
    }
    @media (max-width: 1180px) {
        .invoice-create-layout {
            grid-template-columns: 1fr;
        }
        .invoice-create-form {
            position: static;
        }
        .button-row {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 900px) {
        .invoice-preview-header,
        .invoice-party-grid,
        .invoice-preview-footer,
        .invoice-preview-bank {
            grid-template-columns: 1fr;
        }
        .invoice-line-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="invoice-create-layout">
    {{-- Cette colonne de gauche contient le formulaire de creation de la facture. --}}
    <form method="POST" action="{{ route('invoices.store') }}" class="card stack invoice-create-form" id="invoice-form">
        @csrf
        <div class="card-head">
            <div>
                <h3>Informations de la facture</h3>
                <p class="muted">L apercu a droite suit la meme logique visuelle que le PDF final.</p>
            </div>
        </div>

        <div class="form-grid">
            {{-- Cette zone gere le choix du client et les informations generales de la facture. --}}
            <div class="full">
                <label>Rechercher un client deja cree</label>
                <input type="text" id="client-search" placeholder="Tapez le nom ou le postnom du client">
            </div>
            <div>
                <label>Client existant</label>
                <select name="client_id" id="client-id">
                    <option value="">Creer un nouveau client</option>
                    @foreach($clients as $client)
                        @php($clientFullName = trim($client->name . ' ' . ($client->post_name ?? '')))
                        <option
                            value="{{ $client->id }}"
                            data-name="{{ $client->name }}"
                            data-post-name="{{ $client->post_name }}"
                            data-email="{{ $client->email }}"
                            data-phone="{{ $client->phone }}"
                            data-address="{{ $client->address }}"
                            data-company="{{ $client->company }}"
                        >
                            {{ $clientFullName }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div><label>Date facture</label><input type="date" name="invoice_date" id="invoice-date" value="{{ now()->toDateString() }}" required></div>
            <input type="hidden" id="tax-rate" value="{{ number_format((float) $settings->default_tax_rate, 2, '.', '') }}">
            <div><label>Nom client</label><input type="text" name="client_name" id="client-name" placeholder="Nom du client"></div>
            <div><label>Postnom client</label><input type="text" name="client_post_name" id="client-post-name" placeholder="Postnom du client"></div>
            <div><label>Email client</label><input type="email" name="client_email" id="client-email"></div>
            <div><label>Telephone client</label><input type="text" name="client_phone" id="client-phone"></div>
            <div><label>Entreprise client</label><input type="text" name="client_company" id="client-company"></div>
            <div class="full"><label>Adresse client</label><textarea name="client_address" id="client-address"></textarea></div>
            <div class="full"><label>Notes</label><textarea name="notes" id="invoice-notes"></textarea></div>
        </div>

        <div class="card nested-card">
            <div class="card-head">
                <h3>Lignes de facture</h3>
                <div class="invoice-line-toolbar">
                    <div class="invoice-tax-badge">
                        <span>TVA appliquee</span>
                        <strong><span id="invoice-tax-rate-badge">{{ number_format((float) $settings->default_tax_rate, 2) }}</span>%</strong>
                    </div>
                    {{-- Ce bouton ajoute une nouvelle ligne de produit dans la facture. --}}
                    <button type="button" class="btn btn-secondary" id="add-line">Ajouter une ligne</button>
                </div>
            </div>
            <div id="invoice-lines">
                {{-- Cette premiere ligne sert de modele aux autres lignes ajoutees en JavaScript. --}}
                <div class="invoice-line-grid invoice-line" data-line-index="0">
                    <div>
                        <label>Produit</label>
                        <select name="items[0][produit_id]" class="invoice-product-select">
                            @foreach($products as $product)
                                <option
                                    value="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-price="{{ number_format((float) $product->sale_price, 2, '.', '') }}"
                                    data-stock="{{ $product->stock }}"
                                >
                                    {{ $product->name }} ({{ $product->stock }} en stock)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Quantite</label>
                        <input type="number" name="items[0][quantity]" class="invoice-quantity-input" value="1" min="1">
                    </div>
                    <div>
                        <label>Action</label>
                        <button type="button" class="btn btn-danger remove-line">Retirer</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="button-row">
            <button class="btn btn-primary" type="submit" name="invoice_action" value="create">Creer la facture</button>
            <button class="btn btn-secondary" type="submit" name="invoice_action" value="create_send">Creer et envoyer</button>
            <button class="btn btn-secondary" type="submit" name="invoice_action" value="create_download">Creer et telecharger</button>
        </div>
    </form>

    {{-- Cette colonne de droite affiche l apercu direct de la facture finale. --}}
    <section class="invoice-preview-shell">
        @if($backgroundUrl)
            <div class="invoice-preview-watermark" style="background-image: url('{{ $backgroundUrl }}');"></div>
        @endif

        <div class="invoice-preview-body">
            {{-- Cette entete affiche le logo, le titre de la facture et le bloc numero/date. --}}
            <div class="invoice-preview-header">
                <div>
                    <div class="invoice-preview-logo">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Logo">
                        @else
                            GA
                        @endif
                    </div>
                </div>

                <div>
                    <h2 class="invoice-preview-title" id="preview-title" style="color: {{ $settings->invoice_primary_color ?: '#1f6f97' }};">{{ $settings->invoice_title ?: 'FACTURE' }}</h2>
                    <div class="invoice-preview-subtitle">
                        {{ $settings->company_name }}<br>
                        {{ $settings->company_address ?: 'Adresse entreprise' }}<br>
                        {{ $settings->company_email ?: 'Email entreprise' }}<br>
                        {{ $settings->company_phone ?: 'Telephone entreprise' }}
                    </div>
                </div>

                <div class="invoice-preview-meta">
                    <div class="invoice-preview-meta-row">
                        <span>Numero</span>
                        <strong id="preview-invoice-number">{{ $invoicePreviewNumber }}</strong>
                    </div>
                    <div class="invoice-preview-meta-row">
                        <span>Date</span>
                        <strong id="preview-invoice-date">{{ now()->format('d/m/Y') }}</strong>
                    </div>
                    <div class="invoice-preview-meta-row">
                        <span>Echeance</span>
                        <strong id="preview-due-date">{{ $previewDueDate }}</strong>
                    </div>
                    <div class="invoice-preview-meta-row">
                        <span>Commercial</span>
                        <strong>{{ auth()->user()->name }}</strong>
                    </div>
                </div>
            </div>

            {{-- Ces deux blocs affichent l emetteur et le client sans repetition inutile. --}}
            <div class="invoice-party-grid">
                <div class="invoice-party-card">
                    <div class="invoice-party-head" style="background: {{ $settings->invoice_primary_color ?: '#1f6f97' }};">{{ $settings->invoice_bill_from_label ?: 'Emetteur' }}</div>
                    <div class="invoice-party-body">
                        <strong>{{ $settings->company_name }}</strong><br>
                        {{ $settings->company_address ?: 'Adresse entreprise' }}<br>
                        {{ $settings->company_email ?: 'Email entreprise' }}<br>
                        {{ $settings->company_phone ?: 'Telephone entreprise' }}
                    </div>
                </div>
                <div class="invoice-party-card">
                    <div class="invoice-party-head" style="background: {{ $settings->invoice_primary_color ?: '#1f6f97' }};">{{ $settings->invoice_bill_to_label ?: 'Facturer a' }}</div>
                    <div class="invoice-party-body">
                        <strong id="preview-client-name">Nom complet du client</strong><br>
                        <span id="preview-client-company">Entreprise client</span><br>
                        <span id="preview-client-address">Adresse client</span><br>
                        <span id="preview-client-email">Email client</span><br>
                        <span id="preview-client-phone">Telephone client</span>
                    </div>
                </div>
            </div>

            {{-- Ce tableau affiche les lignes de produits facturees. --}}
            <table class="invoice-preview-table">
                <thead>
                    <tr>
                        <th style="width: 46%; background: {{ $settings->invoice_primary_color ?: '#1f6f97' }};">{{ $settings->invoice_description_label ?: 'Description' }}</th>
                        <th style="width: 12%; background: {{ $settings->invoice_primary_color ?: '#1f6f97' }};" class="invoice-preview-right">{{ $settings->invoice_quantity_label ?: 'Qte' }}</th>
                        <th style="width: 20%; background: {{ $settings->invoice_primary_color ?: '#1f6f97' }};" class="invoice-preview-right">{{ $settings->invoice_unit_price_label ?: 'Prix unitaire HT' }}</th>
                        <th style="width: 22%; background: {{ $settings->invoice_primary_color ?: '#1f6f97' }};" class="invoice-preview-right">{{ $settings->invoice_amount_label ?: 'Montant HT' }}</th>
                    </tr>
                </thead>
                <tbody id="preview-lines-body">
                    <tr>
                        <td>{{ $products->first()?->name ?: 'Produit' }}</td>
                        <td class="invoice-preview-right">1</td>
                        <td class="invoice-preview-right">{{ number_format((float) ($products->first()?->sale_price ?? 0), 2) }} {{ $settings->currency }}</td>
                        <td class="invoice-preview-right">{{ number_format((float) ($products->first()?->sale_price ?? 0), 2) }} {{ $settings->currency }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- Cette zone affiche les totaux HT, TVA et TTC. --}}
            <div class="invoice-preview-summary">
                <table>
                    <tr>
                        <td>Total HT</td>
                        <td class="invoice-preview-right" id="preview-total-ht">{{ number_format((float) ($products->first()?->sale_price ?? 0), 2) }} {{ $settings->currency }}</td>
                    </tr>
                    <tr>
                        <td>TVA <span id="preview-tax-rate">{{ number_format((float) $settings->default_tax_rate, 2) }}</span>%</td>
                        <td class="invoice-preview-right" id="preview-tax-amount">{{ number_format(((float) ($products->first()?->sale_price ?? 0) * ((float) $settings->default_tax_rate / 100)), 2) }} {{ $settings->currency }}</td>
                    </tr>
                    <tr class="grand">
                        <td style="background: {{ $settings->invoice_primary_color ?: '#1f6f97' }};">Total TTC</td>
                        <td style="background: {{ $settings->invoice_primary_color ?: '#1f6f97' }};" class="invoice-preview-right" id="preview-total-ttc">{{ number_format((float) ($products->first()?->sale_price ?? 0) * (1 + ((float) $settings->default_tax_rate / 100)), 2) }} {{ $settings->currency }}</td>
                    </tr>
                </table>
            </div>

            {{-- Cette zone affiche les conditions de paiement et la signature. --}}
            <div class="invoice-preview-footer">
                <div>
                    <div class="invoice-preview-section-title" style="color: {{ $settings->invoice_primary_color ?: '#1f6f97' }};">{{ $settings->invoice_payment_label ?: 'Conditions de paiement' }}</div>
                    <div class="invoice-preview-note" id="preview-terms">{{ $settings->invoice_terms ?: 'Aucune condition particuliere renseignee.' }}</div>
                </div>
                <div class="invoice-preview-signature">
                    @if($settings->invoice_show_signature ?? true)
                        <div class="invoice-preview-signature-mark">Signature</div>
                        <div class="invoice-preview-signature-line">{{ $settings->invoice_signature_label ?: 'Signature autorisee' }}</div>
                    @endif
                </div>
            </div>

            {{-- Cette ligne affiche les informations bancaires et de contact visibles dans le pied de facture. --}}
            <div class="invoice-preview-bank">
                <div class="invoice-preview-bank-card">
                    <span class="invoice-preview-chip" style="background: {{ $settings->invoice_secondary_color ?: '#eef4f8' }}; color: {{ $settings->invoice_primary_color ?: '#1f6f97' }};">{{ $settings->invoice_footer_bank_label ?: 'Banque' }}</span><br>
                    {{ $settings->bank_name ?: 'Non renseigne' }}<br>
                    {{ $settings->bank_account_name ?: 'Nom du compte non renseigne' }}
                </div>
                <div class="invoice-preview-bank-card">
                    <span class="invoice-preview-chip" style="background: {{ $settings->invoice_secondary_color ?: '#eef4f8' }}; color: {{ $settings->invoice_primary_color ?: '#1f6f97' }};">{{ $settings->invoice_footer_account_label ?: 'Compte' }}</span><br>
                    {{ $settings->bank_account_number ?: 'Numero non renseigne' }}<br>
                    SWIFT : {{ $settings->bank_swift ?: 'Non renseigne' }}
                </div>
                <div class="invoice-preview-bank-card">
                    <span class="invoice-preview-chip" style="background: {{ $settings->invoice_secondary_color ?: '#eef4f8' }}; color: {{ $settings->invoice_primary_color ?: '#1f6f97' }};">{{ $settings->invoice_footer_contact_label ?: 'Contact' }}</span><br>
                    {{ $settings->company_email ?: 'Email non renseigne' }}<br>
                    {{ $settings->company_phone ?: 'Telephone non renseigne' }}
                </div>
            </div>

            @if($settings->invoice_footer_note)
                <div class="invoice-preview-footnote">{{ $settings->invoice_footer_note }}</div>
            @endif
        </div>
    </section>
</div>

<script>
    window.invoiceProducts = @json($invoiceProductsData);
</script>
<script>
    (() => {
        const form = document.getElementById('invoice-form');
        const clientSearch = document.getElementById('client-search');
        const clientSelect = document.getElementById('client-id');
        const linesContainer = document.getElementById('invoice-lines');
        const addLineButton = document.getElementById('add-line');
        const currency = @json($settings->currency);

        const formatMoney = (value) => `${Number(value || 0).toFixed(2)} ${currency}`;
        const formatDate = (value) => {
            if (!value) {
                return '--/--/----';
            }

            const date = new Date(`${value}T00:00:00`);

            if (Number.isNaN(date.getTime())) {
                return '--/--/----';
            }

            return date.toLocaleDateString('fr-FR');
        };

        const setText = (id, value, fallback = '-') => {
            const element = document.getElementById(id);

            if (element) {
                element.textContent = value && String(value).trim() !== '' ? value : fallback;
            }
        };

        const createProductOptions = () => window.invoiceProducts.map((product) => `
            <option value="${product.id}" data-name="${product.name}" data-price="${product.price}" data-stock="${product.stock}">
                ${product.name} (${product.stock} en stock)
            </option>
        `).join('');

        const refreshClientPreview = () => {
            const selectedOption = clientSelect.options[clientSelect.selectedIndex];
            const selectedClient = clientSelect.value ? {
                name: selectedOption.dataset.name || '',
                postName: selectedOption.dataset.postName || '',
                email: selectedOption.dataset.email || '',
                phone: selectedOption.dataset.phone || '',
                address: selectedOption.dataset.address || '',
                company: selectedOption.dataset.company || '',
            } : null;

            const payload = {
                name: selectedClient?.name || document.getElementById('client-name').value,
                postName: selectedClient?.postName || document.getElementById('client-post-name').value,
                email: selectedClient?.email || document.getElementById('client-email').value,
                phone: selectedClient?.phone || document.getElementById('client-phone').value,
                address: selectedClient?.address || document.getElementById('client-address').value,
                company: selectedClient?.company || document.getElementById('client-company').value,
            };

            const fullName = [payload.name, payload.postName].filter((value) => value && String(value).trim() !== '').join(' ');

            setText('preview-client-name', fullName, 'Nom complet du client');
            setText('preview-client-company', payload.company, 'Entreprise client');
            setText('preview-client-address', payload.address, 'Adresse client');
            setText('preview-client-email', payload.email, 'Email client');
            setText('preview-client-phone', payload.phone, 'Telephone client');
        };

        const refreshDatePreview = () => {
            const invoiceDateValue = document.getElementById('invoice-date').value;
            const dueDays = Number(@json((int) $settings->invoice_due_days));
            const baseDate = invoiceDateValue ? new Date(`${invoiceDateValue}T00:00:00`) : new Date();

            setText('preview-invoice-date', formatDate(invoiceDateValue), '--/--/----');

            if (!Number.isNaN(baseDate.getTime())) {
                baseDate.setDate(baseDate.getDate() + dueDays);
                setText('preview-due-date', baseDate.toLocaleDateString('fr-FR'), '--/--/----');
            }
        };

        const refreshLinesPreview = () => {
            const rows = Array.from(linesContainer.querySelectorAll('.invoice-line'));
            const previewBody = document.getElementById('preview-lines-body');
            const taxRate = Number(document.getElementById('tax-rate').value || 0);
            let totalHt = 0;
            let html = '';

            rows.forEach((row) => {
                const select = row.querySelector('.invoice-product-select');
                const quantityInput = row.querySelector('.invoice-quantity-input');
                const selectedOption = select.options[select.selectedIndex];
                const productName = selectedOption?.dataset.name || 'Produit';
                const unitPrice = Number(selectedOption?.dataset.price || 0);
                const quantity = Math.max(1, Number(quantityInput.value || 1));
                const lineTotal = unitPrice * quantity;

                totalHt += lineTotal;

                html += `
                    <tr>
                        <td>${productName}</td>
                        <td class="invoice-preview-right">${quantity}</td>
                        <td class="invoice-preview-right">${formatMoney(unitPrice)}</td>
                        <td class="invoice-preview-right">${formatMoney(lineTotal)}</td>
                    </tr>
                `;
            });

            if (!html) {
                html = `
                    <tr>
                        <td>Produit</td>
                        <td class="invoice-preview-right">0</td>
                        <td class="invoice-preview-right">${formatMoney(0)}</td>
                        <td class="invoice-preview-right">${formatMoney(0)}</td>
                    </tr>
                `;
            }

            previewBody.innerHTML = html;

            const taxAmount = totalHt * (taxRate / 100);
            const totalTtc = totalHt + taxAmount;

            setText('preview-total-ht', formatMoney(totalHt));
            setText('preview-tax-rate', taxRate.toFixed(2), '0.00');
            setText('preview-tax-amount', formatMoney(taxAmount));
            setText('preview-total-ttc', formatMoney(totalTtc));
        };

        const createLine = (index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'invoice-line-grid invoice-line';
            wrapper.dataset.lineIndex = index;
            wrapper.innerHTML = `
                <div>
                    <label>Produit</label>
                    <select name="items[${index}][produit_id]" class="invoice-product-select">
                        ${createProductOptions()}
                    </select>
                </div>
                <div>
                    <label>Quantite</label>
                    <input type="number" name="items[${index}][quantity]" class="invoice-quantity-input" value="1" min="1">
                </div>
                <div>
                    <label>Action</label>
                    <button type="button" class="btn btn-danger remove-line">Retirer</button>
                </div>
            `;

            linesContainer.appendChild(wrapper);
        };

        addLineButton.addEventListener('click', () => {
            const nextIndex = linesContainer.querySelectorAll('.invoice-line').length;
            createLine(nextIndex);
            refreshLinesPreview();
        });

        linesContainer.addEventListener('click', (event) => {
            if (!event.target.classList.contains('remove-line')) {
                return;
            }

            const rows = linesContainer.querySelectorAll('.invoice-line');

            if (rows.length === 1) {
                return;
            }

            event.target.closest('.invoice-line').remove();
            refreshLinesPreview();
        });

        form.addEventListener('input', (event) => {
            if (event.target.closest('#invoice-lines')) {
                refreshLinesPreview();
            }

            if (event.target.matches('#client-name, #client-post-name, #client-email, #client-phone, #client-address, #client-company')) {
                refreshClientPreview();
            }

            if (event.target.matches('#invoice-date')) {
                refreshDatePreview();
            }

            if (event.target.matches('#tax-rate')) {
                refreshLinesPreview();
            }
        });

        clientSelect.addEventListener('change', () => {
            const hasExistingClient = clientSelect.value !== '';

            if (hasExistingClient) {
                document.getElementById('client-name').value = '';
                document.getElementById('client-post-name').value = '';
                document.getElementById('client-email').value = '';
                document.getElementById('client-phone').value = '';
                document.getElementById('client-address').value = '';
                document.getElementById('client-company').value = '';
            }

            refreshClientPreview();
        });
        clientSearch.addEventListener('input', () => {
            const search = clientSearch.value.trim().toLowerCase();
            let firstMatchValue = '';

            Array.from(clientSelect.options).forEach((option, index) => {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }

                const isMatch = search === '' || option.text.toLowerCase().includes(search);
                option.hidden = !isMatch;

                if (isMatch && firstMatchValue === '') {
                    firstMatchValue = option.value;
                }
            });

            if (search === '') {
                clientSelect.value = '';
            } else if (firstMatchValue !== '') {
                clientSelect.value = firstMatchValue;
            }

            clientSelect.dispatchEvent(new Event('change'));
        });

        refreshClientPreview();
        refreshDatePreview();
        refreshLinesPreview();
    })();
</script>
@endsection
