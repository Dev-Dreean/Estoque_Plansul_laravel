<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Reverte a tela 1000-3 para 1008 para manter sequência simples
     */
    public function up(): void
    {
        // Reverter Gráficos de 1000-3 para 1008
        DB::table('acessotela')
            ->where('NUSEQTELA', '1000-3')
            ->update([
                'NUSEQTELA' => '1008',
                'NUSEQTELA_PAI' => null,
                'DETELA' => 'Gráficos'
            ]);

        // Reverter Histórico de 1000-2 para 1007
        DB::table('acessotela')
            ->where('NUSEQTELA', '1000-2')
            ->update([
                'NUSEQTELA' => '1007',
                'NUSEQTELA_PAI' => null,
                'DETELA' => 'Histórico de Movimentações'
            ]);

        // Remover Patrimônios 1000-1 (não existe no original)
        DB::table('acessotela')->where('NUSEQTELA', '1000-1')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não reverter
    }
};
