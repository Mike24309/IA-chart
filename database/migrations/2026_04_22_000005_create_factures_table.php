<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette methode cree la table principale des factures avec les montants calcules et l etat du document.
    public function up(): void
    {
        // Cette table stocke les en-têtes de facture avec les montants globaux.
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('total_ht', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_ttc', 12, 2)->default(0);
            $table->enum('status', ['draft', 'validated', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    // Cette methode supprime la table si la migration est annulee.
    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
