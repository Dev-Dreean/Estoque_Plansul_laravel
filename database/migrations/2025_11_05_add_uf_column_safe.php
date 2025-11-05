<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adiciona coluna UF em tabfant se não existir
        if (Schema::hasTable('tabfant') && !Schema::hasColumn('tabfant', 'UF')) {
            Schema::table('tabfant', function (Blueprint $table) {
                $table->string('UF', 2)->nullable()->after('LOCAL');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove coluna UF
        if (Schema::hasTable('tabfant') && Schema::hasColumn('tabfant', 'UF')) {
            Schema::table('tabfant', function (Blueprint $table) {
                $table->dropColumn('UF');
            });
        }
    }
};
