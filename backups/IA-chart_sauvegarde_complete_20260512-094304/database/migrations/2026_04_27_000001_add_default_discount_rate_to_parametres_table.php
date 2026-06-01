<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette migration ajoute le pourcentage de remise par defaut utilise a la facturation.
    public function up(): void
    {
        if (! Schema::hasTable('parametres')) {
            return;
        }

        if (! Schema::hasColumn('parametres', 'pourcentage_remise_defaut')) {
            Schema::table('parametres', function (Blueprint $table): void {
                $table->decimal('pourcentage_remise_defaut', 5, 2)->default(0)->after('taux_tva_defaut');
            });
        }

        DB::table('parametres')
            ->whereNull('pourcentage_remise_defaut')
            ->update(['pourcentage_remise_defaut' => 0]);
    }

    // Cette migration retire le pourcentage de remise si un retour arriere est necessaire.
    public function down(): void
    {
        if (! Schema::hasTable('parametres') || ! Schema::hasColumn('parametres', 'pourcentage_remise_defaut')) {
            return;
        }

        Schema::table('parametres', function (Blueprint $table): void {
            $table->dropColumn('pourcentage_remise_defaut');
        });
    }
};
