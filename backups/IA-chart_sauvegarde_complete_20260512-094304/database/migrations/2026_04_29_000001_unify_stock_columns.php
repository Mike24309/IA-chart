<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette migration remplace le double stock par un stock unique dans la base active.
    public function up(): void
    {
        if (Schema::hasTable('produits')) {
            if (Schema::hasColumn('produits', 'stock_theorique') && ! Schema::hasColumn('produits', 'stock')) {
                DB::statement('ALTER TABLE `produits` CHANGE `stock_theorique` `stock` INT NOT NULL DEFAULT 0');
            }

            if (Schema::hasColumn('produits', 'theoretical_stock') && ! Schema::hasColumn('produits', 'stock')) {
                DB::statement('ALTER TABLE `produits` CHANGE `theoretical_stock` `stock` INT NOT NULL DEFAULT 0');
            }

            if (Schema::hasColumn('produits', 'stock_physique')) {
                Schema::table('produits', function (Blueprint $table): void {
                    $table->dropColumn('stock_physique');
                });
            }

            if (Schema::hasColumn('produits', 'physical_stock')) {
                Schema::table('produits', function (Blueprint $table): void {
                    $table->dropColumn('physical_stock');
                });
            }
        }

        if (Schema::hasTable('mouvements_stock')) {
            if (Schema::hasColumn('mouvements_stock', 'stock_theorique_avant') && ! Schema::hasColumn('mouvements_stock', 'stock_avant')) {
                DB::statement('ALTER TABLE `mouvements_stock` CHANGE `stock_theorique_avant` `stock_avant` INT NOT NULL');
            }

            if (Schema::hasColumn('mouvements_stock', 'stock_theorique_apres') && ! Schema::hasColumn('mouvements_stock', 'stock_apres')) {
                DB::statement('ALTER TABLE `mouvements_stock` CHANGE `stock_theorique_apres` `stock_apres` INT NOT NULL');
            }

            if (Schema::hasColumn('mouvements_stock', 'stock_physique_avant')) {
                Schema::table('mouvements_stock', function (Blueprint $table): void {
                    $table->dropColumn('stock_physique_avant');
                });
            }

            if (Schema::hasColumn('mouvements_stock', 'stock_physique_apres')) {
                Schema::table('mouvements_stock', function (Blueprint $table): void {
                    $table->dropColumn('stock_physique_apres');
                });
            }
        }
    }

    // Cette methode restaure l ancien schema si un retour arriere est necessaire.
    public function down(): void
    {
        if (Schema::hasTable('produits')) {
            if (Schema::hasColumn('produits', 'stock') && ! Schema::hasColumn('produits', 'stock_theorique')) {
                DB::statement('ALTER TABLE `produits` CHANGE `stock` `stock_theorique` INT NOT NULL DEFAULT 0');
            }

            if (! Schema::hasColumn('produits', 'stock_physique')) {
                Schema::table('produits', function (Blueprint $table): void {
                    $table->integer('stock_physique')->default(0)->after('stock_theorique');
                });
            }

            DB::table('produits')->update(['stock_physique' => DB::raw('stock_theorique')]);
        }

        if (Schema::hasTable('mouvements_stock')) {
            if (Schema::hasColumn('mouvements_stock', 'stock_avant') && ! Schema::hasColumn('mouvements_stock', 'stock_theorique_avant')) {
                DB::statement('ALTER TABLE `mouvements_stock` CHANGE `stock_avant` `stock_theorique_avant` INT NOT NULL');
            }

            if (Schema::hasColumn('mouvements_stock', 'stock_apres') && ! Schema::hasColumn('mouvements_stock', 'stock_theorique_apres')) {
                DB::statement('ALTER TABLE `mouvements_stock` CHANGE `stock_apres` `stock_theorique_apres` INT NOT NULL');
            }

            if (! Schema::hasColumn('mouvements_stock', 'stock_physique_avant')) {
                Schema::table('mouvements_stock', function (Blueprint $table): void {
                    $table->integer('stock_physique_avant')->default(0)->after('stock_theorique_apres');
                    $table->integer('stock_physique_apres')->default(0)->after('stock_physique_avant');
                });
            }

            DB::table('mouvements_stock')->update([
                'stock_physique_avant' => DB::raw('stock_theorique_avant'),
                'stock_physique_apres' => DB::raw('stock_theorique_apres'),
            ]);
        }
    }
};
