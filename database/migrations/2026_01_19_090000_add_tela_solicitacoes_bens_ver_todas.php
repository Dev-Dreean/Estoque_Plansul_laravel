<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TELA_CODIGO = 1011;

    public function up(): void
    {
        $exists = DB::table('acessotela')
            ->where('NUSEQTELA', self::TELA_CODIGO)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('acessotela')->insert([
            'NUSEQTELA' => self::TELA_CODIGO,
            'DETELA' => 'Solicitacoes de Bens - Ver todas',
            'NMSISTEMA' => 'Sistema Principal',
            'FLACESSO' => 'S',
        ]);
    }

    public function down(): void
    {
        DB::table('acessotela')
            ->where('NUSEQTELA', self::TELA_CODIGO)
            ->delete();
    }
};
