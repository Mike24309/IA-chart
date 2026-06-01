<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette methode cree la table des produits avec les informations de prix et de stock.
    public function up(): void
    {
        // Cette table contient les produits commercialisés et suivis en stock.
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete()->cascadeOnUpdate();
            $table->string('name');
            $table->string('reference')->unique();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->string('photo_path')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['name', 'reference']);
        });
    }

    // Cette methode supprime la table des produits en cas de retour arriere.
    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
