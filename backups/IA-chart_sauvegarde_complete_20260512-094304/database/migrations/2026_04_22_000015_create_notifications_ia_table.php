<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette methode cree la table des alertes generees par l IA.
    public function up(): void
    {
        // Cette table enregistre les alertes générées par le module IA.
        Schema::create('notifications_ia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('log_ia_id')->nullable()->constrained('logs_ia')->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('severity', 20)->default('moyen');
            $table->boolean('is_read')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });
    }

    // Cette methode supprime la table des notifications IA en cas d annulation.
    public function down(): void
    {
        Schema::dropIfExists('notifications_ia');
    }
};
