<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette migration aligne la vraie base locale sur les noms francais utilises par l'application.
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->renameTable('parametres_systeme', 'parametres');
        $this->renameTable('journal_activite', 'activites_utilisateurs');
        $this->renameTable('ai_logs', 'journaux_ia');
        $this->renameTable('ligne_factures', 'details_facture');

        if (Schema::hasTable('roles')) {
            $this->renameColumn('roles', 'nom', 'nom', 'varchar(50) NOT NULL');
            if (! Schema::hasColumn('roles', 'libelle')) {
                Schema::table('roles', function (Blueprint $table): void {
                    $table->string('libelle')->nullable()->after('nom');
                });
                DB::table('roles')->update(['libelle' => DB::raw('nom')]);
            }
        }

        if (Schema::hasTable('utilisateurs')) {
            $this->renameColumn('utilisateurs', 'photo', 'chemin_photo_profil', 'varchar(255) NULL');
            $this->renameColumn('utilisateurs', 'code_access', 'code_connexion', 'varchar(255) NULL');

            if (! Schema::hasColumn('utilisateurs', 'telephone')) {
                Schema::table('utilisateurs', function (Blueprint $table): void {
                    $table->string('telephone')->nullable()->after('code_connexion');
                });
            }
        }

        if (Schema::hasTable('clients')) {
            if (! Schema::hasColumn('clients', 'adresse')) {
                Schema::table('clients', function (Blueprint $table): void {
                    $table->text('adresse')->nullable()->after('telephone');
                });
            }

            if (! Schema::hasColumn('clients', 'entreprise')) {
                Schema::table('clients', function (Blueprint $table): void {
                    $table->string('entreprise')->nullable()->after('adresse');
                });
            }
        }

        if (Schema::hasTable('produits')) {
            $this->renameColumn('produits', 'code', 'reference', 'varchar(255) NOT NULL');
            $this->renameColumn('produits', 'stock_quantity', 'stock', 'int(11) NOT NULL DEFAULT 0');
            $this->renameColumn('produits', 'critical_threshold', 'stock_minimum', 'int(11) NOT NULL DEFAULT 5');

            if (! Schema::hasColumn('produits', 'chemin_photo')) {
                Schema::table('produits', function (Blueprint $table): void {
                    $table->string('chemin_photo')->nullable()->after('stock_minimum');
                });
            }
        }

        if (Schema::hasTable('factures')) {
            $this->renameColumn('factures', 'number', 'numero_facture', 'varchar(255) NOT NULL');
            $this->renameColumn('factures', 'subtotal', 'total_ht', 'decimal(12,2) NOT NULL DEFAULT 0.00');
            $this->renameColumn('factures', 'total', 'total_ttc', 'decimal(12,2) NOT NULL DEFAULT 0.00');

            if (! Schema::hasColumn('factures', 'date_echeance')) {
                Schema::table('factures', function (Blueprint $table): void {
                    $table->date('date_echeance')->nullable()->after('date_facture');
                });
            }

            if (! Schema::hasColumn('factures', 'montant_remise')) {
                Schema::table('factures', function (Blueprint $table): void {
                    $table->decimal('montant_remise', 12, 2)->default(0)->after('montant_tva');
                });
            }
        }

        if (Schema::hasTable('details_facture')) {
            $this->renameColumn('details_facture', 'quantity', 'quantite', 'int(11) NOT NULL');
            $this->renameColumn('details_facture', 'unit_price', 'prix_unitaire_ht', 'decimal(12,2) NOT NULL');
            $this->renameColumn('details_facture', 'line_total', 'total_ligne_ht', 'decimal(12,2) NOT NULL');

            if (! Schema::hasColumn('details_facture', 'description')) {
                Schema::table('details_facture', function (Blueprint $table): void {
                    $table->string('description')->default('')->after('produit_id');
                });
            }

            DB::statement("
                UPDATE details_facture df
                LEFT JOIN produits p ON p.id = df.produit_id
                SET df.description = COALESCE(NULLIF(df.description, ''), p.nom, '')
            ");
        }

        if (Schema::hasTable('mouvements_stock')) {
            $this->renameColumn('mouvements_stock', 'stock_before', 'stock_avant', 'int(11) NOT NULL');
            $this->renameColumn('mouvements_stock', 'stock_after', 'stock_apres', 'int(11) NOT NULL');

            if (! Schema::hasColumn('mouvements_stock', 'date_mouvement')) {
                Schema::table('mouvements_stock', function (Blueprint $table): void {
                    $table->date('date_mouvement')->nullable()->after('motif');
                });
                DB::table('mouvements_stock')->update(['date_mouvement' => DB::raw('DATE(created_at)')]);
            }

            DB::statement("
                UPDATE mouvements_stock
                SET type_mouvement = CASE
                    WHEN type_mouvement = 'entry' THEN 'entree'
                    WHEN type_mouvement = 'exit' THEN 'sortie'
                    WHEN type_mouvement = 'adjustment' THEN 'ajustement'
                    ELSE type_mouvement
                END
            ");

            DB::statement("
                ALTER TABLE mouvements_stock
                MODIFY type_mouvement ENUM('entree','sortie','ajustement') NOT NULL
            ");
        }

        if (Schema::hasTable('activites_utilisateurs')) {
            $this->renameColumn('activites_utilisateurs', 'user_id', 'utilisateur_id', 'bigint(20) unsigned NULL');
            $this->renameColumn('activites_utilisateurs', 'target_type', 'type_cible', 'varchar(255) NULL');
            $this->renameColumn('activites_utilisateurs', 'target_id', 'cible_id', 'bigint(20) unsigned NULL');
            $this->renameColumn('activites_utilisateurs', 'metadata', 'metadonnees', 'longtext NULL');
            $this->renameColumn('activites_utilisateurs', 'ip_address', 'adresse_ip', 'varchar(45) NULL');
            $this->renameColumn('activites_utilisateurs', 'user_agent', 'agent_utilisateur', 'varchar(255) NULL');
        }

        if (Schema::hasTable('parametres')) {
            $this->renameColumn('parametres', 'company_name', 'nom_entreprise', 'varchar(255) NOT NULL');
            $this->renameColumn('parametres', 'company_email', 'email_entreprise', 'varchar(255) NULL');
            $this->renameColumn('parametres', 'company_phone', 'telephone_entreprise', 'varchar(255) NULL');
            $this->renameColumn('parametres', 'company_address', 'adresse_entreprise', 'text NULL');
            $this->renameColumn('parametres', 'logo_path', 'chemin_logo', 'varchar(255) NULL');
            $this->renameColumn('parametres', 'invoice_background_path', 'chemin_fond_facture', 'varchar(255) NULL');
            $this->renameColumn('parametres', 'currency', 'devise', 'varchar(10) NOT NULL DEFAULT \'USD\'');
            $this->renameColumn('parametres', 'default_tax_rate', 'taux_tva_defaut', 'decimal(5,2) NOT NULL DEFAULT 18.00');
            $this->renameColumn('parametres', 'invoice_due_days', 'delai_echeance_facture_jours', 'int(11) NOT NULL DEFAULT 15');
            $this->renameColumn('parametres', 'global_critical_threshold', 'seuil_stock_minimum_global', 'int(11) NOT NULL DEFAULT 5');
            $this->renameColumn('parametres', 'bank_name', 'nom_banque', 'varchar(255) NULL');
            $this->renameColumn('parametres', 'bank_account_name', 'nom_compte_bancaire', 'varchar(255) NULL');
            $this->renameColumn('parametres', 'bank_account_number', 'numero_compte_bancaire', 'varchar(255) NULL');
            $this->renameColumn('parametres', 'bank_swift', 'code_swift', 'varchar(255) NULL');
            $this->renameColumn('parametres', 'invoice_terms', 'conditions_facture', 'text NULL');

            $this->addColumnIfMissing('parametres', 'format_numero_facture', fn (Blueprint $table) => $table->string('format_numero_facture')->default('FAC-{YEAR}-{SEQ}')->after('taux_tva_defaut'));
            $this->addColumnIfMissing('parametres', 'chemin_fond_facture', fn (Blueprint $table) => $table->string('chemin_fond_facture')->nullable()->after('chemin_logo'));
            $this->addColumnIfMissing('parametres', 'couleur_principale_facture', fn (Blueprint $table) => $table->string('couleur_principale_facture', 20)->default('#0f172a')->after('conditions_facture'));
            $this->addColumnIfMissing('parametres', 'couleur_secondaire_facture', fn (Blueprint $table) => $table->string('couleur_secondaire_facture', 20)->default('#e11d48')->after('couleur_principale_facture'));
            $this->addColumnIfMissing('parametres', 'titre_facture', fn (Blueprint $table) => $table->string('titre_facture')->default('FACTURE')->after('couleur_secondaire_facture'));
            $this->addColumnIfMissing('parametres', 'libelle_emetteur_facture', fn (Blueprint $table) => $table->string('libelle_emetteur_facture')->default('Emetteur')->after('titre_facture'));
            $this->addColumnIfMissing('parametres', 'libelle_facturer_a', fn (Blueprint $table) => $table->string('libelle_facturer_a')->default('Facturer a')->after('libelle_emetteur_facture'));
            $this->addColumnIfMissing('parametres', 'libelle_envoyer_a', fn (Blueprint $table) => $table->string('libelle_envoyer_a')->default('Envoyer a')->after('libelle_facturer_a'));
            $this->addColumnIfMissing('parametres', 'libelle_paiement_facture', fn (Blueprint $table) => $table->string('libelle_paiement_facture')->default('Conditions et modalites de paiement')->after('libelle_envoyer_a'));
            $this->addColumnIfMissing('parametres', 'libelle_signature_facture', fn (Blueprint $table) => $table->string('libelle_signature_facture')->default('Signature autorisee')->after('libelle_paiement_facture'));
            $this->addColumnIfMissing('parametres', 'libelle_banque_pied_facture', fn (Blueprint $table) => $table->string('libelle_banque_pied_facture')->default('Banque')->after('libelle_signature_facture'));
            $this->addColumnIfMissing('parametres', 'libelle_compte_pied_facture', fn (Blueprint $table) => $table->string('libelle_compte_pied_facture')->default('Compte')->after('libelle_banque_pied_facture'));
            $this->addColumnIfMissing('parametres', 'libelle_contact_pied_facture', fn (Blueprint $table) => $table->string('libelle_contact_pied_facture')->default('Contact')->after('libelle_compte_pied_facture'));
            $this->addColumnIfMissing('parametres', 'libelle_description_facture', fn (Blueprint $table) => $table->string('libelle_description_facture')->default('Description')->after('libelle_contact_pied_facture'));
            $this->addColumnIfMissing('parametres', 'libelle_quantite_facture', fn (Blueprint $table) => $table->string('libelle_quantite_facture')->default('Qte')->after('libelle_description_facture'));
            $this->addColumnIfMissing('parametres', 'libelle_prix_unitaire_facture', fn (Blueprint $table) => $table->string('libelle_prix_unitaire_facture')->default('Prix unitaire')->after('libelle_quantite_facture'));
            $this->addColumnIfMissing('parametres', 'libelle_montant_facture', fn (Blueprint $table) => $table->string('libelle_montant_facture')->default('Montant')->after('libelle_prix_unitaire_facture'));
            $this->addColumnIfMissing('parametres', 'afficher_signature_facture', fn (Blueprint $table) => $table->boolean('afficher_signature_facture')->default(true)->after('libelle_montant_facture'));
            $this->addColumnIfMissing('parametres', 'note_pied_facture', fn (Blueprint $table) => $table->string('note_pied_facture')->nullable()->after('afficher_signature_facture'));

            DB::table('parametres')->whereNull('format_numero_facture')->update(['format_numero_facture' => 'FAC-{YEAR}-{SEQ}']);
        }

        if (! Schema::hasTable('parametres_ia')) {
            Schema::create('parametres_ia', function (Blueprint $table): void {
                $table->id();
                $table->decimal('seuil_marge_minimale', 8, 2)->default(5);
                $table->integer('seuil_stock_critique')->default(5);
                $table->string('niveau_sensibilite', 20)->default('moyenne');
                $table->boolean('activer_analyse_comportementale')->default(true);
                $table->boolean('activer_audit_quotidien')->default(false);
                $table->string('modele_openrouter')->default('openai/gpt-5.2');
                $table->integer('duree_cache_minutes')->default(30);
                $table->boolean('activer_alertes_automatiques')->default(true);
                $table->boolean('activer_pdf_automatique')->default(false);
                $table->boolean('activer_mode_temps_reel')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('journaux_ia')) {
            $this->renameColumn('journaux_ia', 'user_id', 'utilisateur_id', 'bigint(20) unsigned NOT NULL');
            $this->renameColumn('journaux_ia', 'input', 'charge_entree', 'text NOT NULL');
            $this->renameColumn('journaux_ia', 'parsed_data', 'charge_sortie', 'longtext NOT NULL');
            $this->renameColumn('journaux_ia', 'success', 'succes', 'tinyint(1) NOT NULL DEFAULT 0');
            $this->renameColumn('journaux_ia', 'response', 'reponse', 'text NULL');

            $this->addColumnIfMissing('journaux_ia', 'type_journal', fn (Blueprint $table) => $table->string('type_journal', 30)->default('analysis')->after('utilisateur_id'));
            $this->addColumnIfMissing('journaux_ia', 'statut', fn (Blueprint $table) => $table->string('statut', 30)->default('success')->after('type_journal'));
            $this->addColumnIfMissing('journaux_ia', 'signature_entree', fn (Blueprint $table) => $table->string('signature_entree', 64)->nullable()->after('statut'));
            $this->addColumnIfMissing('journaux_ia', 'message_erreur', fn (Blueprint $table) => $table->text('message_erreur')->nullable()->after('reponse'));
            $this->addColumnIfMissing('journaux_ia', 'genere_le', fn (Blueprint $table) => $table->timestamp('genere_le')->nullable()->after('message_erreur'));

            DB::table('journaux_ia')->whereNull('genere_le')->update(['genere_le' => DB::raw('created_at')]);

            // Cette verification evite l erreur lors d un migrate:fresh si l ancienne colonne n existe plus.
            if (Schema::hasColumn('journaux_ia', 'succes')) {
                DB::table('journaux_ia')->where('succes', 1)->update(['statut' => 'success']);
                DB::table('journaux_ia')->where('succes', 0)->update(['statut' => 'failed']);
            }
        }

        if (! Schema::hasTable('notifications_ia')) {
            Schema::create('notifications_ia', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('journal_ia_id')->nullable();
                $table->string('titre');
                $table->text('message');
                $table->string('niveau_gravite', 20)->default('moyen');
                $table->boolean('est_lue')->default(false);
                $table->timestamp('notifiee_le')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('scores_performance')) {
            Schema::create('scores_performance', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('journal_ia_id')->nullable();
                $table->unsignedTinyInteger('score_global')->default(0);
                $table->json('detail_score')->nullable();
                $table->text('explication_score')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('historiques_rapports_ia')) {
            Schema::create('historiques_rapports_ia', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('journal_ia_id')->nullable();
                $table->foreignId('utilisateur_id')->nullable();
                $table->string('titre_rapport');
                $table->timestamp('genere_le')->nullable();
                $table->timestamps();
            });
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
    }

    private function renameTable(string $from, string $to): void
    {
        if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
            DB::statement("RENAME TABLE `{$from}` TO `{$to}`");
        }
    }

    private function renameColumn(string $table, string $from, string $to, string $definition): void
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $from) && ! Schema::hasColumn($table, $to)) {
            DB::statement("ALTER TABLE `{$table}` CHANGE `{$from}` `{$to}` {$definition}");
        }
    }

    private function addColumnIfMissing(string $table, string $column, \Closure $callback): void
    {
        if (Schema::hasTable($table) && ! Schema::hasColumn($table, $column)) {
            Schema::table($table, $callback);
        }
    }
};
