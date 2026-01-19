<?php

namespace App\Console\Commands;

use App\Models\Funcionario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerarRelatorfuncionarios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'relatorio:funcionarios {--output=csv} {--path=}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Gera relatório de funcionários (CSV ou Excel)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $output = $this->option('output');
        $path = $this->option('path');

        $this->info("📊 Gerando relatório de funcionários...");

        $this->gerarCSV($path);
    }

    /**
     * Gera relatório em CSV com chunking (otimizado para 92k+ registros)
     * Usa cursor() para não carregar tudo em memória de uma vez
     */
    private function gerarCSV(?string $path): void
    {
        try {
            $filename = 'relatorio_funcionarios_' . now()->format('Ymd_His') . '.csv';
            $filepath = $path ?? storage_path("output/{$filename}");

            // Criar diretório se não existir
            if (!is_dir(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }

            $file = fopen($filepath, 'w');
            if (!$file) {
                throw new \Exception("Não foi possível abrir o arquivo para escrita");
            }

            // Cabeçalhos
            fputcsv($file, ['Matrícula', 'Nome', 'Cargo', 'Filial', 'UF', 'Data Admissão'], ';');

            // 🚀 OTIMIZAÇÃO: Usar cursor() em vez de get()
            // Cursor carrega 1000 registros por vez da memória (em vez de 92k de uma vez)
            $count = 0;
            $inicio = microtime(true);
            
            $funcionarios = Funcionario::select([
                'CDMATRFUNCIONARIO',
                'NMFUNCIONARIO',
                'CDCARGO',
                'CODFIL',
                'UFPROJ',
                'DTADMISSAO'
            ])
            ->orderBy('CDMATRFUNCIONARIO')
            ->cursor(); // ⚡ CURSOR = Streaming, não carrega tudo!

            foreach ($funcionarios as $func) {
                fputcsv($file, [
                    $func->CDMATRFUNCIONARIO,
                    $func->NMFUNCIONARIO,
                    $func->CDCARGO ?? '-',
                    $func->CODFIL ?? '-',
                    $func->UFPROJ ?? '-',
                    $func->DTADMISSAO ?? '-',
                ], ';');
                $count++;
                
                // Progress: a cada 5000 registros, exibir status
                if ($count % 5000 === 0) {
                    $tempo = (microtime(true) - $inicio);
                    $this->line("   ⏳ {$count} registros processados em " . number_format($tempo, 1) . "s...");
                }
            }

            fclose($file);
            $tempo_total = (microtime(true) - $inicio);

            $this->info("✅ Relatório gerado: {$filepath}");
            $this->line("   📊 Total: {$count} registros");
            $this->line("   ⏱️  Tempo: " . number_format($tempo_total, 2) . "s (" . number_format($count/$tempo_total, 0) . " reg/s)");
            
            Log::info("✅ [RELATORIO_FUNCIONARIOS] Relatório gerado com sucesso", [
                'arquivo' => $filename,
                'registros' => $count,
                'tempo_segundos' => $tempo_total,
                'caminho' => $filepath,
            ]);

        } catch (\Exception $e) {
            $this->error("❌ Erro ao gerar relatório: " . $e->getMessage());
            Log::error("❌ [RELATORIO_FUNCIONARIOS] Erro ao gerar relatório", [
                'erro' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
        }
    }
}
