<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDates2202 extends Command
{
    protected $signature = 'fix:dates-2202 {--dry-run}';
    protected $description = 'Corrige datas com ano 2202 no banco de dados (erro de digitação)';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('🔍 Procurando datas com ano 2202...');
        
        $erradas = DB::table('patr')
            ->where(function($q) {
                $q->whereYear('DTAQUISICAO', 2202)
                  ->orWhereYear('DTOPERACAO', 2202)
                  ->orWhereYear('DTBAIXA', 2202);
            })
            ->get();
        
        $this->line("Total encontrado: " . count($erradas));
        
        if (count($erradas) === 0) {
            $this->info('✅ Nenhuma data com ano 2202 encontrada!');
            return 0;
        }
        
        // Mostrar registros problemáticos
        $this->line("\n📋 Registros com datas 2202:");
        foreach ($erradas as $r) {
            $this->line("  - Patrimônio {$r->NUPATRIMONIO}: DTAQUISICAO={$r->DTAQUISICAO}, DTOPERACAO={$r->DTOPERACAO}");
        }
        
        // Sugerir correções
        $this->line("\n💡 Sugestão de correção: Ano 2202 → 2022 (digitação invertida)");
        
        if (!$this->confirm("\nDeseja corrigir essas datas? (Alterar 2202 para 2022)")) {
            $this->warn('Operação cancelada.');
            return 1;
        }
        
        if ($isDryRun) {
            $this->info('🔄 (DRY RUN) Seriam feitas as seguintes alterações:');
            foreach ($erradas as $r) {
                if ($r->DTAQUISICAO && strpos($r->DTAQUISICAO, '2202') !== false) {
                    $corrigida = str_replace('2202', '2022', $r->DTAQUISICAO);
                    $this->line("  - DTAQUISICAO: $r->DTAQUISICAO → $corrigida");
                }
                if ($r->DTOPERACAO && strpos($r->DTOPERACAO, '2202') !== false) {
                    $corrigida = str_replace('2202', '2022', $r->DTOPERACAO);
                    $this->line("  - DTOPERACAO: $r->DTOPERACAO → $corrigida");
                }
                if ($r->DTBAIXA && strpos($r->DTBAIXA, '2202') !== false) {
                    $corrigida = str_replace('2202', '2022', $r->DTBAIXA);
                    $this->line("  - DTBAIXA: $r->DTBAIXA → $corrigida");
                }
            }
            $this->info("\n✅ DRY RUN concluído. Use sem --dry-run para aplicar as alterações.");
            return 0;
        }
        
        // Executar correção
        $count = 0;
        foreach ($erradas as $r) {
            $updates = [];
            
            if ($r->DTAQUISICAO && strpos($r->DTAQUISICAO, '2202') !== false) {
                $updates['DTAQUISICAO'] = str_replace('2202', '2022', $r->DTAQUISICAO);
            }
            if ($r->DTOPERACAO && strpos($r->DTOPERACAO, '2202') !== false) {
                $updates['DTOPERACAO'] = str_replace('2202', '2022', $r->DTOPERACAO);
            }
            if ($r->DTBAIXA && strpos($r->DTBAIXA, '2202') !== false) {
                $updates['DTBAIXA'] = str_replace('2202', '2022', $r->DTBAIXA);
            }
            
            if (count($updates) > 0) {
                DB::table('patr')->where('NUSEQPATR', $r->NUSEQPATR)->update($updates);
                $count++;
                $this->line("✅ Patrimônio {$r->NUPATRIMONIO} corrigido");
            }
        }
        
        $this->info("\n🎉 Correção concluída! $count registros atualizados.");
        return 0;
    }
}
