<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MergeBeatrizToBea extends Command
{
    protected $signature = 'users:merge-beatriz-to-bea {--dry-run}';
    protected $description = 'Consolidar: BEATRIZ PATRICIA é principal, vincular BEATRIZ.SC nela (renomear para BEA.SC)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info("\n🔄 Consolidando: BEATRIZ PATRICIA + BEATRIZ.SC → BEA.SC...\n");

        // Encontrar BEATRIZ PATRICIA (completa)
        $beatrizMain = DB::table('usuario')
            ->where('NOMEUSER', 'BEATRIZ PATRICIA VIRISSIMO DOS SANTOS')
            ->first();
        
        if (!$beatrizMain) {
            $this->error("❌ Usuário 'BEATRIZ PATRICIA VIRISSIMO DOS SANTOS' não encontrado!");
            return 1;
        }

        $this->info("✅ Usuário principal encontrado: BEATRIZ PATRICIA VIRISSIMO DOS SANTOS");
        $this->info("   - CDMATRFUNCIONARIO: {$beatrizMain->CDMATRFUNCIONARIO}");
        $this->info("   - UF: {$beatrizMain->UF}\n");

        // Encontrar BEATRIZ.SC (com os lançamentos)
        $beatrizSc = DB::table('usuario')->where('NOMEUSER', 'BEATRIZ.SC')->first();
        if (!$beatrizSc) {
            $this->warn("⚠️ Usuário 'BEATRIZ.SC' não encontrado.");
            return 0;
        }

        $this->info("📋 Usuário a consolidar:");
        $this->line("  • BEATRIZ.SC (CDMATR: " . ($beatrizSc->CDMATRFUNCIONARIO ?? 'NULL') . ")\n");

        // Contar registros
        $this->info("📊 Analisando dados associados...\n");
        
        $patrCount = DB::table('patr')->where('USUARIO', 'BEATRIZ.SC')->count();
        $historicoCount = DB::table('movpartr')->where('USUARIO', 'BEATRIZ.SC')->count();
        $totalRecords = $patrCount + $historicoCount;

        $this->line("  Usuário: BEATRIZ.SC");
        $this->line("    - Patrimônios: $patrCount");
        $this->line("    - Histórico: $historicoCount");
        $this->line("    - TOTAL: $totalRecords registros\n");

        $this->warn("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->warn("🔄 OPERAÇÕES A REALIZAR:");
        $this->warn("   1. Vincular $patrCount patrimônios de BEATRIZ.SC → BEA.SC");
        $this->warn("   2. Vincular $historicoCount históricos de BEATRIZ.SC → BEA.SC");
        $this->warn("   3. Renomear BEATRIZ PATRICIA → BEA.SC");
        $this->warn("   4. Remover entrada BEATRIZ.SC");
        $this->warn("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->warn("📈 TOTAL DE REGISTROS A CONSOLIDAR: $totalRecords\n");

        if ($dryRun) {
            $this->info("🔄 (DRY RUN) Seriam feitas as seguintes consolidações:");
            $this->line("  • Patrimônios: BEATRIZ.SC → BEA.SC ($patrCount registros)");
            $this->line("  • Histórico: BEATRIZ.SC → BEA.SC ($historicoCount registros)");
            $this->line("  • Usuário: BEATRIZ PATRICIA VIRISSIMO DOS SANTOS → BEA.SC");
            $this->line("  • Deletar: BEATRIZ.SC\n");
            $this->info("✅ DRY RUN concluído. Use sem --dry-run para aplicar.");
            return 0;
        }

        // Confirmação
        if (!$this->confirm("\n⚠️  AVISO: Esta operação é IRREVERSÍVEL! Deseja prosseguir?")) {
            $this->info("❌ Operação cancelada.");
            return 1;
        }

        // Backup antes de fazer mudanças
        $this->info("\n💾 Criando backup dos dados...");
        $backup = [
            'beatriz_patricia' => $beatrizMain,
            'beatriz_sc' => $beatrizSc,
            'patr_beatriz_sc' => DB::table('patr')->where('USUARIO', 'BEATRIZ.SC')->get(),
            'movpartr_beatriz_sc' => DB::table('movpartr')->where('USUARIO', 'BEATRIZ.SC')->get(),
        ];

        $timestamp = Carbon::now()->format('Y-m-d_His');
        $backupPath = storage_path("backups/merge_beatriz_to_bea_backup_{$timestamp}.json");
        file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->info("   📄 Backup criado: storage/backups/merge_beatriz_to_bea_backup_{$timestamp}.json");

        // Consolidar dados
        $this->info("\n🔄 Iniciando consolidação...\n");

        // 1. Vincular patrimônios
        $patrUpdated = DB::table('patr')
            ->where('USUARIO', 'BEATRIZ.SC')
            ->update(['USUARIO' => 'BEA.SC']);
        $this->line("✅ $patrUpdated patrimônios vinculados para BEA.SC");

        // 2. Vincular histórico
        $historicoUpdated = DB::table('movpartr')
            ->where('USUARIO', 'BEATRIZ.SC')
            ->update(['USUARIO' => 'BEA.SC']);
        $this->line("✅ $historicoUpdated registros de histórico vinculados para BEA.SC");

        // 3. Renomear BEATRIZ PATRICIA para BEA.SC
        DB::table('usuario')
            ->where('NOMEUSER', 'BEATRIZ PATRICIA VIRISSIMO DOS SANTOS')
            ->update(['NOMEUSER' => 'BEA.SC']);
        $this->line("✅ Usuário renomeado: BEATRIZ PATRICIA VIRISSIMO DOS SANTOS → BEA.SC");

        // 4. Remover entrada duplicada BEATRIZ.SC
        DB::table('usuario')->where('NOMEUSER', 'BEATRIZ.SC')->delete();
        $this->line("✅ Entrada duplicada BEATRIZ.SC removida\n");

        Log::info("🔗 [MERGE-BEATRIZ-TO-BEA] Consolidação realizada - BEA.SC é agora principal (" . 
                  ($patrUpdated + $historicoUpdated) . " registros vinculados)");

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🎉 Consolidação concluída!");
        $this->info("📊 " . ($patrUpdated + $historicoUpdated) . " registros vinculados para BEA.SC");
        $this->info("👤 Usuária agora: BEA.SC (com todos os seus lançamentos)");
        $this->info("💾 Backup disponível em: storage/backups/");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");

        return 0;
    }
}
