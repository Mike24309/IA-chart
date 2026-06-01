<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette methode cree la table d historique des mouvements de stock.
    public function up(): void
    {
        // Cette table journalise toutes les entrées, sorties et ajustements de stock.
        Schema::create('mouvements_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('movement_type', ['entree', 'sortie', 'ajustement']);
            $table->integer('quantity');
            $table->integer('stock_avant');
            $table->integer('stock_apres');
            $table->string('reason');
            $table->date('movement_date');
            $table->timestamps();
        });
    }

    // Cette methode supprime la table des mouvements de stock si besoin.
    public function down(): void
    {
        Schema::dropIfExists('mouvements_stock');
    }
};
