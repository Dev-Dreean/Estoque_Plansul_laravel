<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Patrimonio;
use App\Models\LocalProjeto;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FixPatrimonioProjectMismatch extends Command
{
    protected $signature = 'patrimonio:fix-project-mismatch {--dry-run}';
    protected $description = 'Corrige patrimônios com CDPROJETO inconsistente com o local onde estão alocados.';

    public function handle()
    {
        $this->newLine();
        $this->info('🚀 Iniciando correcão de inconsistências de projeto');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('⚠️  MODO DRY-RUN: Nenhuma alteração será realizada');
        }

        // 1. Encontrar patrimônios com CDPROJETO inconsistente
        $inconsistentes = DB::table('patr')
            ->join('locais_projeto', 'patr.CDLOCAL', '=', 'locais_projeto.cdlocal')
            ->join('tabfant', 'locais_projeto.tabfant_id', '=', 'tabfant.id')
            ->whereRaw('patr.CDPROJETO != tabfant.CDPROJETO')
            ->select(
                'patr.NUSEQPATR',
                'patr.NUPATRIMONIO',
                'patr.DEPATRIMONIO',
                'patr.CDPROJETO as projeto_atual',
                'patr.CDLOCAL',
                'locais_projeto.delocal',
                'tabfant.CDPROJETO as projeto_correto',
                'tabfant.NOMEPROJETO'
            )
            ->get();

        if ($inconsistentes->isEmpty()) {
            $this->info('✅ Nenhuma inconsistência encontrada!');
            return Command::SUCCESS;
        }

        $this->warn("⚠️  Encontradas " . $inconsistentes->count() . " inconsistências");
        $this->newLine();

        // 2. Exibir relatório
        $this->table(
            ['NUSEQPATR', 'NUPATRIMONIO', 'DESCRIÇÃO', 'PROJETO_ATUAL', 'LOCAL', 'PROJETO_CORRETO'],
            $inconsistentes->map(fn($row) => [
                $row->NUSEQPATR,
                $row->NUPATRIMONIO,
                substr($row->DEPATRIMONIO, 0, 30),
                $row->projeto_atual . ' - ???',
                substr($row->delocal, 0, 20),
                $row->projeto_correto . ' - ' . substr($row->NOMEPROJETO, 0, 20)
            ])->toArray()
        );

        $this->newLine();

        // 3. Backup antes de alterar
        if (!$dryRun) {
            $this->info('💾 Criando backup pré-correção...');
            $backupFile = storage_path('backups/patrimonio_mismatch_backup_' . now()->format('Y-m-d_His') . '.json');
            
            if (!is_dir(storage_path('backups'))) {
                mkdir(storage_path('backups'), 0755, true);
            }

            file_put_contents($backupFile, json_encode($inconsistentes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("✅ Backup salvo em: $backupFile");
            $this->newLine();
        }

        // 4. Confirmar ação
        if (!$dryRun && !$this->confirm('Confirma a correção desses ' . $inconsistentes->count() . ' patrimônios?')) {
            $this->info('❌ Operação cancelada');
            return Command::SUCCESS;
        }

        // 5. Corrigir patrimonios
        $corrigidos = 0;
        $erros = 0;

        foreach ($inconsistentes as $row) {
            try {
                if ($this->option('verbose')) {
                    $this->info("🔄 Corrigindo patrimônio {$row->NUPATRIMONIO}: {$row->projeto_atual} → {$row->projeto_correto}");
                }

                if (!$dryRun) {
                    DB::table('patr')
                        ->where('NUSEQPATR', $row->NUSEQPATR)
                        ->update([
                            'CDPROJETO' => $row->projeto_correto,
                            'DTOPERACAO' => now()->format('Y-m-d'),
                            'USUARIO' => 'SISTEMA'
                        ]);
                }

                $corrigidos++;
            } catch (\Exception $e) {
                $this->error("❌ Erro ao corrigir patrimônio {$row->NUPATRIMONIO}: " . $e->getMessage());
                $erros++;
            }
        }

        // 6. Log final
        $this->newLine();
        $this->info('📊 RELATÓRIO FINAL');
        $this->info("   ✅ Corrigidos: $corrigidos");
        if ($erros > 0) {
            $this->error("   ❌ Erros: $erros");
        }

        Log::info('🔧 Patrimônios com projeto inconsistente corrigidos', [
            'modo' => $dryRun ? 'dry-run' : 'execução',
            'total_inconsistentes' => $inconsistentes->count(),
            'corrigidos' => $corrigidos,
            'erros' => $erros,
            'timestamp' => now()
        ]);

        if ($dryRun) {
            $this->info("\n💡 Execute sem --dry-run para aplicar as mudanças");
        } else {
            $this->info("\n✅ Correção concluída com sucesso!");
        }

        $this->newLine();

        return $erros === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
