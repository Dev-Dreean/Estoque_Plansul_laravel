<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RemoveDuplicateUser extends Command
{
    protected $signature = 'users:remove-duplicate {username} {--dry-run}';
    protected $description = 'Remover usuário duplicado ou incompleto do sistema';

    public function handle()
    {
        $username = $this->argument('username');
        $dryRun = $this->option('dry-run');

        $this->info("\n🔄 Removendo usuário duplicado...\n");

        // Encontrar usuário a ser removido
        $userToRemove = DB::table('usuario')->where('NOMEUSER', $username)->first();
        if (!$userToRemove) {
            $this->error("❌ Usuário '$username' não encontrado!");
            return 1;
        }

        $this->info("❌ Usuário a remover: $username");
        $this->info("   - CDMATRFUNCIONARIO: " . ($userToRemove->CDMATRFUNCIONARIO ?? 'NULL'));
        $this->info("   - UF: " . ($userToRemove->UF ?? 'NULL') . "\n");

        // Contar registros associados
        $patrCount = DB::table('patr')->where('USUARIO', $username)->count();
        $historicoCount = DB::table('movpartr')->where('USUARIO', $username)->count();
        $totalRecords = $patrCount + $historicoCount;

        $this->info("📊 Analisando dados associados...\n");
        $this->line("  - Patrimônios: $patrCount");
        $this->line("  - Histórico: $historicoCount");
        $this->line("  - TOTAL: $totalRecords registros\n");

        $this->warn("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->warn("📈 TOTAL DE REGISTROS A REMOVER: $totalRecords");
        $this->warn("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");

        if ($dryRun) {
            $this->info("🔄 (DRY RUN) Seriam removidos:");
            $this->line("  • Usuário: $username");
            $this->line("  • $patrCount patrimônios");
            $this->line("  • $historicoCount registros de histórico\n");
            $this->info("✅ DRY RUN concluído. Use sem --dry-run para aplicar.");
            return 0;
        }

        // Confirmação
        if (!$this->confirm("\n⚠️  AVISO: Esta operação é IRREVERSÍVEL! Deseja prosseguir?")) {
            $this->info("❌ Operação cancelada.");
            return 1;
        }

        // Backup antes de deletar
        $this->info("\n💾 Criando backup dos dados...");
        $backup = [
            'user' => $userToRemove,
            'patr' => DB::table('patr')->where('USUARIO', $username)->get(),
            'movpartr' => DB::table('movpartr')->where('USUARIO', $username)->get(),
        ];

        $timestamp = Carbon::now()->format('Y-m-d_His');
        $backupPath = storage_path("backups/user_remove_backup_{$username}_{$timestamp}.json");
        file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->info("   📄 Backup criado: storage/backups/user_remove_backup_{$username}_{$timestamp}.json");

        // Deletar registros
        $this->info("\n🔄 Iniciando remoção...\n");
        
        // Remover patrimônios
        $patrDeleted = DB::table('patr')->where('USUARIO', $username)->delete();
        $this->line("✅ $patrDeleted patrimônios removidos");

        // Remover histórico
        $historicoDeleted = DB::table('movpartr')->where('USUARIO', $username)->delete();
        $this->line("✅ $historicoDeleted registros de histórico removidos");

        // Remover usuário
        DB::table('usuario')->where('NOMEUSER', $username)->delete();
        $this->line("✅ Usuário $username removido do sistema\n");

        Log::info("🗑️ [REMOVE-DUPLICATE] Usuário removido: $username ($totalRecords registros deletados)");

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🎉 Remoção concluída!");
        $this->info("📊 $totalRecords registros processados");
        $this->info("💾 Backup disponível em: storage/backups/");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");

        return 0;
    }
}
