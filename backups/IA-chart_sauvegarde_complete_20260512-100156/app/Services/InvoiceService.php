<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\FactureDetail;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Ce service gère la création des factures et la sortie de stock.
class InvoiceService
{
    public function __construct(
        private readonly ParameterService $parameterService,
        private readonly StockService $stockService,
        private readonly ActivityService $activityService
    ) {
    }

    public function create(array $payload, User $user): Facture
    {
        return DB::transaction(function () use ($payload, $user): Facture {
            $settings = $this->parameterService->current();
            $taxRate = $this->money((float) ($settings->default_tax_rate ?? 0));
            $sequence = Facture::count() + 1;
            $number = str_replace(
                ['{YEAR}', '{SEQ}'],
                [now()->format('Y'), str_pad((string) $sequence, 5, '0', STR_PAD_LEFT)],
                $settings->invoice_number_format
            );

            $totalHt = 0.0;

            $facture = Facture::create([
                'invoice_number' => $number,
                'client_id' => $payload['client_id'],
                'user_id' => $user->id,
                'invoice_date' => $payload['invoice_date'],
                'due_date' => Carbon::parse($payload['invoice_date'])->addDays($settings->invoice_due_days),
                'tax_rate' => $taxRate,
                'discount_amount' => 0,
                'status' => 'validated',
                'notes' => $payload['notes'] ?? null,
            ]);

            foreach ($payload['items'] as $item) {
                $produit = Produit::findOrFail($item['produit_id']);
                $quantity = (int) $item['quantity'];
                $unitPrice = $this->money((float) $produit->sale_price);
                $lineTotal = $this->money($unitPrice * $quantity);
                $totalHt += $lineTotal;

                FactureDetail::create([
                    'facture_id' => $facture->id,
                    'produit_id' => $produit->id,
                    'description' => $produit->name,
                    'quantity' => $quantity,
                    'unit_price_ht' => $unitPrice,
                    'line_total_ht' => $lineTotal,
                ]);

                $this->stockService->move(
                    $produit,
                    $user,
                    'sortie',
                    $quantity,
                    "Sortie liée à la facture {$number}"
                );
            }

            $totalHt = $this->money($totalHt);
            $discountAmount = 0.0;
            $taxableAmount = $totalHt;
            $taxAmount = $this->money($taxableAmount * ($taxRate / 100));
            $totalTtc = $this->money($taxableAmount + $taxAmount);

            $facture->update([
                'total_ht' => $totalHt,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_ttc' => $totalTtc,
            ]);

            $this->activityService->log('creation_facture', $facture, ['invoice_number' => $number]);

            return $facture->load(['client', 'user', 'details.produit']);
        });
    }

    private function money(float $amount): float
    {
        return round($amount, 2);
    }
}
