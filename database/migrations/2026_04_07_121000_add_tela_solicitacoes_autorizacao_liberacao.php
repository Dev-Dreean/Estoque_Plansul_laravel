<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('acessotela')->where('NUSEQTELA', 1021)->exists();

        if (!$exists) {
            DB::table('acessotela')->insert([
                'NUSEQTELA' => 1021,
                'DETELA' => 'Solicitações - Autorização de Liberação',
                'NMSISTEMA' => 'Sistema Principal',
                'FLACESSO' => 'S',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('acessotela')->where('NUSEQTELA', 1021)->delete();
    }
};