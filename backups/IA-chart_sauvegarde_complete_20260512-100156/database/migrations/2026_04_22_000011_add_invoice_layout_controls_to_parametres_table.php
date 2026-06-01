<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ces champs permettent de piloter les colonnes, la signature et le message final de la facture.
        Schema::table('parametres', function (Blueprint $table) {
            $table->string('invoice_description_label')->default('Description')->after('invoice_footer_contact_label');
            $table->string('invoice_quantity_label')->default('Qte')->after('invoice_description_label');
            $table->string('invoice_unit_price_label')->default('Prix unitaire')->after('invoice_quantity_label');
            $table->string('invoice_amount_label')->default('Montant')->after('invoice_unit_price_label');
            $table->boolean('invoice_show_signature')->default(true)->after('invoice_amount_label');
            $table->string('invoice_footer_note')->nullable()->after('invoice_show_signature');
        });
    }

    public function down(): void
    {
        Schema::table('parametres', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_description_label',
                'invoice_quantity_label',
                'invoice_unit_price_label',
                'invoice_amount_label',
                'invoice_show_signature',
                'invoice_footer_note',
            ]);
        });
    }
};
