<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Preencher DEPATRIMONIO vazio com MODELO quando CODOBJETO estiver vazio
        DB::statement("
            UPDATE patr
            SET DEPATRIMONIO = TRIM(MODELO)
            WHERE (DEPATRIMONIO = '' OR DEPATRIMONIO IS NULL)
            AND MODELO IS NOT NULL 
            AND MODELO <> ''
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não fazer nada no rollback para evitar perder dados
    }
};
