<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('acessotela')) {
            return;
        }

        $data = [
            'NUSEQTELA' => 1009,
            'DETELA' => 'Removidos',
            'NMSISTEMA' => 'Sistema Principal',
            'FLACESSO' => 'S',
        ];

        if (Schema::hasColumn('acessotela', 'NIVEL_VISIBILIDADE')) {
            $data['NIVEL_VISIBILIDADE'] = 'TODOS';
        }

        DB::table('acessotela')->updateOrInsert(
            ['NUSEQTELA' => 1009],
            $data
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('acessotela')) {
            return;
        }

        DB::table('acessotela')->where('NUSEQTELA', 1009)->delete();
    }
};

