<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdatePatrimoniosVerificadosCopa2 extends Command
{
    protected $signature = 'patrimonios:update-copa2';
    protected $description = 'Marcar patrimônios como verificados e mover para Copa 2 - Projeto 8';

    public function handle()
    {
        $patrimonios = [
            36104, 36103, 36102, 36101, 36107, 36108, 16105, 36106,
            14136, 14135, 32999, 32965, 32967, 32964, 32973, 32968,
            32966, 32963, 32971, 32970, 32962, 32972, 32969, 32961,
            32960, 32959, 22439, 36137, 999
        ];

        $this->info('=== Atualização de Patrimônios (Copa 2) ===');
        $this->info('Total de patrimônios: ' . count($patrimonios));
        $this->info('Ações:');
        $this->info('  - FLCONFERIDO = S (verificado)');
        $this->info('  - CDLOCAL = 2030 (Copa 2)');
        $this->info('  - CDPROJETO = 8 (SEDE)');

        // Buscar o local Copa 2 dinamicamente
        $local = DB::table('locais_projeto')
            ->whereRaw('LOWER(delocal) = ?', ['copa 2'])
            ->where('tabfant_id', 8)
            ->first();

        if (!$local) {
            $this->error('❌ Local "Copa 2" não encontrado no Projeto 8!');
            return 1;
        }

        $this->info("✅ Local encontrado: ID={$local->id}, cdlocal={$local->cdlocal}");

        // Atualizar patrimônios
        $updated = DB::table('patr')
            ->whereIn('NUPATRIMONIO', $patrimonios)
            ->update([
                'FLCONFERIDO' => 'S',
                'CDLOCAL' => $local->cdlocal,
                'CDPROJETO' => 8,
                'DTOPERACAO' => now(),
            ]);

        $this->info("✅ Atualizado: $updated registros");

        // Listar resultado
        $atualizados = DB::table('patr')
            ->whereIn('NUPATRIMONIO', $patrimonios)
            ->select('NUPATRIMONIO', 'DEPATRIMONIO', 'FLCONFERIDO', 'CDLOCAL', 'CDPROJETO')
            ->orderBy('NUPATRIMONIO')
            ->get();

        $this->line("\n=== Resultado ===");
        foreach ($atualizados as $p) {
            $this->line("#{$p->NUPATRIMONIO} - {$p->DEPATRIMONIO} | Conf: {$p->FLCONFERIDO} | Local: {$p->CDLOCAL}");
        }

        $this->info("\n✅ Comando concluído com sucesso!");
        return 0;
    }
}
