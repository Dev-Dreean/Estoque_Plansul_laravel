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
        // Limpar datas do campo MODELO (erro de importação de dados)
        // Se MODELO contém uma data (formato DD/MM/YYYY), remover
        DB::statement("
            UPDATE patr
            SET MODELO = NULL
            WHERE MODELO REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não fazer nada no rollback
    }
};
