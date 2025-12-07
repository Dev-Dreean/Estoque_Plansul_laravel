<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnifyDuplicateUsers extends Command
{
    protected $signature = 'users:unify {--dry-run} {--user=BEATRIZ.SC}';
    protected $description = 'Unifica usuários duplicados e consolida seus dados. Exemplo: php artisan users:unify --user=BEATRIZ.SC (consolidará BEATRIZ.SC como principal, movendo dados de BEA.SC para ela)';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $mainUser = $this->option('user');
        
        $this->info("🔍 Procurando usuários duplicados...\n");
        
        // Buscar o usuário principal
        $usuario = DB::table('usuario')->where('NMLOGIN', $mainUser)->first();
        
        if (!$usuario) {
            $this->error("❌ Usuário '$mainUser' não encontrado!");
            return 1;
        }
        
        $this->line("✅ Usuário principal encontrado: {$usuario->NMLOGIN}");
        $this->line("   - CDMATRFUNCIONARIO: {$usuario->CDMATRFUNCIONARIO}");
        $this->line("   - NOMEUSER: {$usuario->NOMEUSER}\n");
        
        // Procurar por padrões similares (por nome)
        $nomeBase = substr($mainUser, 0, 3); // Ex: "BEA"
        $usuariosSimilares = DB::table('usuario')
            ->whereRaw("LOWER(NMLOGIN) LIKE ?", ["%$nomeBase%"])
            ->where('NMLOGIN', '!=', $mainUser)
            ->get();
        
        if ($usuariosSimilares->isEmpty()) {
            $this->info("ℹ️ Nenhum usuário similar encontrado.");
            return 0;
        }
        
        $this->line("📋 Usuários similares encontrados:\n");
        foreach ($usuariosSimilares as $u) {
            $this->line("  • {$u->NMLOGIN} (CDMATR: {$u->CDMATRFUNCIONARIO})");
        }
        
        // Contar dados associados
        $this->line("\n📊 Analisando dados associados...\n");
        
        $totalDados = 0;
        $consolidacoes = [];
        
        foreach ($usuariosSimilares as $usuarioSecundario) {
            // Patrimônios cadastrados pelo usuário secundário
            $patrimonios = DB::table('patr')
                ->where('USUARIO', $usuarioSecundario->NMLOGIN)
                ->count();
            
            // Patrimônios onde é responsável
            $patrimoniosResponsavel = DB::table('patr')
                ->where('CDMATRFUNCIONARIO', $usuarioSecundario->CDMATRFUNCIONARIO)
                ->count();
            
            // Histórico
            $historico = DB::table('movpartr')
                ->where('USUARIO', $usuarioSecundario->NMLOGIN)
                ->count();
            
            $totalRegistros = $patrimonios + $patrimoniosResponsavel + $historico;
            
            if ($totalRegistros > 0) {
                $this->line("  Usuário: {$usuarioSecundario->NMLOGIN}");
                $this->line("    - Patrimônios cadastrados: $patrimonios");
                $this->line("    - Patrimônios como responsável: $patrimoniosResponsavel");
                $this->line("    - Registros de histórico: $historico");
                $this->line("    - TOTAL: $totalRegistros registros\n");
                
                $consolidacoes[] = [
                    'usuario' => $usuarioSecundario,
                    'patrimonios' => $patrimonios,
                    'patrimoniosResponsavel' => $patrimoniosResponsavel,
                    'historico' => $historico,
                    'total' => $totalRegistros
                ];
                
                $totalDados += $totalRegistros;
            }
        }
        
        if (empty($consolidacoes)) {
            $this->info("ℹ️ Nenhum dado associado aos usuários similares.");
            return 0;
        }
        
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("📈 TOTAL DE REGISTROS A CONSOLIDAR: $totalDados");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");
        
        if ($isDryRun) {
            $this->info("🔄 (DRY RUN) Seriam feitas as seguintes consolidações:");
            foreach ($consolidacoes as $c) {
                $this->line("  • {$c['usuario']->NMLOGIN} → $mainUser ({$c['total']} registros)");
            }
            $this->info("\n✅ DRY RUN concluído. Use sem --dry-run para aplicar.");
            return 0;
        }
        
        if (!$this->confirm("\n⚠️  AVISO: Esta operação é irreversível! Deseja prosseguir com a consolidação?")) {
            $this->warn("❌ Operação cancelada.");
            return 1;
        }
        
        // Criar backup antes de fazer alterações
        $this->info("\n💾 Criando backup dos dados...");
        $this->criarBackup($mainUser, $consolidacoes);
        
        // Executar consolidações
        $this->info("\n🔄 Iniciando consolidação...\n");
        
        $registrosConsolidados = 0;
        
        foreach ($consolidacoes as $c) {
            $usuarioSecundario = $c['usuario'];
            
            try {
                // 1. Atualizar patrimônios cadastrados
                if ($c['patrimonios'] > 0) {
                    DB::table('patr')
                        ->where('USUARIO', $usuarioSecundario->NMLOGIN)
                        ->update(['USUARIO' => $mainUser]);
                    
                    $this->line("✅ {$c['patrimonios']} patrimônios cadastrados reatribuídos para $mainUser");
                    $registrosConsolidados += $c['patrimonios'];
                }
                
                // 2. Atualizar patrimônios onde é responsável (CDMATRFUNCIONARIO)
                if ($c['patrimoniosResponsavel'] > 0) {
                    DB::table('patr')
                        ->where('CDMATRFUNCIONARIO', $usuarioSecundario->CDMATRFUNCIONARIO)
                        ->update(['CDMATRFUNCIONARIO' => $usuario->CDMATRFUNCIONARIO]);
                    
                    $this->line("✅ {$c['patrimoniosResponsavel']} patrimônios como responsável reatribuídos");
                    $registrosConsolidados += $c['patrimoniosResponsavel'];
                }
                
                // 3. Atualizar histórico
                if ($c['historico'] > 0) {
                    DB::table('movpartr')
                        ->where('USUARIO', $usuarioSecundario->NMLOGIN)
                        ->update(['USUARIO' => $mainUser]);
                    
                    $this->line("✅ {$c['historico']} registros de histórico consolidados");
                    $registrosConsolidados += $c['historico'];
                }
                
                // 4. Registrar consolidação no log
                Log::info("👤 [USERS:UNIFY] Usuário consolidado", [
                    'usuario_secundario' => $usuarioSecundario->NMLOGIN,
                    'usuario_principal' => $mainUser,
                    'cdmatr_secundario' => $usuarioSecundario->CDMATRFUNCIONARIO,
                    'cdmatr_principal' => $usuario->CDMATRFUNCIONARIO,
                    'patrimonio_cadastrados' => $c['patrimonios'],
                    'patrimonio_responsavel' => $c['patrimoniosResponsavel'],
                    'historico_registros' => $c['historico'],
                ]);
                
            } catch (\Exception $e) {
                $this->error("❌ Erro ao consolidar {$usuarioSecundario->NMLOGIN}: " . $e->getMessage());
                Log::error("Erro ao consolidar usuário", [
                    'usuario_secundario' => $usuarioSecundario->NMLOGIN,
                    'erro' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        $this->line("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("🎉 Consolidação concluída!");
        $this->line("📊 $registrosConsolidados registros consolidados com sucesso");
        $this->line("💾 Backup disponível em: storage/backups/");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        return 0;
    }
    
    protected function criarBackup($mainUser, $consolidacoes)
    {
        $timestamp = now()->format('Y-m-d_His');
        $backupDir = storage_path('backups');
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . "/user_unify_backup_{$mainUser}_{$timestamp}.json";
        
        $dadosBackup = [
            'timestamp' => now()->toDateTimeString(),
            'main_user' => $mainUser,
            'consolidacoes' => array_map(function($c) {
                return [
                    'usuario_secundario' => $c['usuario']->NMLOGIN,
                    'cdmatr' => $c['usuario']->CDMATRFUNCIONARIO,
                    'registros' => $c['total']
                ];
            }, $consolidacoes),
        ];
        
        file_put_contents($backupFile, json_encode($dadosBackup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->line("   📄 Backup criado: $backupFile");
    }
}
