@extends('layouts.app')
{{-- Cette vue centralise les parametres generaux de l entreprise et de la facture. --}}

@section('page-title', 'Parametres')
@section('page-description', 'Configuration generale de l entreprise et du modele de facture')
@section('content')
<section class="card settings-hub">
    <div class="card-head">
        <div>
            <h3>Administration</h3>
            <p class="muted">Gestion centralisee des utilisateurs, des acces et du module IA.</p>
        </div>
    </div>
    <div class="settings-shortcuts">
        {{-- Ces cartes ouvrent directement la gestion des utilisateurs et les reglages IA. --}}
        <a href="{{ route('users.index') }}" class="note settings-shortcut">
            <strong>Gestion des utilisateurs</strong>
            <span class="muted">Creer, modifier, suspendre et attribuer un code de connexion.</span>
        </a>
        <a href="{{ route('users.create') }}" class="note settings-shortcut">
            <strong>Creer un utilisateur</strong>
            <span class="muted">Ajouter un compte avec role et code d acces.</span>
        </a>
        <a href="{{ route('ai.settings.edit') }}" class="note settings-shortcut">
            <strong>Parametres IA</strong>
            <span class="muted">Regler le modele, la sensibilite et les alertes.</span>
        </a>
    </div>
</section>

<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="stack">
    @csrf
    @method('PUT')

    <section class="card form-grid">
        <div class="full card-head"><h3>Identite de l entreprise</h3></div>
        <div><label>Nom entreprise</label><input type="text" name="company_name" value="{{ old('company_name', $settings->company_name) }}" required></div>
        <div><label>Email</label><input type="email" name="company_email" value="{{ old('company_email', $settings->company_email) }}"></div>
        <div><label>Telephone</label><input type="text" name="company_phone" value="{{ old('company_phone', $settings->company_phone) }}"></div>
        <div><label>Devise</label><input type="text" name="currency" value="{{ old('currency', $settings->currency) }}" required></div>
        <div class="full"><label>Adresse</label><textarea name="company_address">{{ old('company_address', $settings->company_address) }}</textarea></div>
        {{-- Ce champ change le logo visible dans l application et sur les factures. --}}
        <div><label>Logo entreprise</label><input type="file" name="logo"></div>
        <div class="settings-preview-cell">
            @if($settings->logo_path)
                <img src="{{ asset('storage/' . $settings->logo_path) }}?v={{ $settings->updated_at?->timestamp ?? time() }}" alt="Logo" class="settings-thumb">
            @else
                <div class="settings-placeholder">Aucun logo</div>
            @endif
        </div>
    </section>

    <section class="card form-grid">
        <div class="full card-head"><h3>Facturation</h3></div>
        <div><label>TVA par defaut</label><input type="number" step="0.01" name="default_tax_rate" value="{{ old('default_tax_rate', $settings->default_tax_rate) }}" required></div>
        <div><label>Format numero facture</label><input type="text" name="invoice_number_format" value="{{ old('invoice_number_format', $settings->invoice_number_format) }}" required></div>
        <div><label>Echeance (jours)</label><input type="number" name="invoice_due_days" value="{{ old('invoice_due_days', $settings->invoice_due_days) }}" required></div>
        <div><label>Stock minimum global</label><input type="number" name="global_minimum_stock" value="{{ old('global_minimum_stock', $settings->global_minimum_stock) }}" required></div>
        <div><label>Titre du document</label><input type="text" name="invoice_title" value="{{ old('invoice_title', $settings->invoice_title ?? 'FACTURE') }}" required></div>
        <div><label>Bloc emetteur</label><input type="text" name="invoice_bill_from_label" value="{{ old('invoice_bill_from_label', $settings->invoice_bill_from_label ?? 'Emetteur') }}" required></div>
        <div><label>Bloc facturer a</label><input type="text" name="invoice_bill_to_label" value="{{ old('invoice_bill_to_label', $settings->invoice_bill_to_label ?? 'Facturer a') }}" required></div>
        <div><label>Libelle paiement</label><input type="text" name="invoice_payment_label" value="{{ old('invoice_payment_label', $settings->invoice_payment_label ?? 'Conditions et modalites de paiement') }}" required></div>
        <div><label>Libelle signature</label><input type="text" name="invoice_signature_label" value="{{ old('invoice_signature_label', $settings->invoice_signature_label ?? 'Signature autorisee') }}" required></div>
        <div><label>Colonne description</label><input type="text" name="invoice_description_label" value="{{ old('invoice_description_label', $settings->invoice_description_label ?? 'Description') }}" required></div>
        <div><label>Colonne quantite</label><input type="text" name="invoice_quantity_label" value="{{ old('invoice_quantity_label', $settings->invoice_quantity_label ?? 'Qte') }}" required></div>
        <div><label>Colonne prix unitaire</label><input type="text" name="invoice_unit_price_label" value="{{ old('invoice_unit_price_label', $settings->invoice_unit_price_label ?? 'Prix unitaire') }}" required></div>
        <div><label>Colonne montant</label><input type="text" name="invoice_amount_label" value="{{ old('invoice_amount_label', $settings->invoice_amount_label ?? 'Montant') }}" required></div>
        {{-- Ces deux champs changent les couleurs visibles dans la facture. --}}
        <div><label>Couleur principale facture</label><input type="color" name="invoice_primary_color" value="{{ old('invoice_primary_color', $settings->invoice_primary_color ?? '#0f172a') }}" required></div>
        <div><label>Couleur secondaire facture</label><input type="color" name="invoice_secondary_color" value="{{ old('invoice_secondary_color', $settings->invoice_secondary_color ?? '#e11d48') }}" required></div>
        {{-- Ce champ permet d ajouter ou de remplacer l image de fond de la facture. --}}
        <div><label>Image de fond facture</label><input type="file" name="invoice_background"></div>
        <div class="settings-preview-cell">
            @if($settings->invoice_background_path)
                <img src="{{ asset('storage/' . $settings->invoice_background_path) }}?v={{ $settings->updated_at?->timestamp ?? time() }}" alt="Fond facture" class="settings-thumb settings-thumb-wide">
            @else
                <div class="settings-placeholder">Aucun fond</div>
            @endif
        </div>
        <div class="full"><label>Conditions generales</label><textarea name="invoice_terms">{{ old('invoice_terms', $settings->invoice_terms) }}</textarea></div>
        <div><label>Bloc pied de page banque</label><input type="text" name="invoice_footer_bank_label" value="{{ old('invoice_footer_bank_label', $settings->invoice_footer_bank_label ?? 'Banque') }}" required></div>
        <div><label>Bloc pied de page compte</label><input type="text" name="invoice_footer_account_label" value="{{ old('invoice_footer_account_label', $settings->invoice_footer_account_label ?? 'Compte') }}" required></div>
        <div><label>Bloc pied de page contact</label><input type="text" name="invoice_footer_contact_label" value="{{ old('invoice_footer_contact_label', $settings->invoice_footer_contact_label ?? 'Contact') }}" required></div>
        <div><label>Afficher la signature</label><select name="invoice_show_signature"><option value="1" @selected(old('invoice_show_signature', (int) ($settings->invoice_show_signature ?? 1)) == 1)>Oui</option><option value="0" @selected(old('invoice_show_signature', (int) ($settings->invoice_show_signature ?? 1)) == 0)>Non</option></select></div>
        <div class="full"><label>Message de bas de page</label><input type="text" name="invoice_footer_note" value="{{ old('invoice_footer_note', $settings->invoice_footer_note) }}" placeholder="Message final visible sur la facture"></div>
    </section>

    <section class="card form-grid">
        {{-- Cette section contient les informations bancaires affichees dans la facture. --}}
        <div class="full card-head"><h3>Informations bancaires</h3></div>
        <div><label>Banque</label><input type="text" name="bank_name" value="{{ old('bank_name', $settings->bank_name) }}"></div>
        <div><label>Nom du compte</label><input type="text" name="bank_account_name" value="{{ old('bank_account_name', $settings->bank_account_name) }}"></div>
        <div><label>Numero du compte</label><input type="text" name="bank_account_number" value="{{ old('bank_account_number', $settings->bank_account_number) }}"></div>
        <div><label>SWIFT</label><input type="text" name="bank_swift" value="{{ old('bank_swift', $settings->bank_swift) }}"></div>
    </section>

    <div>
        <button class="btn btn-primary" type="submit">Enregistrer les parametres</button>
    </div>
</form>
@endsection
