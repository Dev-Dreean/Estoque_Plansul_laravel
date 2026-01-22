<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TELAS = [
        1014 => 'Solicitacoes de Bens - Aprovar',
        1015 => 'Solicitacoes de Bens - Cancelar',
    ];

    public function up(): void
    {
        foreach (self::TELAS as $codigo => $nome) {
            $exists = DB::table('acessotela')
                ->where('NUSEQTELA', $codigo)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('acessotela')->insert([
                'NUSEQTELA' => $codigo,
                'DETELA' => $nome,
                'NMSISTEMA' => 'Sistema Principal',
                'FLACESSO' => 'S',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('acessotela')
            ->whereIn('NUSEQTELA', array_keys(self::TELAS))
            ->delete();
    }
};
