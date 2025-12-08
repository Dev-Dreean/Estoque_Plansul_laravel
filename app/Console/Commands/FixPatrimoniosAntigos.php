<?php

namespace App\Console\Commands;

use App\Models\Patrimonio;
use App\Models\ObjetoPatr;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixPatrimoniosAntigos extends Command
{
    protected $signature = 'patrimonio:fix-antigos {--dry-run}';
    protected $description = 'Corrigir patrimônios antigos: CDPROJETO=1, criar objetos únicos por DEPATRIMONIO';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('🔍 Analisando patrimônios antigos...');
        
        // Patrimonios antigos com DEPATRIMONIO preenchido
        $patrimoniosAntigos = Patrimonio::whereNotNull('DEPATRIMONIO')
            ->where('DEPATRIMONIO', '!=', '')
            ->where('NUSEQPATR', '<', 100)
            ->orderBy('NUSEQPATR')
            ->get();
        
        $total = $patrimoniosAntigos->count();
        $this->info("📊 Encontrados: $total patrimônios\n");
        
        if ($total === 0) {
            $this->info('✅ Nenhum patrimônio antigo!');
            return 0;
        }
        
        $atualizados = 0;
        $objetosCriados = 0;
        $erros = 0;
        $mapaDescricoes = [];
        
        foreach ($patrimoniosAntigos as $patr) {
            try {
                $nuseq = $patr->NUSEQPATR;
                $descricao = trim($patr->DEPATRIMONIO);
                
                // 1. Corrigir CDPROJETO
                if (empty($patr->CDPROJETO) || $patr->CDPROJETO == 0) {
                    if (!$dryRun) {
                        $patr->update(['CDPROJETO' => 1]);
                    }
                    $this->line("  ✅ #{$nuseq}: CDPROJETO → 1");
                }
                
                // 2. Criar/buscar objeto para essa descrição
                if (!isset($mapaDescricoes[$descricao])) {
                    $objeto = ObjetoPatr::where('DEOBJETO', $descricao)->first();
                    
                    if (!$objeto) {
                        if (!$dryRun) {
                            $proximoId = DB::table('OBJETOPATR')->max('NUSEQOBJETO') + 1;
                            DB::table('OBJETOPATR')->insert([
                                'NUSEQOBJETO' => $proximoId,
                                'NUSEQTIPOPATR' => 20,
                                'DEOBJETO' => $descricao,
                            ]);
                            $mapaDescricoes[$descricao] = $proximoId;
                            $this->line("    🆕 Objeto: ID=$proximoId, DESC=$descricao");
                            $objetosCriados++;
                        } else {
                            $this->line("    [DRY] Criaria objeto: $descricao");
                            $mapaDescricoes[$descricao] = 9999; // placeholder
                        }
                    } else {
                        $mapaDescricoes[$descricao] = $objeto->NUSEQOBJETO;
                        $this->line("    ♻️ Objeto existente: ID={$objeto->NUSEQOBJETO}");
                    }
                }
                
                // 3. Vincular CODOBJETO
                $codObj = $mapaDescricoes[$descricao];
                if ($codObj !== 9999) {
                    if (!$dryRun) {
                        $patr->update(['CODOBJETO' => $codObj]);
                    }
                    $this->line("  ✅ #{$nuseq}: CODOBJETO → $codObj");
                    $atualizados++;
                }
                
            } catch (\Exception $e) {
                $this->error("  ❌ #{$patr->NUSEQPATR}: {$e->getMessage()}");
                $erros++;
            }
        }
        
        $this->info("\n╔════════════════════════════════════════╗");
        $this->info("║        RESULTADO DA OPERAÇÃO         ║");
        $this->info("╠════════════════════════════════════════╣");
        $this->info("║  ✅ Atualizados: " . str_pad($atualizados, 26) . "║");
        $this->info("║  🆕 Objetos:     " . str_pad($objetosCriados, 26) . "║");
        $this->info("║  ❌ Erros:       " . str_pad($erros, 26) . "║");
        $this->info("║  🔄 Modo:       " . str_pad($dryRun ? 'DRY-RUN' : 'PRODUÇÃO', 23) . "║");
        $this->info("╚════════════════════════════════════════╝\n");
        
        Log::info("✨ [FixPatrimoniosAntigos] {$atualizados} atualizados, {$objetosCriados} objetos criados. DRY-RUN: " . ($dryRun ? 'SIM' : 'NÃO'));
        
        if ($dryRun) {
            $this->warn("\n⚠️  DRY-RUN ativo!");
            $this->info("Execute sem --dry-run:");
            $this->info("php artisan patrimonio:fix-antigos");
        }
        
        return 0;
    }
}
