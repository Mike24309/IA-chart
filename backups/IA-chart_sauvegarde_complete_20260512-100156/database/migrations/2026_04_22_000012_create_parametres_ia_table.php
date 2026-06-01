<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette methode cree la table des parametres de fonctionnement du module IA.
    public function up(): void
    {
        // Cette table contient la configuration du module IA administrable par l'administrateur.
        Schema::create('parametres_ia', function (Blueprint $table) {
            $table->id();
            $table->decimal('minimum_margin_threshold', 8, 2)->default(5);
            $table->integer('critical_stock_threshold')->default(5);
            $table->string('sensitivity_level', 20)->default('moyenne');
            $table->boolean('enable_behavior_analysis')->default(true);
            $table->boolean('enable_daily_audit')->default(false);
            $table->string('openrouter_model')->default('openai/gpt-5.2');
            $table->integer('cache_duration_minutes')->default(30);
            $table->boolean('enable_auto_alerts')->default(true);
            $table->boolean('enable_auto_pdf')->default(false);
            $table->boolean('enable_realtime_mode')->default(true);
            $table->timestamps();
        });
    }

    // Cette methode supprime la table des parametres IA en cas de retour arriere.
    public function down(): void
    {
        Schema::dropIfExists('parametres_ia');
    }
};
