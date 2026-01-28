<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando para marcar todos os patrimônios com situação BAIXA como verificados.
 * 
 * Este comando atualiza o campo FLCONFERIDO para 'S' em todos os registros
 * onde SITUACAO = 'BAIXA'.
 */
class MarcarBaixaComoVerificado extends Command
{
    protected $signature = 'patrimonios:marcar-baixa-verificado';
    protected $description = 'Marca todos os patrimônios com situação BAIXA como verificados';

    public function handle()
    {
        $this->info('🔍 Buscando patrimônios com situação BAIXA...');

        $total = DB::table('patr')
            ->whereRaw("UPPER(TRIM(SITUACAO)) = 'BAIXA'")
            ->count();

        if ($total === 0) {
            $this->warn('⚠️  Nenhum patrimônio com situação BAIXA encontrado.');
            return 0;
        }

        $this->info("📊 Encontrados {$total} patrimônios com situação BAIXA");

        if (!$this->confirm('Deseja marcar todos como verificados (FLCONFERIDO = S)?', true)) {
            $this->warn('❌ Operação cancelada.');
            return 0;
        }

        $updated = DB::table('patr')
            ->whereRaw("UPPER(TRIM(SITUACAO)) = 'BAIXA'")
            ->update(['FLCONFERIDO' => 'S']);

        $this->info("✅ {$updated} patrimônios marcados como verificados.");

        return 0;
    }
}
