<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixPatrimonioLocal2029 extends Command
{
    protected $signature = 'patrimonio:fix-local-2029 {--dry-run}';

    protected $description = 'Move patrimônios PLANSUL-MG para o novo escritório (local 2029)';

    public function handle()
    {
        $this->info('🔍 Movendo patrimônios para o novo escritório (local 2029)...\n');

        // Local 2029 = novo escritório, projeto 197 (PLANSUL-MG)
        $localCorreto = DB::table('locais_projeto')
            ->where('cdlocal', 2029)
            ->first();

        if (!$localCorreto) {
            $this->error('❌ Local 2029 não encontrado!');
            return 1;
        }

        $this->line("✅ Local alvo: ID={$localCorreto->id} | cdlocal={$localCorreto->cdlocal} | delocal={$localCorreto->delocal}");
        $this->line("   Projeto: {$localCorreto->tabfant_id} (PLANSUL-MG)\n");

        // Encontrar patrimônios que deveriam estar em 2029 mas estão em outro local
        $query1 = DB::table('patr')
            ->where('CDPROJETO', 197)
            ->where('CDLOCAL', '<>', 2029)
            ->select('NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'CDLOCAL', 'CDPROJETO');

        $query2 = DB::table('patr')
            ->where('CDLOCAL', 1895)
            ->where('CDPROJETO', '<>', 197)
            ->select('NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'CDLOCAL', 'CDPROJETO');

        $mismatch = collect($query1->get())
            ->merge($query2->get())
            ->unique('NUSEQPATR')
            ->sortBy('NUPATRIMONIO');

        $this->warn("\n⚠️  Patrimônios encontrados para mover: " . count($mismatch));
        
        if ($mismatch->isEmpty()) {
            $this->info("✅ Todos os patrimônios estão corretos!");
            return 0;
        }
        
        // Agrupar por local atual
        $porLocal = [];
        foreach ($mismatch as $p) {
            if (!isset($porLocal[$p->CDLOCAL])) {
                $porLocal[$p->CDLOCAL] = [];
            }
            $porLocal[$p->CDLOCAL][] = $p;
        }

        // Exibir resumo
        $this->line("\n📊 Resumo por local atual:");
        foreach ($porLocal as $local => $patrimonios) {
            $localInfo = DB::table('locais_projeto')->where('cdlocal', $local)->first();
            $this->line("   📍 Local {$local} ({$localInfo?->delocal}): " . count($patrimonios) . " patrimônios");
        }

        // Exibir amostra
        $this->line("\n📋 Amostra (primeiros 10):");
        foreach ($mismatch->take(10) as $p) {
            $this->line("   #{$p->NUPATRIMONIO} ({$p->NUSEQPATR}) - {$p->DEPATRIMONIO}");
            $this->line("      Mover de local {$p->CDLOCAL} para 2029");
        }
        
        if (count($mismatch) > 10) {
            $this->line("   ... e mais " . (count($mismatch) - 10) . " patrimônios");
        }

        if ($this->option('dry-run')) {
            $this->info("\n✅ Modo --dry-run: nenhuma alteração foi feita.");
            return 0;
        }

        // Criar backup
        $this->info("\n💾 Criando backup pré-correção...");
        $backup = $mismatch->toArray();
        $backupPath = storage_path('backups/patrimonio_mover_local2029_backup_' . now()->format('Y-m-d_His') . '.json');
        file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("✅ Backup salvo em: {$backupPath}");

        // Confirmar
        if (!$this->confirm("\n⚠️  Confirma mover esses " . count($mismatch) . " patrimônios para local 2029?")) {
            $this->error("❌ Operação cancelada");
            return 1;
        }

        // Executar movimentação
        $this->info("\n🔧 Movendo patrimônios...");
        
        try {
            DB::beginTransaction();
            
            // Mover patrimônios do projeto 197 que não estão em 2029
            DB::table('patr')
                ->where('CDPROJETO', 197)
                ->where('CDLOCAL', '<>', 2029)
                ->update([
                    'CDLOCAL' => 2029,
                    'DTOPERACAO' => now(),
                    'USUARIO' => 'system',
                ]);

            // Mover patrimônios que estão em local 1895 (ARAXA) com projeto errado
            DB::table('patr')
                ->where('CDLOCAL', 1895)
                ->where('CDPROJETO', '<>', 197)
                ->update([
                    'CDLOCAL' => 2029,
                    'CDPROJETO' => 197,
                    'DTOPERACAO' => now(),
                    'USUARIO' => 'system',
                ]);

            DB::commit();
            
            Log::info("✅ Patrimônios movidos para local 2029", [
                'total' => count($mismatch),
                'local_destino' => 2029,
                'projeto' => 197,
            ]);

            $this->info("✅ Movimentação concluída com sucesso!");
            $this->info("   Total de patrimônios movidos: " . count($mismatch));

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Erro ao mover: " . $e->getMessage());
            Log::error("Erro ao mover patrimônios para local 2029", ['erro' => $e->getMessage()]);
            return 1;
        }

        return 0;
    }
}
