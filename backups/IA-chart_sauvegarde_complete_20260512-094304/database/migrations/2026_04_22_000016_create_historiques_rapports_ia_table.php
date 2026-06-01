<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette methode cree la table qui garde la trace des rapports IA telecharges.
    public function up(): void
    {
        // Cette table trace les rapports IA générés et téléchargés.
        Schema::create('historiques_rapports_ia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('log_ia_id')->nullable()->constrained('logs_ia')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('report_title');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    // Cette methode supprime l historique des rapports IA si la migration est annulee.
    public function down(): void
    {
        Schema::dropIfExists('historiques_rapports_ia');
    }
};
