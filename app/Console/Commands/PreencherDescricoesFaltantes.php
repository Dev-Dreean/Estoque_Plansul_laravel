<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Patrimonio;
use Illuminate\Support\Facades\Log;

class PreencherDescricoesFaltantes extends Command
{
    protected $signature = 'patrimonios:preencher-faltantes {--dry-run} {--force}';
    protected $description = 'Preenche descrições aleatórias convincentes para patrimônios sem descrição';

    // Descrições genéricas por tipo de situação
    private $descricoes = [
        'EM USO' => [
            'Equipamento em funcionamento',
            'Bem operacional',
            'Item em uso regular',
            'Equipamento disponível',
            'Material de trabalho',
            'Bem funcional',
            'Equipamento ativo',
        ],
        'BAIXA' => [
            'Item em processo de retirada',
            'Bem descartado',
            'Equipamento fora de uso',
            'Material descontinuado',
            'Bem baixado do patrimônio',
            'Item retirado',
        ],
        'À DISPOSIÇÃO' => [
            'Bem à disposição da organização',
            'Equipamento reservado',
            'Item para alocação',
            'Material disponível',
            'Bem para distribuição',
            'Equipamento reservado',
            'Item em espera de destinação',
        ],
        'DANIFICADO' => [
            'Bem danificado aguardando reparo',
            'Equipamento com defeito',
            'Material com avaria',
            'Item em manutenção',
        ],
        'PERDIDO' => [
            'Bem não localizado',
            'Equipamento perdido',
            'Material extraviado',
        ],
    ];

    public function handle()
    {
        $this->info('🚀 [PREENCHER DESCRIÇÕES] Preenchendo patrimônios sem descrição');

        // IDs dos patrimônios sem descrição
        $ids = [5311, 5560, 8505, 8548, 20318, 25210, 29728, 36206];

        $processados = 0;
        $atualizados = 0;
        $erros = 0;

        foreach ($ids as $id) {
            try {
                $patrimonio = Patrimonio::where('NUPATRIMONIO', $id)->first();

                if (!$patrimonio) {
                    $this->warn("⚠️  Patrimônio #{$id} não encontrado");
                    $erros++;
                    continue;
                }

                $processados++;

                // Obter situação
                $situacao = trim($patrimonio->SITUACAO ?? 'EM USO');

                // Selecionar descrição aleatória baseada na situação
                $descricoes = $this->descricoes[$situacao] ?? $this->descricoes['EM USO'];
                $descricao = $descricoes[array_rand($descricoes)];

                if ($this->option('dry-run')) {
                    $this->info("🔍 [DRY-RUN] Patrimônio #{$id}: VAZIO → '{$descricao}'");
                } else {
                    $patrimonio->DEPATRIMONIO = $descricao;
                    $patrimonio->save();

                    Log::info("📝 [PREENCHER FALTANTES] Patrimônio #{$id}: '{$descricao}'", [
                        'NUPATRIMONIO' => $id,
                        'SITUACAO' => $situacao,
                        'DEPATRIMONIO' => $descricao,
                    ]);

                    $this->info("✅ Patrimônio #{$id}: '{$descricao}'");
                    $atualizados++;
                }
            } catch (\Exception $e) {
                $this->error("❌ Erro ao processar #{$id}: {$e->getMessage()}");
                $erros++;
            }
        }

        $this->newLine();
        $this->info(str_repeat('═', 60));
        $this->info('📊 RESUMO DO PREENCHIMENTO');
        $this->info(str_repeat('═', 60));
        $this->info("📝 Total processados: {$processados}");
        $this->info("✅ Total atualizados: {$atualizados}");
        $this->info("❌ Total erros: {$erros}");
        $this->info(str_repeat('═', 60));

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->line('<fg=cyan>💡 Use sem --dry-run para executar de verdade</>');
        }

        return 0;
    }
}
