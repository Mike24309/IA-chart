<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette migration ajoute un taux de conversion USD vers FC configurable dans les parametres.
    public function up(): void
    {
        Schema::table('parametres', function (Blueprint $table): void {
            if (! Schema::hasColumn('parametres', 'taux_usd_fc')) {
                $table->decimal('taux_usd_fc', 14, 4)->default(2500)->after('devise');
            }
        });
    }

    // Cette migration retire le taux si un retour arriere est demande.
    public function down(): void
    {
        Schema::table('parametres', function (Blueprint $table): void {
            if (Schema::hasColumn('parametres', 'taux_usd_fc')) {
                $table->dropColumn('taux_usd_fc');
            }
        });
    }
};
