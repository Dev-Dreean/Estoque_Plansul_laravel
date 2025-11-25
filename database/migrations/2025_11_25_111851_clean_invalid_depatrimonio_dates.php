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
        // Limpar datas do campo DEPATRIMONIO (erro de importação de dados)
        // Se DEPATRIMONIO contém apenas uma data (formato DD/MM/YYYY), remover
        DB::statement("
            UPDATE patr
            SET DEPATRIMONIO = NULL
            WHERE DEPATRIMONIO REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$'
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
