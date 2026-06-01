<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette migration renomme physiquement les tables et colonnes principales en francais.
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        $this->renameTableIfExists('users', 'utilisateurs');
        $this->renameTableIfExists('facture_details', 'details_facture');
        $this->renameTableIfExists('logs_ia', 'journaux_ia');
        $this->renameTableIfExists('score_performance', 'scores_performance');

        $this->renameColumnIfExists('roles', 'name', 'nom');
        $this->renameColumnIfExists('roles', 'label', 'libelle');

        $this->renameColumnIfExists('utilisateurs', 'name', 'nom');
        $this->renameColumnIfExists('utilisateurs', 'email', 'adresse_email');
        $this->renameColumnIfExists('utilisateurs', 'login_code', 'code_connexion');
        $this->renameColumnIfExists('utilisateurs', 'phone', 'telephone');
        $this->renameColumnIfExists('utilisateurs', 'profile_photo_path', 'chemin_photo_profil');
        $this->renameColumnIfExists('utilisateurs', 'password', 'mot_de_passe');
        $this->renameColumnIfExists('utilisateurs', 'is_active', 'est_actif');
        $this->renameColumnIfExists('utilisateurs', 'last_login_at', 'dernier_login_a');

        $this->renameColumnIfExists('categories', 'name', 'nom');
        $this->renameColumnIfExists('categories', 'is_active', 'est_active');

        $this->renameColumnIfExists('clients', 'name', 'nom');
        $this->renameColumnIfExists('clients', 'phone', 'telephone');
        $this->renameColumnIfExists('clients', 'address', 'adresse');
        $this->renameColumnIfExists('clients', 'company', 'entreprise');

        $this->renameColumnIfExists('produits', 'category_id', 'categorie_id');
        $this->renameColumnIfExists('produits', 'name', 'nom');
        $this->renameColumnIfExists('produits', 'purchase_price', 'prix_achat');
        $this->renameColumnIfExists('produits', 'sale_price', 'prix_vente');
        $this->renameColumnIfExists('produits', 'theoretical_stock', 'stock_theorique');
        $this->renameColumnIfExists('produits', 'physical_stock', 'stock_physique');
        $this->renameColumnIfExists('produits', 'minimum_stock', 'stock_minimum');
        $this->renameColumnIfExists('produits', 'photo_path', 'chemin_photo');
        $this->renameColumnIfExists('produits', 'is_active', 'est_actif');

        $this->renameColumnIfExists('factures', 'invoice_number', 'numero_facture');
        $this->renameColumnIfExists('factures', 'user_id', 'utilisateur_id');
        $this->renameColumnIfExists('factures', 'invoice_date', 'date_facture');
        $this->renameColumnIfExists('factures', 'due_date', 'date_echeance');
        $this->renameColumnIfExists('factures', 'tax_rate', 'taux_tva');
        $this->renameColumnIfExists('factures', 'tax_amount', 'montant_tva');
        $this->renameColumnIfExists('factures', 'discount_amount', 'montant_remise');
        $this->renameColumnIfExists('factures', 'status', 'statut');

        $this->renameColumnIfExists('details_facture', 'quantity', 'quantite');
        $this->renameColumnIfExists('details_facture', 'unit_price_ht', 'prix_unitaire_ht');
        $this->renameColumnIfExists('details_facture', 'line_total_ht', 'total_ligne_ht');

        $this->renameColumnIfExists('mouvements_stock', 'user_id', 'utilisateur_id');
        $this->renameColumnIfExists('mouvements_stock', 'movement_type', 'type_mouvement');
        $this->renameColumnIfExists('mouvements_stock', 'quantity', 'quantite');
        $this->renameColumnIfExists('mouvements_stock', 'reason', 'motif');
        $this->renameColumnIfExists('mouvements_stock', 'movement_date', 'date_mouvement');

        $this->renameColumnIfExists('activites_utilisateurs', 'user_id', 'utilisateur_id');
        $this->renameColumnIfExists('activites_utilisateurs', 'target_type', 'type_cible');
        $this->renameColumnIfExists('activites_utilisateurs', 'target_id', 'cible_id');
        $this->renameColumnIfExists('activites_utilisateurs', 'metadata', 'metadonnees');
        $this->renameColumnIfExists('activites_utilisateurs', 'ip_address', 'adresse_ip');
        $this->renameColumnIfExists('activites_utilisateurs', 'user_agent', 'agent_utilisateur');

        $this->renameColumnIfExists('parametres', 'company_name', 'nom_entreprise');
        $this->renameColumnIfExists('parametres', 'company_email', 'email_entreprise');
        $this->renameColumnIfExists('parametres', 'company_phone', 'telephone_entreprise');
        $this->renameColumnIfExists('parametres', 'company_address', 'adresse_entreprise');
        $this->renameColumnIfExists('parametres', 'logo_path', 'chemin_logo');
        $this->renameColumnIfExists('parametres', 'invoice_background_path', 'chemin_fond_facture');
        $this->renameColumnIfExists('parametres', 'currency', 'devise');
        $this->renameColumnIfExists('parametres', 'default_tax_rate', 'taux_tva_defaut');
        $this->renameColumnIfExists('parametres', 'invoice_number_format', 'format_numero_facture');
        $this->renameColumnIfExists('parametres', 'invoice_due_days', 'delai_echeance_facture_jours');
        $this->renameColumnIfExists('parametres', 'global_minimum_stock', 'seuil_stock_minimum_global');
        $this->renameColumnIfExists('parametres', 'bank_name', 'nom_banque');
        $this->renameColumnIfExists('parametres', 'bank_account_name', 'nom_compte_bancaire');
        $this->renameColumnIfExists('parametres', 'bank_account_number', 'numero_compte_bancaire');
        $this->renameColumnIfExists('parametres', 'bank_swift', 'code_swift');
        $this->renameColumnIfExists('parametres', 'invoice_terms', 'conditions_facture');
        $this->renameColumnIfExists('parametres', 'invoice_primary_color', 'couleur_principale_facture');
        $this->renameColumnIfExists('parametres', 'invoice_secondary_color', 'couleur_secondaire_facture');
        $this->renameColumnIfExists('parametres', 'invoice_title', 'titre_facture');
        $this->renameColumnIfExists('parametres', 'invoice_bill_from_label', 'libelle_emetteur_facture');
        $this->renameColumnIfExists('parametres', 'invoice_bill_to_label', 'libelle_facturer_a');
        $this->renameColumnIfExists('parametres', 'invoice_ship_to_label', 'libelle_envoyer_a');
        $this->renameColumnIfExists('parametres', 'invoice_payment_label', 'libelle_paiement_facture');
        $this->renameColumnIfExists('parametres', 'invoice_signature_label', 'libelle_signature_facture');
        $this->renameColumnIfExists('parametres', 'invoice_footer_bank_label', 'libelle_banque_pied_facture');
        $this->renameColumnIfExists('parametres', 'invoice_footer_account_label', 'libelle_compte_pied_facture');
        $this->renameColumnIfExists('parametres', 'invoice_footer_contact_label', 'libelle_contact_pied_facture');
        $this->renameColumnIfExists('parametres', 'invoice_description_label', 'libelle_description_facture');
        $this->renameColumnIfExists('parametres', 'invoice_quantity_label', 'libelle_quantite_facture');
        $this->renameColumnIfExists('parametres', 'invoice_unit_price_label', 'libelle_prix_unitaire_facture');
        $this->renameColumnIfExists('parametres', 'invoice_amount_label', 'libelle_montant_facture');
        $this->renameColumnIfExists('parametres', 'invoice_show_signature', 'afficher_signature_facture');
        $this->renameColumnIfExists('parametres', 'invoice_footer_note', 'note_pied_facture');

        $this->renameColumnIfExists('parametres_ia', 'minimum_margin_threshold', 'seuil_marge_minimale');
        $this->renameColumnIfExists('parametres_ia', 'critical_stock_threshold', 'seuil_stock_critique');
        $this->renameColumnIfExists('parametres_ia', 'sensitivity_level', 'niveau_sensibilite');
        $this->renameColumnIfExists('parametres_ia', 'enable_behavior_analysis', 'activer_analyse_comportementale');
        $this->renameColumnIfExists('parametres_ia', 'enable_daily_audit', 'activer_audit_quotidien');
        $this->renameColumnIfExists('parametres_ia', 'openrouter_model', 'modele_openrouter');
        $this->renameColumnIfExists('parametres_ia', 'cache_duration_minutes', 'duree_cache_minutes');
        $this->renameColumnIfExists('parametres_ia', 'enable_auto_alerts', 'activer_alertes_automatiques');
        $this->renameColumnIfExists('parametres_ia', 'enable_auto_pdf', 'activer_pdf_automatique');
        $this->renameColumnIfExists('parametres_ia', 'enable_realtime_mode', 'activer_mode_temps_reel');

        $this->renameColumnIfExists('journaux_ia', 'user_id', 'utilisateur_id');
        $this->renameColumnIfExists('journaux_ia', 'log_type', 'type_journal');
        $this->renameColumnIfExists('journaux_ia', 'status', 'statut');
        $this->renameColumnIfExists('journaux_ia', 'input_signature', 'signature_entree');
        $this->renameColumnIfExists('journaux_ia', 'input_payload', 'charge_entree');
        $this->renameColumnIfExists('journaux_ia', 'output_payload', 'charge_sortie');
        $this->renameColumnIfExists('journaux_ia', 'error_message', 'message_erreur');
        $this->renameColumnIfExists('journaux_ia', 'generated_at', 'genere_le');

        $this->renameColumnIfExists('scores_performance', 'log_ia_id', 'journal_ia_id');
        $this->renameColumnIfExists('scores_performance', 'global_score', 'score_global');
        $this->renameColumnIfExists('scores_performance', 'score_breakdown', 'detail_score');
        $this->renameColumnIfExists('scores_performance', 'score_explanation', 'explication_score');

        $this->renameColumnIfExists('notifications_ia', 'log_ia_id', 'journal_ia_id');
        $this->renameColumnIfExists('notifications_ia', 'title', 'titre');
        $this->renameColumnIfExists('notifications_ia', 'severity', 'niveau_gravite');
        $this->renameColumnIfExists('notifications_ia', 'is_read', 'est_lue');
        $this->renameColumnIfExists('notifications_ia', 'notified_at', 'notifiee_le');

        $this->renameColumnIfExists('historiques_rapports_ia', 'log_ia_id', 'journal_ia_id');
        $this->renameColumnIfExists('historiques_rapports_ia', 'user_id', 'utilisateur_id');
        $this->renameColumnIfExists('historiques_rapports_ia', 'report_title', 'titre_rapport');
        $this->renameColumnIfExists('historiques_rapports_ia', 'generated_at', 'genere_le');

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    // Cette migration remet les anciens noms en anglais si un retour arriere est necessaire.
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        $this->renameColumnIfExists('roles', 'nom', 'name');
        $this->renameColumnIfExists('roles', 'libelle', 'label');

        $this->renameColumnIfExists('utilisateurs', 'nom', 'name');
        $this->renameColumnIfExists('utilisateurs', 'adresse_email', 'email');
        $this->renameColumnIfExists('utilisateurs', 'code_connexion', 'login_code');
        $this->renameColumnIfExists('utilisateurs', 'telephone', 'phone');
        $this->renameColumnIfExists('utilisateurs', 'chemin_photo_profil', 'profile_photo_path');
        $this->renameColumnIfExists('utilisateurs', 'mot_de_passe', 'password');
        $this->renameColumnIfExists('utilisateurs', 'est_actif', 'is_active');
        $this->renameColumnIfExists('utilisateurs', 'dernier_login_a', 'last_login_at');

        $this->renameColumnIfExists('categories', 'nom', 'name');
        $this->renameColumnIfExists('categories', 'est_active', 'is_active');

        $this->renameColumnIfExists('clients', 'nom', 'name');
        $this->renameColumnIfExists('clients', 'telephone', 'phone');
        $this->renameColumnIfExists('clients', 'adresse', 'address');
        $this->renameColumnIfExists('clients', 'entreprise', 'company');

        $this->renameColumnIfExists('produits', 'categorie_id', 'category_id');
        $this->renameColumnIfExists('produits', 'nom', 'name');
        $this->renameColumnIfExists('produits', 'prix_achat', 'purchase_price');
        $this->renameColumnIfExists('produits', 'prix_vente', 'sale_price');
        $this->renameColumnIfExists('produits', 'stock_theorique', 'theoretical_stock');
        $this->renameColumnIfExists('produits', 'stock_physique', 'physical_stock');
        $this->renameColumnIfExists('produits', 'stock_minimum', 'minimum_stock');
        $this->renameColumnIfExists('produits', 'chemin_photo', 'photo_path');
        $this->renameColumnIfExists('produits', 'est_actif', 'is_active');

        $this->renameColumnIfExists('factures', 'numero_facture', 'invoice_number');
        $this->renameColumnIfExists('factures', 'utilisateur_id', 'user_id');
        $this->renameColumnIfExists('factures', 'date_facture', 'invoice_date');
        $this->renameColumnIfExists('factures', 'date_echeance', 'due_date');
        $this->renameColumnIfExists('factures', 'taux_tva', 'tax_rate');
        $this->renameColumnIfExists('factures', 'montant_tva', 'tax_amount');
        $this->renameColumnIfExists('factures', 'montant_remise', 'discount_amount');
        $this->renameColumnIfExists('factures', 'statut', 'status');

        $this->renameColumnIfExists('details_facture', 'quantite', 'quantity');
        $this->renameColumnIfExists('details_facture', 'prix_unitaire_ht', 'unit_price_ht');
        $this->renameColumnIfExists('details_facture', 'total_ligne_ht', 'line_total_ht');

        $this->renameColumnIfExists('mouvements_stock', 'utilisateur_id', 'user_id');
        $this->renameColumnIfExists('mouvements_stock', 'type_mouvement', 'movement_type');
        $this->renameColumnIfExists('mouvements_stock', 'quantite', 'quantity');
        $this->renameColumnIfExists('mouvements_stock', 'motif', 'reason');
        $this->renameColumnIfExists('mouvements_stock', 'date_mouvement', 'movement_date');

        $this->renameColumnIfExists('activites_utilisateurs', 'utilisateur_id', 'user_id');
        $this->renameColumnIfExists('activites_utilisateurs', 'type_cible', 'target_type');
        $this->renameColumnIfExists('activites_utilisateurs', 'cible_id', 'target_id');
        $this->renameColumnIfExists('activites_utilisateurs', 'metadonnees', 'metadata');
        $this->renameColumnIfExists('activites_utilisateurs', 'adresse_ip', 'ip_address');
        $this->renameColumnIfExists('activites_utilisateurs', 'agent_utilisateur', 'user_agent');

        $this->renameColumnIfExists('parametres', 'nom_entreprise', 'company_name');
        $this->renameColumnIfExists('parametres', 'email_entreprise', 'company_email');
        $this->renameColumnIfExists('parametres', 'telephone_entreprise', 'company_phone');
        $this->renameColumnIfExists('parametres', 'adresse_entreprise', 'company_address');
        $this->renameColumnIfExists('parametres', 'chemin_logo', 'logo_path');
        $this->renameColumnIfExists('parametres', 'chemin_fond_facture', 'invoice_background_path');
        $this->renameColumnIfExists('parametres', 'devise', 'currency');
        $this->renameColumnIfExists('parametres', 'taux_tva_defaut', 'default_tax_rate');
        $this->renameColumnIfExists('parametres', 'format_numero_facture', 'invoice_number_format');
        $this->renameColumnIfExists('parametres', 'delai_echeance_facture_jours', 'invoice_due_days');
        $this->renameColumnIfExists('parametres', 'seuil_stock_minimum_global', 'global_minimum_stock');
        $this->renameColumnIfExists('parametres', 'nom_banque', 'bank_name');
        $this->renameColumnIfExists('parametres', 'nom_compte_bancaire', 'bank_account_name');
        $this->renameColumnIfExists('parametres', 'numero_compte_bancaire', 'bank_account_number');
        $this->renameColumnIfExists('parametres', 'code_swift', 'bank_swift');
        $this->renameColumnIfExists('parametres', 'conditions_facture', 'invoice_terms');
        $this->renameColumnIfExists('parametres', 'couleur_principale_facture', 'invoice_primary_color');
        $this->renameColumnIfExists('parametres', 'couleur_secondaire_facture', 'invoice_secondary_color');
        $this->renameColumnIfExists('parametres', 'titre_facture', 'invoice_title');
        $this->renameColumnIfExists('parametres', 'libelle_emetteur_facture', 'invoice_bill_from_label');
        $this->renameColumnIfExists('parametres', 'libelle_facturer_a', 'invoice_bill_to_label');
        $this->renameColumnIfExists('parametres', 'libelle_envoyer_a', 'invoice_ship_to_label');
        $this->renameColumnIfExists('parametres', 'libelle_paiement_facture', 'invoice_payment_label');
        $this->renameColumnIfExists('parametres', 'libelle_signature_facture', 'invoice_signature_label');
        $this->renameColumnIfExists('parametres', 'libelle_banque_pied_facture', 'invoice_footer_bank_label');
        $this->renameColumnIfExists('parametres', 'libelle_compte_pied_facture', 'invoice_footer_account_label');
        $this->renameColumnIfExists('parametres', 'libelle_contact_pied_facture', 'invoice_footer_contact_label');
        $this->renameColumnIfExists('parametres', 'libelle_description_facture', 'invoice_description_label');
        $this->renameColumnIfExists('parametres', 'libelle_quantite_facture', 'invoice_quantity_label');
        $this->renameColumnIfExists('parametres', 'libelle_prix_unitaire_facture', 'invoice_unit_price_label');
        $this->renameColumnIfExists('parametres', 'libelle_montant_facture', 'invoice_amount_label');
        $this->renameColumnIfExists('parametres', 'afficher_signature_facture', 'invoice_show_signature');
        $this->renameColumnIfExists('parametres', 'note_pied_facture', 'invoice_footer_note');

        $this->renameColumnIfExists('parametres_ia', 'seuil_marge_minimale', 'minimum_margin_threshold');
        $this->renameColumnIfExists('parametres_ia', 'seuil_stock_critique', 'critical_stock_threshold');
        $this->renameColumnIfExists('parametres_ia', 'niveau_sensibilite', 'sensitivity_level');
        $this->renameColumnIfExists('parametres_ia', 'activer_analyse_comportementale', 'enable_behavior_analysis');
        $this->renameColumnIfExists('parametres_ia', 'activer_audit_quotidien', 'enable_daily_audit');
        $this->renameColumnIfExists('parametres_ia', 'modele_openrouter', 'openrouter_model');
        $this->renameColumnIfExists('parametres_ia', 'duree_cache_minutes', 'cache_duration_minutes');
        $this->renameColumnIfExists('parametres_ia', 'activer_alertes_automatiques', 'enable_auto_alerts');
        $this->renameColumnIfExists('parametres_ia', 'activer_pdf_automatique', 'enable_auto_pdf');
        $this->renameColumnIfExists('parametres_ia', 'activer_mode_temps_reel', 'enable_realtime_mode');

        $this->renameColumnIfExists('journaux_ia', 'utilisateur_id', 'user_id');
        $this->renameColumnIfExists('journaux_ia', 'type_journal', 'log_type');
        $this->renameColumnIfExists('journaux_ia', 'statut', 'status');
        $this->renameColumnIfExists('journaux_ia', 'signature_entree', 'input_signature');
        $this->renameColumnIfExists('journaux_ia', 'charge_entree', 'input_payload');
        $this->renameColumnIfExists('journaux_ia', 'charge_sortie', 'output_payload');
        $this->renameColumnIfExists('journaux_ia', 'message_erreur', 'error_message');
        $this->renameColumnIfExists('journaux_ia', 'genere_le', 'generated_at');

        $this->renameColumnIfExists('scores_performance', 'journal_ia_id', 'log_ia_id');
        $this->renameColumnIfExists('scores_performance', 'score_global', 'global_score');
        $this->renameColumnIfExists('scores_performance', 'detail_score', 'score_breakdown');
        $this->renameColumnIfExists('scores_performance', 'explication_score', 'score_explanation');

        $this->renameColumnIfExists('notifications_ia', 'journal_ia_id', 'log_ia_id');
        $this->renameColumnIfExists('notifications_ia', 'titre', 'title');
        $this->renameColumnIfExists('notifications_ia', 'niveau_gravite', 'severity');
        $this->renameColumnIfExists('notifications_ia', 'est_lue', 'is_read');
        $this->renameColumnIfExists('notifications_ia', 'notifiee_le', 'notified_at');

        $this->renameColumnIfExists('historiques_rapports_ia', 'journal_ia_id', 'log_ia_id');
        $this->renameColumnIfExists('historiques_rapports_ia', 'utilisateur_id', 'user_id');
        $this->renameColumnIfExists('historiques_rapports_ia', 'titre_rapport', 'report_title');
        $this->renameColumnIfExists('historiques_rapports_ia', 'genere_le', 'generated_at');

        $this->renameTableIfExists('utilisateurs', 'users');
        $this->renameTableIfExists('details_facture', 'facture_details');
        $this->renameTableIfExists('journaux_ia', 'logs_ia');
        $this->renameTableIfExists('scores_performance', 'score_performance');

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    // Cette methode renomme une table seulement si elle existe encore.
    private function renameTableIfExists(string $from, string $to): void
    {
        if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE \"{$from}\" RENAME TO \"{$to}\"");
            } else {
                DB::statement("RENAME TABLE `{$from}` TO `{$to}`");
            }
        }
    }

    // Cette methode renomme une colonne seulement si l'ancien nom existe encore.
    private function renameColumnIfExists(string $table, string $from, string $to): void
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $from) && ! Schema::hasColumn($table, $to)) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE \"{$table}\" RENAME COLUMN \"{$from}\" TO \"{$to}\"");

                return;
            }

            $safeColumn = str_replace('`', '``', $from);
            $column = DB::selectOne("SHOW FULL COLUMNS FROM `{$table}` LIKE '{$safeColumn}'");

            if (! $column) {
                return;
            }

            $definition = "`{$to}` {$column->Type}";
            $definition .= $column->Null === 'YES' ? ' NULL' : ' NOT NULL';

            if ($column->Default !== null) {
                $upperDefault = strtoupper((string) $column->Default);
                $definition .= in_array($upperDefault, ['CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP()'], true)
                    ? " DEFAULT {$column->Default}"
                    : " DEFAULT " . DB::getPdo()->quote((string) $column->Default);
            } elseif ($column->Null === 'YES') {
                $definition .= ' DEFAULT NULL';
            }

            if (! empty($column->Extra)) {
                $definition .= " {$column->Extra}";
            }

            if (! empty($column->Comment)) {
                $definition .= ' COMMENT ' . DB::getPdo()->quote((string) $column->Comment);
            }

            DB::statement("ALTER TABLE `{$table}` CHANGE `{$from}` {$definition}");
        }
    }
};
