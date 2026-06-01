<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette methode cree la table des lignes de facture liees a l entete de facture.
    public function up(): void
    {
        // Cette table stocke les lignes détaillées de chaque facture.
        Schema::create('facture_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained('factures')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('description');
            $table->integer('quantity');
            $table->decimal('unit_price_ht', 12, 2);
            $table->decimal('line_total_ht', 12, 2);
            $table->timestamps();
        });
    }

    // Cette methode supprime la table des lignes de facture en cas d annulation.
    public function down(): void
    {
        Schema::dropIfExists('facture_details');
    }
};
