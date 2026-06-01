<?php

namespace App\Models;

use App\Models\Concerns\MappeAnciensAttributs;
use Illuminate\Database\Eloquent\Model;

// Ce modele represente les parametres generaux de l'application.
class Parametre extends Model
{
    use MappeAnciensAttributs;

    protected $table = 'parametres';

    protected array $mappageAnciensAttributs = [
        'company_name' => 'nom_entreprise',
        'company_email' => 'email_entreprise',
        'company_phone' => 'telephone_entreprise',
        'company_address' => 'adresse_entreprise',
        'logo_path' => 'chemin_logo',
        'invoice_background_path' => 'chemin_fond_facture',
        'currency' => 'devise',
        'usd_to_fc_rate' => 'taux_usd_fc',
        'default_tax_rate' => 'taux_tva_defaut',
        'default_discount_rate' => 'pourcentage_remise_defaut',
        'invoice_number_format' => 'format_numero_facture',
        'invoice_due_days' => 'delai_echeance_facture_jours',
        'global_minimum_stock' => 'seuil_stock_minimum_global',
        'bank_name' => 'nom_banque',
        'bank_account_name' => 'nom_compte_bancaire',
        'bank_account_number' => 'numero_compte_bancaire',
        'bank_swift' => 'code_swift',
        'invoice_terms' => 'conditions_facture',
        'invoice_primary_color' => 'couleur_principale_facture',
        'invoice_secondary_color' => 'couleur_secondaire_facture',
        'invoice_title' => 'titre_facture',
        'invoice_bill_from_label' => 'libelle_emetteur_facture',
        'invoice_bill_to_label' => 'libelle_facturer_a',
        'invoice_ship_to_label' => 'libelle_envoyer_a',
        'invoice_payment_label' => 'libelle_paiement_facture',
        'invoice_signature_label' => 'libelle_signature_facture',
        'invoice_footer_bank_label' => 'libelle_banque_pied_facture',
        'invoice_footer_account_label' => 'libelle_compte_pied_facture',
        'invoice_footer_contact_label' => 'libelle_contact_pied_facture',
        'invoice_description_label' => 'libelle_description_facture',
        'invoice_quantity_label' => 'libelle_quantite_facture',
        'invoice_unit_price_label' => 'libelle_prix_unitaire_facture',
        'invoice_amount_label' => 'libelle_montant_facture',
        'invoice_show_signature' => 'afficher_signature_facture',
        'invoice_footer_note' => 'note_pied_facture',
    ];

    protected $fillable = [
        'company_name',
        'company_email',
        'company_phone',
        'company_address',
        'logo_path',
        'invoice_background_path',
        'currency',
        'usd_to_fc_rate',
        'default_tax_rate',
        'default_discount_rate',
        'invoice_number_format',
        'invoice_due_days',
        'global_minimum_stock',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_swift',
        'invoice_terms',
        'invoice_primary_color',
        'invoice_secondary_color',
        'invoice_title',
        'invoice_bill_from_label',
        'invoice_bill_to_label',
        'invoice_ship_to_label',
        'invoice_payment_label',
        'invoice_signature_label',
        'invoice_footer_bank_label',
        'invoice_footer_account_label',
        'invoice_footer_contact_label',
        'invoice_description_label',
        'invoice_quantity_label',
        'invoice_unit_price_label',
        'invoice_amount_label',
        'invoice_show_signature',
        'invoice_footer_note',
    ];

    protected $casts = [
        'taux_usd_fc' => 'decimal:4',
        'taux_tva_defaut' => 'decimal:2',
        'pourcentage_remise_defaut' => 'decimal:2',
        'afficher_signature_facture' => 'boolean',
    ];
}
