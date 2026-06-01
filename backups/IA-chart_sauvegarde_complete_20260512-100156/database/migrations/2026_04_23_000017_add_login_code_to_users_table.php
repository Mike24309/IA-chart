<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    // Cette methode ajoute un code de connexion alternatif dans la table des utilisateurs.
    public function up(): void
    {
        // Cette migration ajoute un code de connexion unique pour chaque utilisateur.
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_code')->nullable()->unique()->after('email');
        });

        DB::table('users')->whereNull('login_code')->orderBy('id')->get()->each(function ($user) {
            do {
                $code = 'USR-' . strtoupper(Str::random(6));
            } while (DB::table('users')->where('login_code', $code)->exists());

            DB::table('users')->where('id', $user->id)->update(['login_code' => $code]);
        });
    }

    // Cette methode retire le code de connexion si cette evolution est annulee.
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['login_code']);
            $table->dropColumn('login_code');
        });
    }
};
