<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportPatrFromSQL extends Command
{
    protected $signature = 'import:patr-sql {file : Caminho do arquivo SQL a importar} {--dry-run : Simular importação sem gravar} {--log-path= : Caminho customizado para logs}';
    protected $description = '🚀 Importa dados do arquivo SQL patr.sql para a tabela patr do banco';

    public function handle()
    {
        $filePath = $this->argument('file');
        $dryRun = $this->option('dry-run');
        $logPath = $this->option('log-path') ?: storage_path('logs/import_patr.log');

        // Validar arquivo
        if (!file_exists($filePath)) {
            $this->error("❌ Arquivo não encontrado: {$filePath}");
            return 1;
        }

        $this->info("📋 Iniciando importação do arquivo patr.sql");
        $this->info("📁 Arquivo: {$filePath}");
        $this->info("🔍 Modo: " . ($dryRun ? 'DRY-RUN (sem gravar)' : 'PRODUÇÃO'));

        // Criar arquivo de log
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $log = function ($level, $msg, $data = []) use ($logPath) {
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = "[{$timestamp}] {$level} import_patr: {$msg}";
            if ($data) {
                $logEntry .= " | " . json_encode($data);
            }
            file_put_contents($logPath, $logEntry . "\n", FILE_APPEND);
        };

        $log('INFO', 'Iniciando importação', ['arquivo' => $filePath, 'dry_run' => $dryRun]);

        try {
            // Ler arquivo SQL
            $sql = file_get_contents($filePath);

            // Extrair INSERTs
            preg_match_all("/INSERT INTO `patr` \([^)]+\) VALUES\s*\((.*?)\);/s", $sql, $matches, PREG_OFFSET_CAPTURE);

            if (empty($matches[1])) {
                $this->warn("⚠️ Nenhum INSERT encontrado no arquivo");
                $log('WARN', 'Nenhum INSERT encontrado');
                return 0;
            }

            $this->info("📊 Total de blocos INSERT encontrados: " . count($matches[1]));

            $inserted = 0;
            $errors = 0;
            $skipped = 0;

            // Processar cada bloco
            foreach ($matches[1] as $blockIndex => $block) {
                // Dividir valores por linha (cada linha é um registro)
                $lines = preg_split("/\),\s*\(/", $block[0]);
                $lines[0] = ltrim($lines[0], '(');
                $lines[count($lines) - 1] = rtrim(end($lines), ')');

                foreach ($lines as $line) {
                    try {
                        // Parsear linha
                        $values = $this->parseInsertLine($line);
                        
                        if (!$values) {
                            $skipped++;
                            continue;
                        }

                        // Mapeamento de campos
                        $data = [
                            'NUSEQPATR'         => $values[0] ?? null,
                            'NUPATRIMONIO'      => $values[1] ?? null,
                            'SITUACAO'          => $values[2] ?? null,
                            'TIPO'              => $values[3] ?? null,
                            'MARCA'             => $values[4] ?? null,
                            'MODELO'            => $values[5] ?? null,
                            'CARACTERISTICAS'   => $values[6] ?? null,
                            'DIMENSAO'          => $values[7] ?? null,
                            'COR'               => $values[8] ?? null,
                            'NUSERIE'           => $values[9] ?? null,
                            'CDLOCAL'           => $values[10] ?? null,
                            'DTAQUISICAO'       => !empty($values[11]) && $values[11] !== 'NULL' ? $values[11] : null,
                            'DTBAIXA'           => !empty($values[12]) && $values[12] !== 'NULL' ? $values[12] : null,
                            'DTGARANTIA'        => !empty($values[13]) && $values[13] !== 'NULL' ? $values[13] : null,
                            'DEHISTORICO'       => $values[14] ?? null,
                            'DTLAUDO'           => !empty($values[15]) && $values[15] !== 'NULL' ? $values[15] : null,
                            'DEPATRIMONIO'      => $values[16] ?? null,
                            'CDMATRFUNCIONARIO' => $values[17] ?? null,
                            'CDLOCALINTERNO'    => $values[18] ?? null,
                            'CDPROJETO'         => $values[19] ?? null,
                            'NMPLANTA'          => $values[20] ?? null,
                            'USUARIO'           => $values[21] ?? null,
                            'DTOPERACAO'        => !empty($values[22]) && $values[22] !== 'NULL' ? $values[22] : null,
                            'FLCONFERIDO'       => $values[23] ?? null,
                            'NUMOF'             => $values[24] ?? null,
                            'CODOBJETO'         => $values[25] ?? null,
                        ];

                        // Limpar valores
                        foreach ($data as $key => $value) {
                            if ($value === 'NULL' || $value === '') {
                                $data[$key] = null;
                            }
                        }

                        // Verificar se já existe
                        if (!$dryRun) {
                            $exists = DB::table('patr')->where('NUSEQPATR', $data['NUSEQPATR'])->exists();
                            
                            if ($exists) {
                                DB::table('patr')->where('NUSEQPATR', $data['NUSEQPATR'])->update($data);
                            } else {
                                DB::table('patr')->insert($data);
                            }

                            $inserted++;
                        } else {
                            $inserted++;
                        }

                        // Log a cada 100 registros
                        if ($inserted % 100 === 0) {
                            $this->line("✅ {$inserted} registros processados...");
                        }

                    } catch (\Exception $e) {
                        $errors++;
                        $log('ERROR', 'Erro ao processar linha', ['erro' => $e->getMessage()]);
                    }
                }
            }

            $this->info("\n✅ Importação concluída!");
            $this->info("📝 Resumo:");
            $this->line("  ✓ Importados: {$inserted}");
            $this->line("  ⚠️ Erros: {$errors}");
            $this->line("  ⊘ Pulados: {$skipped}");
            $this->info("📋 Logs: {$logPath}");

            $log('INFO', 'Importação concluída', [
                'importados' => $inserted,
                'erros' => $errors,
                'pulados' => $skipped,
                'dry_run' => $dryRun
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erro: " . $e->getMessage());
            $log('ERROR', 'Erro fatal', ['erro' => $e->getMessage()]);
            return 1;
        }
    }

    /**
     * Parse uma linha de INSERT SQL
     */
    private function parseInsertLine($line)
    {
        // Remover espaços extras
        $line = trim($line);
        
        if (empty($line)) {
            return null;
        }

        // Dividir por vírgula, mas respeitar strings entre aspas
        $values = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = null;

        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];

            if (($char === '"' || $char === "'") && ($i === 0 || $line[$i-1] !== '\\')) {
                if (!$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuotes = false;
                    $quoteChar = null;
                } else {
                    $current .= $char;
                }
            } elseif ($char === ',' && !$inQuotes) {
                $values[] = $this->cleanValue($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }

        $values[] = $this->cleanValue($current);

        return count($values) === 26 ? $values : null;
    }

    /**
     * Limpa e converte valor SQL
     */
    private function cleanValue($value)
    {
        $value = trim($value);

        if ($value === 'NULL') {
            return null;
        }

        // Remove aspas
        if ((str_starts_with($value, "'") && str_ends_with($value, "'")) ||
            (str_starts_with($value, '"') && str_ends_with($value, '"'))) {
            $value = substr($value, 1, -1);
            // Unescapa aspas duplas
            $value = str_replace(['\\\'', '\\"'], ["'", '"'], $value);
        }

        return $value;
    }
}
