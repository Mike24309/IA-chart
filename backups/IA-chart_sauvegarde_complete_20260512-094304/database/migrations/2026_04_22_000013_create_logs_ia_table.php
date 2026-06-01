<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette methode cree la table qui stocke les analyses et conversations du module IA.
    public function up(): void
    {
        // Cette table conserve les appels IA, les entrées résumées et les résultats structurés.
        Schema::create('logs_ia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('log_type', 30)->default('analysis');
            $table->string('status', 30)->default('success');
            $table->string('input_signature', 64)->nullable()->index();
            $table->json('input_payload')->nullable();
            $table->json('output_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    // Cette methode supprime la table des logs IA si la migration est annulee.
    public function down(): void
    {
        Schema::dropIfExists('logs_ia');
    }
};
