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
     * Remove completamente as telas que não devem existir no sistema.
     * Mantém apenas:
     * - 1000: Controle de Patrimônio (/patrimonios)
     * - 1002: Cadastro de Usuário (/usuarios)
     * - 1006: Cadastro de Telas (-)
     * 
     * Remove:
     * - 0: Teste3
     * - 1: Teste
     * - 2: Teste2
     * - 5: teste4
     * - 10: teste10
     * - 1001: Atribuição de Patrimônio (Dashboard)
     * - 1003: Cadastro de Projetos
     * - 1004: Relatórios
     * - 1005: Dashboard (Acessos)
     */
    public function up(): void
    {
        // Remover telas de teste
        DB::table('acessotela')->whereIn('NUSEQTELA', [0, 1, 2, 5, 10])->delete();

        // Remover telas que não devem aparecer
        DB::table('acessotela')->whereIn('NUSEQTELA', [1001, 1003, 1004, 1005])->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Esta migration é destrutiva. Se precisar reverter, restaurar do backup.
        // Não implementamos o down() pois seria necessário armazenar os dados deletados.
    }
};
