<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette methode cree la table qui journalise les actions des utilisateurs.
    public function up(): void
    {
        // Cette table garde la trace des actions sensibles réalisées par les utilisateurs.
        Schema::create('activites_utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->string('action');
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['action', 'created_at']);
        });
    }

    // Cette methode supprime le journal d activites si la migration est annulee.
    public function down(): void
    {
        Schema::dropIfExists('activites_utilisateurs');
    }
};
