<?php
// one-off: Restaurar USUARIO (cadastrador) correto do arquivo TSV do KingHost
// Lê usuarios_kinghost.tsv e atualiza TODOS os registros com os valores corretos

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestoreUsuarioFromTsv extends Command
{
    protected $signature = 'restore:usuario-from-tsv {--dry-run}';
    protected $description = 'Restaurar campo USUARIO de todos os patrimônios do arquivo TSV do KingHost';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $filePath = base_path('usuarios_kinghost.tsv');
        
        if (!file_exists($filePath)) {
            $this->error("❌ Arquivo não encontrado: $filePath");
            return 1;
        }
        
        $this->line('📖 [RESTAURAR USUARIO] Lendo arquivo do KingHost...');
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (count($lines) < 2) {
            $this->error("❌ Arquivo vazio ou inválido");
            return 1;
        }
        
        // Skip header
        $header = array_shift($lines);
        
        $this->line("📦 Total de registros a processar: " . count($lines));
        
        // Preparar mapa de NUSEQPATR => USUARIO
        $usuarioMap = [];
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            
            // Split por TAB (característico de TSV)
            $parts = explode("\t", trim($line));
            if (count($parts) >= 2) {
                $nuseqpatr = (int)trim($parts[0]);
                $usuario = trim($parts[1]);
                if ($nuseqpatr > 0 && !empty($usuario)) {
                    $usuarioMap[$nuseqpatr] = $usuario;
                }
            }
        }
        
        $this->line("✅ Mapa carregado: " . count($usuarioMap) . " registros");
        
        // Contar atualizações necessárias
        $toUpdate = DB::table('patr')
            ->whereIn('USUARIO', ['SISTEMA', 'system', ''])
            ->count();
        
        $this->line("⚠️  Registros que serão atualizados: $toUpdate");
        
        if ($dryRun) {
            $this->line("\n🔍 [DRY-RUN] Mostrando primeiras 5 atualizações:");
            
            $samples = DB::table('patr')
                ->whereIn('USUARIO', ['SISTEMA', 'system', ''])
                ->limit(5)
                ->select('NUSEQPATR', 'USUARIO')
                ->get();
            
            foreach ($samples as $item) {
                $newUser = $usuarioMap[$item->NUSEQPATR] ?? 'NÃO ENCONTRADO';
                $newUser = str_replace(['\\'], '', $newUser); // Remove escapes
                $this->line("  NUSEQPATR={$item->NUSEQPATR}: '{$item->USUARIO}' → '{$newUser}'");
            }
            
            $this->warn("\n⚠️  DRY-RUN ATIVO - Nenhuma mudança foi feita!");
            $this->line("Execute SEM --dry-run para restaurar:");
            $this->line("  php artisan restore:usuario-from-tsv");
            return 0;
        }
        
        // REALIZAR RESTAURAÇÃO
        $this->warn("\n🔴 RESTAURANDO DADOS DO KINGHOST...\n");
        
        $updated = 0;
        $errors = 0;
        $notFound = 0;
        
        $bar = $this->output->createProgressBar($toUpdate);
        $bar->start();
        
        // Fazer update em chunks para não sobrecarregar
        $patrimonios = DB::table('patr')
            ->whereIn('USUARIO', ['SISTEMA', 'system', ''])
            ->select('NUSEQPATR', 'USUARIO')
            ->get();
        
        foreach ($patrimonios as $pat) {
            $newUser = $usuarioMap[$pat->NUSEQPATR] ?? null;
            
            if ($newUser === null) {
                $notFound++;
                $bar->advance();
                continue;
            }
            
            try {
                $newUserClean = str_replace(['\\'], '', $newUser); // Remove escapes
                DB::table('patr')
                    ->where('NUSEQPATR', $pat->NUSEQPATR)
                    ->update(['USUARIO' => $newUserClean]);
                $updated++;
            } catch (\Exception $e) {
                $errors++;
                Log::error("Erro ao atualizar NUSEQPATR={$pat->NUSEQPATR}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        
        $this->line("\n\n✅ RESTAURAÇÃO CONCLUÍDA!");
        $this->line("  ✓ Atualizados: $updated");
        $this->line("  ⚠️  Não encontrados no mapa: $notFound");
        if ($errors > 0) {
            $this->line("  ❌ Erros: $errors");
        }
        
        // Verificar resultado
        $this->line("\n📊 Verificando resultado...");
        $usuarios = DB::table('patr')
            ->select('USUARIO')
            ->distinct()
            ->orderBy('USUARIO')
            ->pluck('USUARIO')
            ->toArray();
        
        $this->line("👥 Usuários após restauração:");
        foreach ($usuarios as $user) {
            $count = DB::table('patr')->where('USUARIO', $user)->count();
            $this->line("  - $user: $count registros");
        }
        
        // Verificar se ainda há SISTEMA/system
        $stillCorrupted = DB::table('patr')
            ->whereIn('USUARIO', ['SISTEMA', 'system', ''])
            ->count();
        
        if ($stillCorrupted > 0) {
            $this->warn("\n⚠️  AVISO: Ainda existem $stillCorrupted registros não restaurados!");
            $this->line("Esses provavelmente não existem no arquivo do KingHost.");
        } else {
            $this->line("\n🎉 Todos os registros foram restaurados com sucesso!");
        }
        
        Log::info("🔄 [USUARIO] Restauração completada: $updated atualizados, $notFound não encontrados, $errors erros");
        
        return 0;
    }
}
