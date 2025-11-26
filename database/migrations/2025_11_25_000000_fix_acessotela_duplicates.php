<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove duplicate tela entries and normalize names/codes.
     *
     * @return void
     */
    public function up(): void
    {
        $map = [
            'Controle de Patrimônio' => 1000,
            'Dashboard - Gráficos' => 1001,
            'Cadastro de Locais' => 1002,
            'Cadastro de Usuários' => 1003,
            'Cadastro de Telas' => 1004,
            'Gerenciar Acessos' => 1005,
            'Relatórios' => 1006,
            'Histórico de Movimentações' => 1007,
            'Configurações de Tema' => 1008,
        ];

        foreach ($map as $nome => $codigo) {
            // Remove registros que contenham o mesmo nome mas com código diferente
            DB::table('acessotela')
                ->whereRaw('LOWER(TRIM(DETELA)) LIKE ?', [mb_strtolower(trim($nome))])
                ->where('NUSEQTELA', '<>', $codigo)
                ->delete();

            // Normaliza o registro com o código esperado (se existir)
            DB::table('acessotela')
                ->where('NUSEQTELA', $codigo)
                ->update([
                    'DETELA' => $nome,
                    'FLACESSO' => 'S',
                    'NMSISTEMA' => 'Sistema Principal',
                ]);
        }

        // Caso exista algum registro 'Cadastro de Locais' sem código atribuído corretamente,
        // garantimos que apenas o código 1002 permaneça
        DB::table('acessotela')
            ->whereRaw('LOWER(TRIM(DETELA)) LIKE ?', [mb_strtolower('Cadastro de Locais')])
            ->where('NUSEQTELA', '<>', 1002)
            ->delete();
    }

    /**
     * Reverse the migrations.
     * No automatic rollback for data cleanup.
     *
     * @return void
     */
    public function down(): void
    {
        // Intencionalmente vazio: esta migration faz limpeza/normalização de dados
    }
};
