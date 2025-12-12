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
        // Adicionar coluna UF na tabela patr (patrimonios)
        if (Schema::hasTable('patr') && !Schema::hasColumn('patr', 'UF')) {
            Schema::table('patr', function (Blueprint $table) {
                $table->string('UF', 2)->nullable()->after('CDPROJETO')->comment('Unidade Federativa (Estado) - vinculado ao projeto/local');
            });
        }

        // Adicionar coluna UF na tabela locais_projeto
        if (Schema::hasTable('locais_projeto') && !Schema::hasColumn('locais_projeto', 'UF')) {
            Schema::table('locais_projeto', function (Blueprint $table) {
                $table->string('UF', 2)->nullable()->after('tabfant_id')->comment('Unidade Federativa (Estado) - vinculado ao projeto');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('patr') && Schema::hasColumn('patr', 'UF')) {
            Schema::table('patr', function (Blueprint $table) {
                $table->dropColumn('UF');
            });
        }

        if (Schema::hasTable('locais_projeto') && Schema::hasColumn('locais_projeto', 'UF')) {
            Schema::table('locais_projeto', function (Blueprint $table) {
                $table->dropColumn('UF');
            });
        }
    }
};
