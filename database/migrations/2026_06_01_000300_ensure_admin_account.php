<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $adminRoleId = DB::table('roles')->where('nom', 'admin')->value('id');

        if (! $adminRoleId) {
            $adminRoleId = DB::table('roles')->insertGetId([
                'nom' => 'admin',
                'libelle' => 'Administrateur',
                'description' => 'Acces total a l application.',
            ]);
        }

        DB::table('utilisateurs')->updateOrInsert(
            ['adresse_email' => 'admin@gestion.local'],
            [
                'role_id' => $adminRoleId,
                'nom' => 'Administrateur',
                'telephone' => '+243000000001',
                'mot_de_passe' => Hash::make('password'),
                'est_actif' => true,
                'code_connexion' => 'GA-ADMIN',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
    }
};
