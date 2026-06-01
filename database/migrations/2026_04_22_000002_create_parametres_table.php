<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette methode cree la table de configuration generale de l application.
    public function up(): void
    {
        // Cette table contient la configuration générale de l'application et de l'entreprise.
        Schema::create('parametres', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->text('company_address')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('currency', 10)->default('USD');
            $table->decimal('default_tax_rate', 5, 2)->default(0);
            $table->string('invoice_number_format')->default('FAC-{YEAR}-{SEQ}');
            $table->integer('invoice_due_days')->default(15);
            $table->integer('global_minimum_stock')->default(5);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_swift')->nullable();
            $table->text('invoice_terms')->nullable();
            $table->timestamps();
        });
    }

    // Cette methode supprime la table des parametres en cas d annulation de migration.
    public function down(): void
    {
        Schema::dropIfExists('parametres');
    }
};
