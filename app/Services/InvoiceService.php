<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\FactureDetail;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Ce service gere la creation des factures et la sortie de stock.
class InvoiceService
{
    public function __construct(
        private readonly ParameterService $parameterService,
        private readonly StockService $stockService,
        private readonly ActivityService $activityService
    ) {
    }

    // Cette methode cree la facture, ses lignes et diminue le stock dans une transaction unique.
    public function create(array $payload, User $user): Facture
    {
        return DB::transaction(function () use ($payload, $user): Facture {
            $settings = $this->parameterService->current();
            $taxRate = $this->money((float) ($settings->default_tax_rate ?? 0));
            $currencyCode = strtoupper((string) ($payload['currency_code'] ?? $settings->currency ?? 'USD'));
            $usdToFcRate = max(1, $this->money((float) ($settings->usd_to_fc_rate ?? 2500)));
            $invoiceRate = $currencyCode === 'FC' ? $usdToFcRate : 1.0;
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
                'currency_code' => $currencyCode,
                'currency_rate' => $invoiceRate,
                'notes' => $payload['notes'] ?? null,
            ]);

            foreach ($payload['items'] as $item) {
                // Ce verrou garantit que le stock reste correct meme si plusieurs ventes partent en meme temps.
                $produit = Produit::query()->lockForUpdate()->findOrFail($item['produit_id']);
                $quantity = (int) $item['quantity'];
                $customUnitPrice = array_key_exists('unit_price_ht', $item) && $item['unit_price_ht'] !== null && $item['unit_price_ht'] !== ''
                    ? $this->money((float) $item['unit_price_ht'])
                    : null;

                if (! $produit->is_active) {
                    throw new \InvalidArgumentException("Le produit {$produit->name} n est plus disponible a la vente.");
                }

                if ($quantity > (int) $produit->stock) {
                    throw new \InvalidArgumentException("Stock insuffisant pour {$produit->name}. Quantite disponible : {$produit->stock}.");
                }

                $baseUnitPrice = $this->money((float) $produit->sale_price);
                $unitPrice = $customUnitPrice !== null
                    ? $customUnitPrice
                    : $this->convertForInvoiceCurrency($baseUnitPrice, $currencyCode, $invoiceRate);
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
                    "Sortie liee a la facture {$number}"
                );
            }

            $totalHt = $this->money($totalHt);
            $taxAmount = $this->money($totalHt * ($taxRate / 100));
            $totalTtc = $this->money($totalHt + $taxAmount);

            $facture->update([
                'total_ht' => $totalHt,
                'discount_amount' => 0,
                'tax_amount' => $taxAmount,
                'total_ttc' => $totalTtc,
            ]);

            $this->activityService->log('creation_facture', $facture, ['invoice_number' => $number]);

            return $facture->load(['client', 'user', 'details.produit']);
        });
    }

    // Cette methode arrondit toujours les montants au format monetaire du projet.
    private function money(float $amount): float
    {
        return round($amount, 2);
    }

    // Cette methode convertit un prix de base USD vers la devise visible de la facture.
    private function convertForInvoiceCurrency(float $amount, string $currencyCode, float $invoiceRate): float
    {
        return $currencyCode === 'FC' ? $this->money($amount * $invoiceRate) : $this->money($amount);
    }
}
