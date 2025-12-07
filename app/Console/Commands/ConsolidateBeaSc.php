<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConsolidateBeaSc extends Command
{
    protected $signature = 'users:consolidate-bea-sc {--dry-run}';
    protected $description = 'Consolidar: BEA.SC é principal, vincular BEATRIZ.SC nela (mantendo lançamentos)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info("\n🔄 Consolidando usuários: BEA.SC principal, BEATRIZ.SC → BEA.SC...\n");

        // Encontrar BEA.SC
        $beaSc = DB::table('usuario')->where('NOMEUSER', 'BEA.SC')->first();
        if (!$beaSc) {
            $this->error("❌ Usuário 'BEA.SC' não encontrado!");
            return 1;
        }

        $this->info("✅ Usuário principal encontrado: BEA.SC");
        $this->info("   - CDMATRFUNCIONARIO: {$beaSc->CDMATRFUNCIONARIO}");
        $this->info("   - NOMEUSER: {$beaSc->NOMEUSER}\n");

        // Encontrar BEATRIZ.SC
        $beatrizSc = DB::table('usuario')->where('NOMEUSER', 'BEATRIZ.SC')->first();
        if (!$beatrizSc) {
            $this->warn("⚠️ Usuário 'BEATRIZ.SC' não encontrado.");
            return 0;
        }

        $this->info("📋 Usuário a consolidar em BEA.SC:");
        $this->line("  • BEATRIZ.SC (CDMATR: {$beatrizSc->CDMATRFUNCIONARIO})\n");

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
        $this->warn("📈 TOTAL DE REGISTROS A VINCULAR: $totalRecords");
        $this->warn("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");

        if ($dryRun) {
            $this->info("🔄 (DRY RUN) Seriam feitas as seguintes consolidações:");
            $this->line("  • Vincular $patrCount patrimônios de BEATRIZ.SC → BEA.SC");
            $this->line("  • Vincular $historicoCount históricos de BEATRIZ.SC → BEA.SC");
            $this->line("  • Remover entrada duplicada: BEATRIZ.SC\n");
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
            'bea_sc' => $beaSc,
            'beatriz_sc' => $beatrizSc,
            'patr_beatriz' => DB::table('patr')->where('USUARIO', 'BEATRIZ.SC')->get(),
            'movpartr_beatriz' => DB::table('movpartr')->where('USUARIO', 'BEATRIZ.SC')->get(),
        ];

        $timestamp = Carbon::now()->format('Y-m-d_His');
        $backupPath = storage_path("backups/consolidate_bea_sc_backup_{$timestamp}.json");
        file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->info("   📄 Backup criado: storage/backups/consolidate_bea_sc_backup_{$timestamp}.json");

        // Consolidar dados
        $this->info("\n🔄 Iniciando consolidação...\n");

        // Vincular patrimônios
        $patrUpdated = DB::table('patr')
            ->where('USUARIO', 'BEATRIZ.SC')
            ->update(['USUARIO' => 'BEA.SC']);
        $this->line("✅ $patrUpdated patrimônios vinculados para BEA.SC");

        // Vincular histórico
        $historicoUpdated = DB::table('movpartr')
            ->where('USUARIO', 'BEATRIZ.SC')
            ->update(['USUARIO' => 'BEA.SC']);
        $this->line("✅ $historicoUpdated registros de histórico vinculados para BEA.SC");

        // Remover usuário duplicado
        DB::table('usuario')->where('NOMEUSER', 'BEATRIZ.SC')->delete();
        $this->line("✅ Usuário BEATRIZ.SC removido do sistema\n");

        Log::info("🔄 [CONSOLIDATE-BEA-SC] Consolidação realizada - BEATRIZ.SC → BEA.SC (" . 
                  ($patrUpdated + $historicoUpdated) . " registros vinculados)");

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🎉 Consolidação concluída!");
        $this->info("📊 " . ($patrUpdated + $historicoUpdated) . " registros vinculados para BEA.SC");
        $this->info("💾 Backup disponível em: storage/backups/");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");

        return 0;
    }
}
