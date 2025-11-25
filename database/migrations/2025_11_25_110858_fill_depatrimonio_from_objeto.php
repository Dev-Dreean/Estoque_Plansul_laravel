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
        // Preencher DEPATRIMONIO vazio com descrição do OBJETOPATR
        DB::statement("
            UPDATE patr p
            SET DEPATRIMONIO = (
                SELECT DEOBJETO FROM objetopatr o 
                WHERE o.NUSEQOBJETO = p.CODOBJETO 
                LIMIT 1
            )
            WHERE (DEPATRIMONIO = '' OR DEPATRIMONIO IS NULL)
            AND CODOBJETO IS NOT NULL
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
