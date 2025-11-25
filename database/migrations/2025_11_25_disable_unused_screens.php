<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Remove as telas que não devem aparecer na navegação:
     * - 1001 Dashboard (não solicitado)
     * - 1002 Projetos/Cadastro de Projetos (não solicitado)
     * - 1005 Atribuição de Patrimônio (não solicitado)
     * - 1006 Histórico (não solicitado)
     * - 1007 Relatórios (não solicitado)
     * 
     * Mantém apenas as telas solicitadas:
     * - 1000 Controle de Patrimônio
     * - 1003 Usuários
     * - 1004 Cadastro de Telas
     * - 1008 Gráficos (se adicionar depois)
     * - 1009 Cadastro de Locais (se adicionar depois)
     */
    public function up(): void
    {
        // Desativar telas que não devem aparecer na navegação
        DB::table('acessotela')
            ->whereIn('NUSEQTELA', [1001, 1002, 1005, 1006, 1007])
            ->update(['FLACESSO' => 'N']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reativar as telas desativadas
        DB::table('acessotela')
            ->whereIn('NUSEQTELA', [1001, 1002, 1005, 1006, 1007])
            ->update(['FLACESSO' => 'S']);
    }
};

