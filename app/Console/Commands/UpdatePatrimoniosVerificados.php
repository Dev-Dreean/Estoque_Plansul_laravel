<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdatePatrimoniosVerificados extends Command
{
    protected $signature = 'patrimonios:update-verificados';
    protected $description = 'Marcar patrimônios como verificados e mover para Copa 1 - Projeto 8';

    public function handle()
    {
        $patrimonios = [
            22506, 22486, 22487, 22488, 22489, 22490, 22491, 22492, 22493, 22494,
            22495, 22496, 22497, 22498, 22499, 22500, 22501, 22502, 22503, 22504,
            22505, 5555, 383, 384, 397, 22443, 14102, 14097, 14100, 36138
        ];

        $this->info('=== Atualização de Patrimônios ===');
        $this->info('Total de patrimônios: ' . count($patrimonios));
        $this->info('Ações:');
        $this->info('  - FLCONFERIDO = S (verificado)');
        $this->info('  - CDLOCAL = 1965 (Copa 1)');
        $this->info('  - CDPROJETO = 8 (SEDE)');

        // Buscar o local Copa 1 dinamicamente
        $local = DB::table('locais_projeto')
            ->whereRaw('LOWER(delocal) = ?', ['copa 1'])
            ->where('tabfant_id', 8)
            ->first();

        if (!$local) {
            $this->error('❌ Local "Copa 1" não encontrado no Projeto 8!');
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
