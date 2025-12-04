<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportarTodosPatrimonios extends Command
{
    protected $signature = 'patrimonios:importar-todos';
    protected $description = 'Importa todos os 11.268 registros do arquivo PATRIMONIO.TXT incluindo duplicados';

    public function handle()
    {
        $file = 'storage/imports/Importe anterior/PATRIMONIO.TXT';

        if (!file_exists($file)) {
            $this->error("Arquivo não encontrado: $file");
            return 1;
        }

        $this->info('=== IMPORTAÇÃO COMPLETA DE PATRIMÔNIOS (TODOS OS 11.268) ===');
        $this->newLine();

        // 1. Limpar a tabela
        $this->warn('📋 Limpando tabela patr...');
        DB::table('patr')->truncate();
        $this->info('✅ Tabela patr limpa');
        $this->newLine();

        // 2. Ler o arquivo
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $total_lines = count($lines);
        $this->line("📂 Total de linhas no arquivo: $total_lines");
        $this->newLine();

        // 3. Processar linhas de dados
        $imported = 0;
        $skipped = 0;
        $batch_data = [];
        $batch_size = 500;

        foreach ($lines as $idx => $line) {
            // Pular header e separator
            if ($idx == 0 || strpos($line, '===') !== false) {
                continue;
            }

            // Pular linhas vazias
            if (empty(trim($line))) {
                continue;
            }

            try {
                $trimmed = trim($line);

                // Extrair o primeiro número (NUPATRIMONIO)
                if (preg_match('/^(\d+)/', $trimmed, $matches)) {
                    $nu_patrimonio = (int)$matches[1];
                } else {
                    $skipped++;
                    continue;
                }

                // Remover NUPATRIMONIO do início
                $rest = substr($trimmed, strlen((string)$nu_patrimonio));
                $rest = trim($rest);

                // Split por 2+ espaços para separar campos (até 16 campos)
                $fields = preg_split('/\s{2,}/', $rest, 16, PREG_SPLIT_NO_EMPTY);

                // Função auxiliar para converter valor
                $toValue = function($val) {
                    $trimmed = trim($val ?? '');
                    if ($trimmed === '<null>' || $trimmed === '' || $trimmed === 'N') {
                        return null;
                    }
                    // Converter encoding para UTF-8 se necessário
                    if (!mb_check_encoding($trimmed, 'UTF-8')) {
                        $trimmed = mb_convert_encoding($trimmed, 'UTF-8', 'ISO-8859-1');
                    }
                    return $trimmed;
                };

                // Função para converter número
                $toInt = function($val) use ($toValue) {
                    $v = $toValue($val);
                    return $v === null ? null : (int)$v;
                };

                // Função para converter datas de DD/MM/YYYY para YYYY-MM-DD
                $toDate = function($val) use ($toValue) {
                    $v = $toValue($val);
                    if ($v === null) {
                        return null;
                    }
                    
                    // Tentar converter DD/MM/YYYY para YYYY-MM-DD
                    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $v, $matches)) {
                        $day = (int)$matches[1];
                        $month = (int)$matches[2];
                        $year = (int)$matches[3];
                        
                        // Validar data
                        if ($month < 1 || $month > 12 || $day < 1 || $day > 31 || $year < 1900 || $year > 2099) {
                            return null;
                        }
                        
                        return sprintf('%04d-%02d-%02d', $year, $month, $day);
                    }
                    return null;
                };

                // Preparar dados para inserção
                // Campos do arquivo (na ordem exata):
                // 0: SITUACAO
                // 1: MARCA
                // 2: CDLOCAL
                // 3: MODELO
                // 4: COR
                // 5: DTAQUISICAO
                // 6: DEHISTORICO
                // 7: CDMATRFUNCIONARIO
                // 8: CDPROJETO
                // 9: NUDOCFISCAL (não mapeado - ignorar)
                // 10: USUARIO
                // 11: DTOPERACAO
                // 12: NUMOF
                // 13: CODOBJETO
                // 14: FLCONFERIDO
                
                $data = [
                    'NUPATRIMONIO' => $nu_patrimonio,
                    'SITUACAO' => $toValue($fields[0] ?? null),
                    'MARCA' => $toValue($fields[1] ?? null),
                    'CDLOCAL' => $toInt($fields[2] ?? null),
                    'MODELO' => $toValue($fields[3] ?? null),
                    'COR' => $toValue($fields[4] ?? null),
                    'DTAQUISICAO' => $toDate($fields[5] ?? null),
                    'DEHISTORICO' => $toValue($fields[6] ?? null),
                    'CDMATRFUNCIONARIO' => $toInt($fields[7] ?? null),
                    'CDPROJETO' => $toInt($fields[8] ?? null),
                    'USUARIO' => $toValue($fields[10] ?? null),
                    'DTOPERACAO' => $toDate($fields[11] ?? null),
                    'NUMOF' => $toInt($fields[12] ?? null),
                    'CODOBJETO' => $toInt($fields[13] ?? null),
                    'FLCONFERIDO' => $toValue($fields[14] ?? null),
                ];

                $batch_data[] = $data;

                // Inserir em lotes
                if (count($batch_data) >= $batch_size) {
                    try {
                        DB::table('patr')->insert($batch_data);
                        $imported += count($batch_data);
                        $this->line("  ✓ Importados: $imported registros");
                        $batch_data = [];
                    } catch (\Exception $batchErr) {
                        $this->error("Erro ao inserir lote: " . $batchErr->getMessage());
                        throw $batchErr;
                    }
                }

            } catch (\Exception $e) {
                $this->error("Erro linha " . ($idx + 1) . ": " . $e->getMessage());
                $skipped++;
                break;
            }
        }

        // Inserir lote final
        if (!empty($batch_data)) {
            DB::table('patr')->insert($batch_data);
            $imported += count($batch_data);
            $this->line("  ✓ Importados: $imported registros");
        }

        $this->newLine();
        $this->info('=== APLICANDO CORREÇÕES MANUAIS ===');
        
        // Reaplicar correções conhecidas de CDLOCAL/CDPROJETO
        $corrections = [
            17546 => ['CDLOCAL' => 109, 'CDPROJETO' => 100001],
            19269 => ['CDLOCAL' => 1422, 'CDPROJETO' => 200],
        ];
        
        foreach ($corrections as $nupatrimonio => $data) {
            DB::table('patr')->where('NUPATRIMONIO', $nupatrimonio)->update($data);
            $this->line("  ✓ Patrimônio $nupatrimonio corrigido");
        }
        $this->info('✅ Correções aplicadas');

        $this->newLine();
        $this->info('=== RESULTADO DA IMPORTAÇÃO ===');
        $this->line("✅ Registros importados: $imported");
        $this->line("⏭️  Registros pulados: $skipped");

        // Verificar resultado final
        $total_db = DB::table('patr')->count();
        $unique_nu = DB::table('patr')->distinct('NUPATRIMONIO')->count('NUPATRIMONIO');

        $this->newLine();
        $this->info('=== ESTADO DO BANCO ===');
        $this->line("Total de registros na tabela: $total_db");
        $this->line("NUPATRIMONIO únicos: $unique_nu");

        // Mostrar estatísticas
        $duplicados = DB::select('
            SELECT NUPATRIMONIO, COUNT(*) as qty
            FROM patr
            GROUP BY NUPATRIMONIO
            HAVING COUNT(*) > 1
            ORDER BY qty DESC
            LIMIT 15
        ');

        if (!empty($duplicados)) {
            $this->newLine();
            $this->warn('Duplicados no banco:');
            foreach ($duplicados as $dup) {
                $this->line("  - NUPATRIMONIO {$dup->NUPATRIMONIO}: {$dup->qty} cópias");
            }
        }

        if ($total_db == 11268) {
            $this->newLine();
            $this->info('🎉 SUCESSO! Todos os 11.268 registros foram importados corretamente!');
            return 0;
        } else {
            $this->newLine();
            $this->warn("⚠️  Importação incompleta. Esperado: 11.268, Importado: $total_db");
            return 1;
        }
    }
}
