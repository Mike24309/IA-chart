<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ces colonnes permettent de personnaliser le modèle de facture PDF depuis les paramètres admin.
        Schema::table('parametres', function (Blueprint $table) {
            $table->string('invoice_background_path')->nullable()->after('logo_path');
            $table->string('invoice_primary_color', 20)->default('#0f172a')->after('invoice_terms');
            $table->string('invoice_secondary_color', 20)->default('#e11d48')->after('invoice_primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('parametres', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_background_path',
                'invoice_primary_color',
                'invoice_secondary_color',
            ]);
        });
    }
};
