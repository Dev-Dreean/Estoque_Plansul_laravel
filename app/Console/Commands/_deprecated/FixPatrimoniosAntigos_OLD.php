<?php

namespace App\Console\Commands;

use App\Models\Patrimonio;
use App\Models\ObjetoPatr;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixPatrimonioAntigos extends Command
{
    protected $signature = 'patrimonio:fix-antigos {--dry-run}';
    protected $description = 'Corrigir patrimônios antigos: preencher DEPATRIMONIO, criar objetos em OBJETOPATR, e relacionar corretamente';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('🔍 Analisando patrimônios antigos problemáticos...');
        
        // Encontrar patrimônios COM DEPATRIMONIO mas SEM CODOBJETO (ou com CODOBJETO=0)
        $patrimonios = Patrimonio::where(function($q) {
            $q->whereNull('DEPATRIMONIO')
              ->orWhere('DEPATRIMONIO', '')
              ->orWhere('CODOBJETO', '=', 0)
              ->orWhereNull('CODOBJETO');
        })
        ->whereNotNull('CDMATRFUNCIONARIO')  // Apenas os com dados válidos
        ->where('NUSEQPATR', '<=', 1000)  // Patrimônios antigos
        ->orderBy('NUSEQPATR')
        ->get();
        
        $total = $patrimonios->count();
        $this->info("📊 Patrimônios a corrigir: $total\n");
        
        if ($total === 0) {
            $this->info('✅ Nenhum patrimônio para corrigir!');
            return 0;
        }
        
        $atualizados = 0;
        $criados = 0;
        $erros = 0;
        
        foreach ($patrimonios as $patr) {
            try {
                // Se não tem descrição, usar o nome do objeto ou deixar vazio
                if (empty($patr->DEPATRIMONIO)) {
                    $this->warn("⏭️  #{$patr->NUPATRIMONIO}: SEM DESCRIÇÃO, pulando");
                    continue;
                }
                
                $descricao = trim($patr->DEPATRIMONIO);
                
                // ===== PROCURAR OU CRIAR OBJETO =====
                
                // 1. Se tem CODOBJETO válido, usar ele
                $objeto = null;
                if ($patr->CODOBJETO && $patr->CODOBJETO > 0) {
                    $objeto = ObjetoPatr::find($patr->CODOBJETO);
                }
                
                // 2. Se não encontrou, buscar por descrição similar
                if (!$objeto) {
                    $objeto = ObjetoPatr::whereRaw('LOWER(DEOBJETO) LIKE ?', ['%' . mb_strtolower($descricao) . '%'])
                        ->first();
                }
                
                // 3. Se não encontrou, CRIAR novo objeto
                if (!$objeto) {
                    if (!$dryRun) {
                        // Encontrar próximo NUSEQOBJETO disponível
                        $proximoSeq = ObjetoPatr::max('NUSEQOBJETO') + 1;
                        
                        $objeto = ObjetoPatr::create([
                            'NUSEQOBJETO'  => $proximoSeq,
                            'NUSEQTIPOPATR' => 20,  // Tipo genérico
                            'DEOBJETO'     => strtoupper($descricao)
                        ]);
                    }
                    $this->line("  ✨ CRIADO: ObjetoPatr para '{$descricao}'");
                    $criados++;
                }
                
                // ===== ATUALIZAR PATRIMÔNIO =====
                $updates = [];
                
                // Sempre preencher DEPATRIMONIO se vazio
                if (empty($patr->DEPATRIMONIO)) {
                    $updates['DEPATRIMONIO'] = strtoupper($descricao);
                }
                
                // Se não tem CODOBJETO, preencher
                if (!$patr->CODOBJETO || $patr->CODOBJETO == 0) {
                    $updates['CODOBJETO'] = $objeto->NUSEQOBJETO;
                }
                
                // Preencher CDPROJETO com 1 se for 0
                if (!$patr->CDPROJETO || $patr->CDPROJETO == 0) {
                    $updates['CDPROJETO'] = 1;  // SEDE
                }
                
                // Preencher MARCA se vazio
                if (empty($patr->MARCA)) {
                    $updates['MARCA'] = 'N/A';
                }
                
                // Preencher MODELO se vazio
                if (empty($patr->MODELO)) {
                    // Usar primeira parte da descrição ou "N/A"
                    $updates['MODELO'] = substr(strtoupper($descricao), 0, 60) ?: 'N/A';
                }
                
                if (!empty($updates)) {
                    if (!$dryRun) {
                        $patr->update($updates);
                    }
                    
                    $msg = "  ✅ #{$patr->NUPATRIMONIO}: {$descricao}";
                    if (isset($updates['CODOBJETO'])) {
                        $msg .= " [CODOBJETO={$updates['CODOBJETO']}]";
                    }
                    if (isset($updates['CDPROJETO'])) {
                        $msg .= " [PROJETO=1]";
                    }
                    $this->line($msg);
                    $atualizados++;
                } else {
                    $this->info("  ℹ️  #{$patr->NUPATRIMONIO}: JÁ CORRETO");
                }
                
            } catch (\Exception $e) {
                $this->error("  ❌ #{$patr->NUPATRIMONIO}: {$e->getMessage()}");
                $erros++;
            }
        }
        
        $this->info("\n╔════════════════════════════════════════════╗");
        $this->info("║         RESULTADO DA CORREÇÃO           ║");
        $this->info("╠════════════════════════════════════════════╣");
        $this->info("║  ✅ Atualizados:  " . str_pad($atualizados, 35) . "║");
        $this->info("║  ✨ Objetos criados: " . str_pad($criados, 31) . "║");
        $this->info("║  ❌ Erros:        " . str_pad($erros, 35) . "║");
        $this->info("║  🔄 Modo:        " . str_pad($dryRun ? 'DRY-RUN' : 'PRODUÇÃO', 34) . "║");
        $this->info("╚════════════════════════════════════════════╝\n");
        
        Log::info("🔧 [FixPatrimonioAntigos] $atualizados atualizados, $criados criados, $erros erros. DRY-RUN: " . ($dryRun ? 'SIM' : 'NÃO'));
        
        if ($dryRun) {
            $this->warn("\n⚠️  Modo DRY-RUN ativo!");
            $this->info("Execute sem --dry-run para aplicar:");
            $this->info("php artisan patrimonio:fix-antigos");
        }
        
        return 0;
    }
}
