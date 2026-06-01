<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cette migration ajoute le postnom du client pour completer son identite.
    public function up(): void
    {
        if (! Schema::hasTable('clients') || Schema::hasColumn('clients', 'postnom')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table): void {
            $table->string('postnom')->default('')->after('nom');
        });

        DB::table('clients')->whereNull('postnom')->update(['postnom' => '']);
    }

    // Cette migration retire le postnom en cas de retour arriere.
    public function down(): void
    {
        if (! Schema::hasTable('clients') || ! Schema::hasColumn('clients', 'postnom')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn('postnom');
        });
    }
};
