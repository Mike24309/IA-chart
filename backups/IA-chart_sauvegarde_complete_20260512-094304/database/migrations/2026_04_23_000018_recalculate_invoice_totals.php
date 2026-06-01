<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Cette methode recalcule les montants des factures deja existantes selon la logique de TVA apres remise.
    public function up(): void
    {
        // Cette migration realigne les anciennes factures sur la regle actuelle :
        // sous-total HT, remise, TVA sur HT apres remise, puis total TTC.
        DB::table('factures')->orderBy('id')->get()->each(function ($invoice) {
            $totalHt = (float) DB::table('facture_details')
                ->where('facture_id', $invoice->id)
                ->sum('line_total_ht');

            $discount = round(min((float) $invoice->discount_amount, $totalHt), 2);
            $taxableAmount = round(max(0, $totalHt - $discount), 2);
            $taxAmount = round($taxableAmount * (((float) $invoice->tax_rate) / 100), 2);
            $totalTtc = round($taxableAmount + $taxAmount, 2);

            DB::table('factures')->where('id', $invoice->id)->update([
                'total_ht' => round($totalHt, 2),
                'discount_amount' => $discount,
                'tax_amount' => $taxAmount,
                'total_ttc' => $totalTtc,
                'updated_at' => now(),
            ]);
        });
    }

    // Cette methode ne restaure pas les anciennes valeurs car le recalcul corrige les donnees.
    public function down(): void
    {
        // Aucun retour arriere fiable : les anciens calculs n avaient pas une regle unique.
    }
};
