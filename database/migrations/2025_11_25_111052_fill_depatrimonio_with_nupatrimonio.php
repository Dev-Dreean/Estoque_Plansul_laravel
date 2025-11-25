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
        // Última tentativa: preencher DEPATRIMONIO com "Patrimônio " + NUPATRIMONIO para registros ainda vazios
        DB::statement("
            UPDATE patr
            SET DEPATRIMONIO = CONCAT('Patrimônio ', NUPATRIMONIO)
            WHERE (DEPATRIMONIO = '' OR DEPATRIMONIO IS NULL)
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
