<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Client;
use App\Models\Parametre;
use App\Models\Produit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['label' => 'Administrateur', 'description' => 'Accès total à l’application.']
        );

        $employeeRole = Role::firstOrCreate(
            ['name' => 'employee'],
            ['label' => 'Employé', 'description' => 'Accès opérationnel limité.']
        );

        User::firstOrCreate(
            ['email' => 'admin@gestion.local'],
            [
                'role_id' => $adminRole->id,
                'name' => 'Administrateur',
                'phone' => '+243000000001',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'employe@gestion.local'],
            [
                'role_id' => $employeeRole->id,
                'name' => 'Employé Démo',
                'phone' => '+243000000002',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        Parametre::firstOrCreate(
            ['id' => 1],
            [
                'company_name' => 'DEV IA',
                'company_email' => 'contact@gestion.local',
                'company_phone' => '+243000000100',
                'company_address' => 'Kinshasa - RDC',
                'currency' => 'USD',
                'default_tax_rate' => 16,
                'invoice_number_format' => 'FAC-{YEAR}-{SEQ}',
                'invoice_due_days' => 15,
                'global_minimum_stock' => 5,
                'bank_name' => 'Banque Démo',
                'bank_account_name' => 'DEV IA',
                'bank_account_number' => '000-111-222',
                'bank_swift' => 'DEMOXXX',
                'invoice_terms' => 'Paiement exigible à l’échéance indiquée sur la facture.',
                'invoice_primary_color' => '#0f172a',
                'invoice_secondary_color' => '#e11d48',
                'invoice_title' => 'FACTURE',
                'invoice_bill_from_label' => 'Emetteur',
                'invoice_bill_to_label' => 'Facturer a',
                'invoice_ship_to_label' => 'Envoyer a',
                'invoice_payment_label' => 'Conditions et modalites de paiement',
                'invoice_signature_label' => 'Signature autorisee',
                'invoice_footer_bank_label' => 'Banque',
                'invoice_footer_account_label' => 'Compte',
                'invoice_footer_contact_label' => 'Contact',
                'invoice_description_label' => 'Description',
                'invoice_quantity_label' => 'Qte',
                'invoice_unit_price_label' => 'Prix unitaire',
                'invoice_amount_label' => 'Montant',
                'invoice_show_signature' => true,
                'invoice_footer_note' => 'Merci pour votre confiance. Document genere automatiquement depuis DEV IA.',
            ]
        );

        $category = Category::firstOrCreate(
            ['name' => 'Bureautique'],
            ['description' => 'Équipements et fournitures de bureau', 'is_active' => true]
        );

        Produit::firstOrCreate(
            ['reference' => 'PRD-001'],
            [
                'category_id' => $category->id,
                'name' => 'Imprimante Laser',
                'purchase_price' => 120,
                'sale_price' => 180,
                'stock' => 10,
                'minimum_stock' => 3,
                'description' => 'Produit de démonstration',
                'is_active' => true,
            ]
        );

        Client::firstOrCreate(
            ['email' => 'client.demo@example.com'],
            [
                'name' => 'Client Démonstration',
                'phone' => '+243000000300',
                'address' => 'Gombe, Kinshasa',
                'company' => 'Entreprise Demo',
            ]
        );
    }
}
