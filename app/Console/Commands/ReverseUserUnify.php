<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReverseUserUnify extends Command
{
    protected $signature = 'users:reverse-unify {--user=BEA.SC} {--dry-run}';
    protected $description = 'Reverter consolidação: BEA.SC é principal, remove BEATRIZ.SC (sem senha/UF)';

    public function handle()
    {
        $mainUser = $this->option('user');
        $dryRun = $this->option('dry-run');

        $this->info("\n🔄 Revertendo consolidação de usuários...\n");

        // Encontrar usuário principal (BEA.SC)
        $mainUserRecord = DB::table('usuario')->where('NOMEUSER', $mainUser)->first();
        if (!$mainUserRecord) {
            $this->error("❌ Usuário principal '$mainUser' não encontrado!");
            return 1;
        }

        $this->info("✅ Usuário principal encontrado: $mainUser");
        $this->info("   - CDMATRFUNCIONARIO: {$mainUserRecord->CDMATRFUNCIONARIO}");
        $this->info("   - NOMEUSER: {$mainUserRecord->NOMEUSER}\n");

        // Encontrar usuários a serem removidos (BEATRIZ.SC)
        $usersToRemove = DB::table('usuario')
            ->whereRaw("SUBSTRING(NOMEUSER, 1, 3) = ?", [substr($mainUser, 0, 3)])
            ->where('NOMEUSER', '!=', $mainUser)
            ->get();

        if ($usersToRemove->isEmpty()) {
            $this->warn("⚠️ Nenhum usuário a remover encontrado.");
            return 0;
        }

        $this->info("📋 Usuários a serem removidos:\n");
        foreach ($usersToRemove as $user) {
            $this->line("  • {$user->NOMEUSER} (CDMATR: {$user->CDMATRFUNCIONARIO})");
        }

        // Contar registros associados
        $this->info("\n📊 Analisando dados associados...\n");
        
        $totalRecords = 0;
        foreach ($usersToRemove as $user) {
            $patrCount = DB::table('patr')->where('USUARIO', $user->NOMEUSER)->count();
            $historicoCount = DB::table('movpartr')->where('USUARIO', $user->NOMEUSER)->count();
            $subTotal = $patrCount + $historicoCount;
            
            $this->line("  Usuário: {$user->NOMEUSER}");
            $this->line("    - Patrimônios: $patrCount");
            $this->line("    - Histórico: $historicoCount");
            $this->line("    - TOTAL: $subTotal registros\n");
            
            $totalRecords += $subTotal;
        }

        $this->warn("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->warn("📈 TOTAL DE REGISTROS A REMOVER: $totalRecords");
        $this->warn("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");

        if ($dryRun) {
            $this->info("🔄 (DRY RUN) Seriam removidos:");
            foreach ($usersToRemove as $user) {
                $this->line("  • {$user->NOMEUSER} (e $totalRecords registros associados)");
            }
            $this->info("\n✅ DRY RUN concluído. Use sem --dry-run para aplicar.");
            return 0;
        }

        // Confirmação
        if (!$this->confirm("\n⚠️  AVISO: Esta operação é IRREVERSÍVEL! Deseja prosseguir?")) {
            $this->info("❌ Operação cancelada.");
            return 1;
        }

        // Backup antes de deletar
        $this->info("\n💾 Criando backup dos dados...");
        $backup = [];
        foreach ($usersToRemove as $user) {
            $backup[$user->NOMEUSER] = [
                'user' => $user,
                'patr' => DB::table('patr')->where('USUARIO', $user->NOMEUSER)->get(),
                'movpartr' => DB::table('movpartr')->where('USUARIO', $user->NOMEUSER)->get(),
            ];
        }

        $timestamp = Carbon::now()->format('Y-m-d_His');
        $backupPath = storage_path("backups/user_remove_backup_{$timestamp}.json");
        file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->info("   📄 Backup criado: storage/backups/user_remove_backup_{$timestamp}.json");

        // Deletar registros
        $this->info("\n🔄 Iniciando remoção...\n");
        
        foreach ($usersToRemove as $user) {
            // Remover registros de patrimônio
            $patrDeleted = DB::table('patr')->where('USUARIO', $user->NOMEUSER)->delete();
            $this->line("✅ {$patrDeleted} patrimônios removidos para {$user->NOMEUSER}");

            // Remover registros de histórico
            $historicoDeleted = DB::table('movpartr')->where('USUARIO', $user->NOMEUSER)->delete();
            $this->line("✅ {$historicoDeleted} registros de histórico removidos");

            // Remover usuário
            $userDeleted = DB::table('usuario')->where('NOMEUSER', $user->NOMEUSER)->delete();
            if ($userDeleted) {
                $this->line("✅ Usuário {$user->NOMEUSER} removido do sistema\n");
            }
        }

        Log::info("🗑️ [REVERSE-UNIFY] Consolidação revertida - Usuários removidos: " . 
                  implode(', ', $usersToRemove->pluck('NOMEUSER')->toArray()));

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🎉 Remoção concluída!");
        $this->info("📊 $totalRecords registros processados");
        $this->info("💾 Backup disponível em: storage/backups/");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");

        return 0;
    }
}
