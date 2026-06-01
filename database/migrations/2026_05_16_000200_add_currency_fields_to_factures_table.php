<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette migration ajoute la devise visible de la facture sans casser les montants internes du dashboard.
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table): void {
            if (! Schema::hasColumn('factures', 'currency_code')) {
                $table->string('currency_code', 10)->default('USD');
            }

            if (! Schema::hasColumn('factures', 'currency_rate')) {
                $table->decimal('currency_rate', 14, 4)->default(1);
            }
        });
    }

    // Cette migration retire les informations de devise si un retour arriere est demande.
    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table): void {
            if (Schema::hasColumn('factures', 'currency_rate')) {
                $table->dropColumn('currency_rate');
            }

            if (Schema::hasColumn('factures', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
        });
    }
};
