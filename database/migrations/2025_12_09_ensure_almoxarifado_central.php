<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tabfant') || !Schema::hasTable('locais_projeto')) {
            return;
        }

        $cdProjeto = 999915;
        $projeto = DB::table('tabfant')->where('CDPROJETO', $cdProjeto)->first();

        if ($projeto) {
            $projetoId = $projeto->id ?? null;
            if (!$projetoId) {
                $projetoId = (int) ((DB::table('tabfant')->max('id') ?? 10000000) + 1);
                DB::table('tabfant')->where('CDPROJETO', $cdProjeto)->update(['id' => $projetoId]);
            }
        } else {
            $projetoId = (int) ((DB::table('tabfant')->max('id') ?? 10000000) + 1);
            $dadosProjeto = [
                'id' => $projetoId,
                'CDPROJETO' => $cdProjeto,
                'NOMEPROJETO' => 'ALMOXARIFADO CENTRAL',
                'LOCAL' => 'ALMOXARIFADO CENTRAL',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('tabfant', 'flativo')) {
                $dadosProjeto['flativo'] = true;
            }
            if (Schema::hasColumn('tabfant', 'UF')) {
                $dadosProjeto['UF'] = 'SC';
            }
            DB::table('tabfant')->insert($dadosProjeto);
        }

        $locais = [
            ['cdlocal' => '1642', 'delocal' => 'ALMOXARIFADO CENTRAL'],
            ['cdlocal' => '2002', 'delocal' => 'EM TRANSITO'],
        ];

        foreach ($locais as $local) {
            $existe = DB::table('locais_projeto')
                ->where('cdlocal', $local['cdlocal'])
                ->where('tabfant_id', $projetoId)
                ->first();

            if ($existe) {
                continue;
            }

            $dadosLocal = [
                'cdlocal' => $local['cdlocal'],
                'delocal' => $local['delocal'],
                'tabfant_id' => $projetoId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('locais_projeto', 'flativo')) {
                $dadosLocal['flativo'] = true;
            }

            DB::table('locais_projeto')->insert($dadosLocal);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('tabfant') || !Schema::hasTable('locais_projeto')) {
            return;
        }

        $projetoId = DB::table('tabfant')->where('CDPROJETO', 999915)->value('id');

        if ($projetoId) {
            DB::table('locais_projeto')
                ->where('tabfant_id', $projetoId)
                ->whereIn('cdlocal', ['1642', '2002'])
                ->delete();
        }

        DB::table('tabfant')->where('CDPROJETO', 999915)->delete();
    }
};
