<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ces champs permettent de personnaliser les textes visibles du modèle de facture PDF.
        Schema::table('parametres', function (Blueprint $table) {
            $table->string('invoice_title')->default('FACTURE')->after('invoice_secondary_color');
            $table->string('invoice_bill_from_label')->default('Emetteur')->after('invoice_title');
            $table->string('invoice_bill_to_label')->default('Facturer a')->after('invoice_bill_from_label');
            $table->string('invoice_ship_to_label')->default('Envoyer a')->after('invoice_bill_to_label');
            $table->string('invoice_payment_label')->default('Conditions et modalites de paiement')->after('invoice_ship_to_label');
            $table->string('invoice_signature_label')->default('Signature autorisee')->after('invoice_payment_label');
            $table->string('invoice_footer_bank_label')->default('Banque')->after('invoice_signature_label');
            $table->string('invoice_footer_account_label')->default('Compte')->after('invoice_footer_bank_label');
            $table->string('invoice_footer_contact_label')->default('Contact')->after('invoice_footer_account_label');
        });
    }

    public function down(): void
    {
        Schema::table('parametres', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_title',
                'invoice_bill_from_label',
                'invoice_bill_to_label',
                'invoice_ship_to_label',
                'invoice_payment_label',
                'invoice_signature_label',
                'invoice_footer_bank_label',
                'invoice_footer_account_label',
                'invoice_footer_contact_label',
            ]);
        });
    }
};
