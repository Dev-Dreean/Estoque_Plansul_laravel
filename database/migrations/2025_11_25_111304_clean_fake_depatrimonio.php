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
        // Limpar valores fake de DEPATRIMONIO que foram gerados automaticamente
        // Remover registros que tem "Patrimônio " seguido de número
        DB::statement("
            UPDATE patr
            SET DEPATRIMONIO = NULL
            WHERE DEPATRIMONIO LIKE 'Patrimônio %'
            AND DEPATRIMONIO REGEXP '^Patrimônio [0-9]+$'
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
