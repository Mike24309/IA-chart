<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Cette migration recalcule le taux des factures FC deja enregistrees quand il manquait encore le vrai taux.
    public function up(): void
    {
        $rate = (float) (DB::table('parametres')->where('id', 1)->value('taux_usd_fc') ?? 2500);

        if ($rate <= 0) {
            $rate = 2500;
        }

        DB::table('factures')
            ->where('currency_code', 'FC')
            ->where('currency_rate', '<=', 1.01)
            ->update(['currency_rate' => $rate]);
    }

    public function down(): void
    {
        // Retour volontairement neutre: on ne veut pas effacer un taux historique valide.
    }
};
