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
     * Correção do patrimônio 17466:
     * - CDPROJETO incorreto: 999915 (estava sincronizando do local erroneamente)
     * - CDPROJETO correto: 999915 (ALMOXARIFADO CENTRAL - já está correto!)
     * 
     * Nota: Esta migration documenta que o CDPROJETO 999915 está correto.
     * O problema era a lógica de "projeto_correto" no Model que sobrescrevia os dados.
     */
    public function up(): void
    {
        // Verificar se o CDPROJETO já está correto (999915)
        $patrimonio = DB::table('patr')
            ->where('NUPATRIMONIO', 17466)
            ->first();
        
        if ($patrimonio && $patrimonio->CDPROJETO != 999915) {
            DB::table('patr')
                ->where('NUPATRIMONIO', 17466)
                ->update(['CDPROJETO' => 999915]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não há reversão necessária - manter como está
    }
};
