<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $adminRole = Role::firstOrCreate(
            ['nom' => 'admin'],
            ['libelle' => 'Administrateur', 'description' => 'Acces total a l application.']
        );

        User::updateOrCreate(
            ['adresse_email' => 'admin@gestion.local'],
            [
                'role_id' => $adminRole->id,
                'nom' => 'Administrateur',
                'telephone' => '+243000000001',
                'mot_de_passe' => Hash::make('password'),
                'est_actif' => true,
                'code_connexion' => 'GA-ADMIN',
            ]
        );
    }

    public function down(): void
    {
    }
};
