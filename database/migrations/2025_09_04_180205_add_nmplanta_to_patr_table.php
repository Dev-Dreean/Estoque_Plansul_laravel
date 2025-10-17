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
        // Algumas versões do MySQL/Information Schema em hosts compartilhados
        // não possuem a coluna 'generation_expression' e causam erro ao usar
        // Schema::hasColumn(). Para evitar isso, tentamos executar o
        // alter table dentro de um try/catch e ignoramos erro de coluna
        // duplicada.
        try {
            Schema::table('patr', function (Blueprint $table) {
                $table->integer('NMPLANTA')->nullable()->after('CDPROJETO');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Código SQLSTATE 42S21 significa 'Column already exists' em MySQL
            // Identificamos o caso e apenas ignoramos para não travar o deploy.
            $sqlState = $e->getCode();
            if ($sqlState != '42S21') {
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patr', function (Blueprint $table) {
            // Remove a coluna caso precisemos reverter a migration
            $table->dropColumn('NMPLANTA');
        });
    }
};