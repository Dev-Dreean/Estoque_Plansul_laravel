<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private const TELAS = [
        1016 => 'Historico de Solicitacoes',
        1017 => 'Solicitacoes - Gerenciar Visibilidade',
        1018 => 'Solicitacoes - Visualizacao Restrita',
    ];

    public function up(): void
    {
        foreach (self::TELAS as $codigo => $descricao) {
            DB::table('acessotela')->updateOrInsert(
                ['NUSEQTELA' => $codigo],
                [
                    'DETELA' => $descricao,
                    'NMSISTEMA' => 'Sistema Principal',
                    'FLACESSO' => 'S',
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('acessotela')
            ->whereIn('NUSEQTELA', array_keys(self::TELAS))
            ->delete();
    }
};
