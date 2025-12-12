<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Correção do local 1642:
     * - Estava vinculado ao projeto 999911 (CASA J. ATLAN.)
     * - Deve ser vinculado ao projeto 999915 (ALMOXARIFADO CENTRAL)
     * 
     * O patrimônio 17466 usa CDLOCAL=1642 e CDPROJETO=999915, mas o local
     * estava apontando para tabfant_id=999911 (projeto errado).
     */
    public function up(): void
    {
        // Atualizar o local 1642 para apontar para o projeto correto (999915)
        DB::table('locais_projeto')
            ->where('cdlocal', 1642)
            ->update(['tabfant_id' => 999915]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para o estado anterior (999911)
        DB::table('locais_projeto')
            ->where('cdlocal', 1642)
            ->update(['tabfant_id' => 999911]);
    }
};
