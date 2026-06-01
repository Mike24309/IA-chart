<?php

namespace App\Services;

use App\Models\MouvementStock;
use App\Models\Produit;
use App\Models\User;
use InvalidArgumentException;

// Ce service applique et journalise les mouvements de stock.
class StockService
{
    // Cette methode applique un mouvement sur un stock unique puis journalise l operation.
    public function move(
        Produit $produit,
        User $user,
        string $type,
        int $quantity,
        string $reason,
        ?int $newStock = null
    ): MouvementStock {
        if ($quantity <= 0 && $type !== 'ajustement') {
            throw new InvalidArgumentException('La quantite doit etre positive.');
        }

        $beforeStock = (int) $produit->stock;

        if ($type === 'entree') {
            $afterStock = $beforeStock + $quantity;
        } elseif ($type === 'sortie') {
            $afterStock = $beforeStock - $quantity;
        } else {
            $afterStock = $newStock ?? max(0, $beforeStock + $quantity);
        }

        if ($afterStock < 0) {
            throw new InvalidArgumentException('Stock insuffisant pour cette operation.');
        }

        $produit->update([
            'stock' => $afterStock,
        ]);

        return MouvementStock::create([
            'produit_id' => $produit->id,
            'user_id' => $user->id,
            'movement_type' => $type,
            'quantity' => $quantity,
            'stock_avant' => $beforeStock,
            'stock_apres' => $afterStock,
            'reason' => $reason,
            'movement_date' => now()->toDateString(),
        ]);
    }
}
