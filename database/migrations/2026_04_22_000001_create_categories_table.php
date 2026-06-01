<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette methode cree la table des categories de produits.
    public function up(): void
    {
        // Cette table stocke les catégories utilisées pour classer les produits.
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    // Cette methode supprime la table des categories en cas de retour arriere.
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
