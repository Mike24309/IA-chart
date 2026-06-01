<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette methode cree la table des clients de l entreprise.
    public function up(): void
    {
        // Cette table stocke les clients utilisés dans les ventes et les factures.
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('company')->nullable();
            $table->timestamps();
        });
    }

    // Cette methode supprime la table des clients si la migration est annulee.
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
