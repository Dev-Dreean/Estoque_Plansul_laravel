<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TELA_CODIGO = 1019;
    private const TELA_NOME = 'Solicitações - Triagem Inicial';

    public function up(): void
    {
        DB::table('acessotela')->updateOrInsert(
            ['NUSEQTELA' => self::TELA_CODIGO],
            [
                'DETELA' => self::TELA_NOME,
                'NMSISTEMA' => 'Sistema Principal',
                'FLACESSO' => 'S',
            ]
        );
    }

    public function down(): void
    {
        DB::table('acessotela')
            ->where('NUSEQTELA', self::TELA_CODIGO)
            ->delete();
    }
};
