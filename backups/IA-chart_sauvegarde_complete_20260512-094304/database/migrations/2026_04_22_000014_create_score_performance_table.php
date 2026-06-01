<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette methode cree la table des scores globaux calcules par l IA.
    public function up(): void
    {
        // Cette table historise les scores globaux produits par l'analyse IA.
        Schema::create('score_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('log_ia_id')->nullable()->constrained('logs_ia')->nullOnDelete();
            $table->unsignedTinyInteger('global_score')->default(0);
            $table->json('score_breakdown')->nullable();
            $table->text('score_explanation')->nullable();
            $table->timestamps();
        });
    }

    // Cette methode supprime la table des scores de performance si besoin.
    public function down(): void
    {
        Schema::dropIfExists('score_performance');
    }
};
