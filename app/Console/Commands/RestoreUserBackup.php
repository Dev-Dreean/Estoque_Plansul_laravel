<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestoreUserBackup extends Command
{
    protected $signature = 'users:restore-backup {backup_file}';
    protected $description = 'Restaurar usuário e dados de um backup JSON';

    public function handle()
    {
        $backupFile = $this->argument('backup_file');
        $backupPath = storage_path("backups/$backupFile");

        if (!file_exists($backupPath)) {
            $this->error("❌ Arquivo de backup não encontrado: $backupPath");
            return 1;
        }

        $this->info("\n🔄 Restaurando dados do backup...\n");

        // Ler backup
        $backup = json_decode(file_get_contents($backupPath), true);
        if (!$backup) {
            $this->error("❌ Arquivo de backup inválido!");
            return 1;
        }

        // Confirmação
        if (!$this->confirm("⚠️  AVISO: Esta operação restaurará todos os dados deletados. Deseja prosseguir?")) {
            $this->info("❌ Operação cancelada.");
            return 1;
        }

        $this->info("\n💾 Iniciando restauração...\n");

        // Restaurar usuário
        $user = $backup['user'];
        DB::table('usuario')->insert((array)$user);
        $this->line("✅ Usuário restaurado: {$user['NOMEUSER']}\n");

        // Restaurar patrimônios
        $patrCount = count($backup['patr']);
        foreach ($backup['patr'] as $patr) {
            DB::table('patr')->insert((array)$patr);
        }
        $this->line("✅ $patrCount patrimônios restaurados");

        // Restaurar histórico
        $historicoCount = count($backup['movpartr']);
        foreach ($backup['movpartr'] as $historico) {
            DB::table('movpartr')->insert((array)$historico);
        }
        $this->line("✅ $historicoCount registros de histórico restaurados\n");

        Log::info("✅ [RESTORE-BACKUP] Backup restaurado: " . $user['NOMEUSER'] . " (" . ($patrCount + $historicoCount) . " registros)");

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🎉 Restauração concluída!");
        $this->info("📊 " . ($patrCount + $historicoCount) . " registros restaurados");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");

        return 0;
    }
}
