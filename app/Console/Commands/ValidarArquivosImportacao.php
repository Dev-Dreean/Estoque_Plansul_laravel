<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidarArquivosImportacao extends Command
{
    protected $signature = 'validar:importacao {--path= : Caminho customizado}';
    protected $description = 'Valida 100% dos arquivos antes da importação com contagem detalhada';

    public function handle()
    {
        $basePath = $this->option('path') ?: storage_path('imports');
        
        $this->line('');
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║     VALIDAÇÃO 100% DOS ARQUIVOS DE IMPORTAÇÃO                  ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->line('');

        $stats = [
            'localprojeto' => $this->validarLocalProjeto($basePath),
            'movpatrhistorico' => $this->validarMovPatrHistorico($basePath),
        ];

        $this->exibirResumoFinal($stats);
    }

    private function validarLocalProjeto($basePath)
    {
        $arquivo = $basePath . '/LOCALPROJETO.TXT';
        
        if (!file_exists($arquivo)) {
            $this->error("❌ Arquivo não encontrado: $arquivo");
            return null;
        }

        $this->line('📄 VALIDAÇÃO: LOCALPROJETO.TXT');
        $this->line('─────────────────────────────────────────────────────────────────');

        $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stats = [
            'total_linhas' => count($linhas),
            'cabecalhos' => 2,
            'registros_totais' => 0,
            'registros_validos' => 0,
            'registros_com_id_zero' => 0,
            'registros_invalidos' => [],
            'ids_unicos' => [],
            'ufs' => [],
        ];

        // Pular cabeçalhos (linhas 0 e 1)
        for ($i = 2; $i < count($linhas); $i++) {
            $linha = trim($linhas[$i]);
            
            if (empty($linha)) {
                continue;
            }

            $stats['registros_totais']++;
            
            // Parse: ID NOME FILIAL UF
            $partes = preg_split('/\s{2,}/', $linha);
            
            if (count($partes) < 3) {
                $stats['registros_invalidos'][] = [
                    'linha' => $i + 1,
                    'motivo' => 'Estrutura inválida (menos de 3 campos)',
                    'conteudo' => substr($linha, 0, 60)
                ];
                continue;
            }

            try {
                $id = (int)trim($partes[0]);
                $uf = trim($partes[count($partes) - 1]);

                if ($id == 0) {
                    $stats['registros_com_id_zero']++;
                } else {
                    $stats['ids_unicos'][$id] = true;
                }

                if (!empty($uf) && $uf !== '<null>') {
                    $stats['ufs'][$uf] = ($stats['ufs'][$uf] ?? 0) + 1;
                }

                $stats['registros_validos']++;

            } catch (\Exception $e) {
                $stats['registros_invalidos'][] = [
                    'linha' => $i + 1,
                    'motivo' => 'Erro ao parsear: ' . $e->getMessage(),
                    'conteudo' => substr($linha, 0, 60)
                ];
            }
        }

        $this->line('  ✓ Total de registros: ' . $stats['registros_totais']);
        $this->line('  ✓ Registros válidos: ' . $stats['registros_validos']);
        $this->line('  ✓ IDs únicos: ' . count($stats['ids_unicos']));
        $this->line('  ✓ Com ID 0: ' . $stats['registros_com_id_zero']);
        $this->line('  ✗ Registros inválidos: ' . count($stats['registros_invalidos']));
        $this->line('  ✓ Estados únicos: ' . count($stats['ufs']));

        if (!empty($stats['ufs'])) {
            $this->line('    Estados: ' . implode(', ', array_keys($stats['ufs'])));
        }

        if (!empty($stats['registros_invalidos'])) {
            $this->line('  ⚠ Problemas encontrados (primeiros 3):');
            foreach (array_slice($stats['registros_invalidos'], 0, 3) as $problema) {
                $this->line("    Linha {$problema['linha']}: {$problema['motivo']}");
                $this->line("    Conteúdo: {$problema['conteudo']}");
            }
        }

        $taxa = ($stats['registros_validos'] / $stats['registros_totais']) * 100;
        $this->line("  📊 Taxa de sucesso esperada: " . number_format($taxa, 2) . "%");
        $this->line('');

        return $stats;
    }

    private function validarMovPatrHistorico($basePath)
    {
        $arquivo = $basePath . '/MOVPATRHISTORICO.TXT';
        
        if (!file_exists($arquivo)) {
            $this->error("❌ Arquivo não encontrado: $arquivo");
            return null;
        }

        $this->line('📄 VALIDAÇÃO: MOVPATRHISTORICO.TXT');
        $this->line('─────────────────────────────────────────────────────────────────');

        $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stats = [
            'total_linhas' => count($linhas),
            'cabecalhos' => 2,
            'registros_totais' => 0,
            'registros_validos' => 0,
            'registros_com_id_zero' => 0,
            'registros_invalidos' => [],
            'datas_validas' => 0,
            'usuarios_unicos' => [],
            'tipos_movimento' => [],
        ];

        // Pular cabeçalhos (linhas 0 e 1)
        for ($i = 2; $i < count($linhas); $i++) {
            $linha = trim($linhas[$i]);
            
            if (empty($linha)) {
                continue;
            }

            $stats['registros_totais']++;

            // Parse: NUPATRIM NUPROJ DTMOVI FLMOV USUARIO DTOPERACAO
            $partes = preg_split('/\s{2,}/', $linha);
            
            if (count($partes) < 4) {
                $stats['registros_invalidos'][] = [
                    'linha' => $i + 1,
                    'motivo' => 'Estrutura inválida (menos de 4 campos)',
                    'conteudo' => substr($linha, 0, 60)
                ];
                continue;
            }

            try {
                $nupatrim = (int)trim($partes[0]);
                $nuproj = (int)trim($partes[1]);
                $dtmovi = trim($partes[2]);
                $flmov = trim($partes[3]);
                $usuario = isset($partes[4]) ? trim($partes[4]) : '';

                // Validar data
                if (!preg_match('/\d{2}\/\d{2}\/\d{4}/', $dtmovi)) {
                    $stats['registros_invalidos'][] = [
                        'linha' => $i + 1,
                        'motivo' => "Data inválida: $dtmovi",
                        'conteudo' => substr($linha, 0, 60)
                    ];
                    continue;
                }

                if ($nupatrim == 0 || $nuproj == 0) {
                    $stats['registros_com_id_zero']++;
                }

                $stats['datas_validas']++;
                
                if (!empty($usuario)) {
                    $stats['usuarios_unicos'][$usuario] = true;
                }
                
                $stats['tipos_movimento'][$flmov] = ($stats['tipos_movimento'][$flmov] ?? 0) + 1;

                $stats['registros_validos']++;

            } catch (\Exception $e) {
                $stats['registros_invalidos'][] = [
                    'linha' => $i + 1,
                    'motivo' => 'Erro ao parsear: ' . $e->getMessage(),
                    'conteudo' => substr($linha, 0, 60)
                ];
            }
        }

        $this->line('  ✓ Total de registros: ' . $stats['registros_totais']);
        $this->line('  ✓ Registros válidos: ' . $stats['registros_validos']);
        $this->line('  ✓ Datas válidas: ' . $stats['datas_validas']);
        $this->line('  ✓ Registros com ID 0: ' . $stats['registros_com_id_zero']);
        $this->line('  ✗ Registros inválidos: ' . count($stats['registros_invalidos']));
        $this->line('  ✓ Usuários únicos: ' . count($stats['usuarios_unicos']));
        $this->line('  ✓ Tipos de movimento: ' . implode(', ', array_keys($stats['tipos_movimento'])));

        if (!empty($stats['registros_invalidos'])) {
            $this->line('  ⚠ Problemas encontrados (primeiros 3):');
            foreach (array_slice($stats['registros_invalidos'], 0, 3) as $problema) {
                $this->line("    Linha {$problema['linha']}: {$problema['motivo']}");
                $this->line("    Conteúdo: {$problema['conteudo']}");
            }
        }

        $taxa = ($stats['registros_validos'] / $stats['registros_totais']) * 100;
        $this->line("  📊 Taxa de sucesso esperada: " . number_format($taxa, 2) . "%");
        $this->line('');

        return $stats;
    }

    private function exibirResumoFinal($stats)
    {
        $this->line('╔════════════════════════════════════════════════════════════════╗');
        $this->line('║                    RESUMO FINAL                                ║');
        $this->line('╚════════════════════════════════════════════════════════════════╝');
        $this->line('');

        $lp = $stats['localprojeto'];
        $mp = $stats['movpatrhistorico'];

        if ($lp) {
            $this->line('LOCALPROJETO.TXT:');
            $this->line("  📊 Total de registros a processar: {$lp['registros_totais']}");
            $this->line("  ✓ Registros válidos e importáveis: {$lp['registros_validos']}");
            $this->line("  ℹ  IDs únicos: " . count($lp['ids_unicos']));
            $this->line('');
        }

        if ($mp) {
            $this->line('MOVPATRHISTORICO.TXT:');
            $this->line("  📊 Total de registros a processar: {$mp['registros_totais']}");
            $this->line("  ✓ Registros válidos e importáveis: {$mp['registros_validos']}");
            $this->line("  ✓ Datas válidas: {$mp['datas_validas']}");
            $this->line('');
        }

        if ($lp && $mp) {
            $total = $lp['registros_totais'] + $mp['registros_totais'];
            $validos = $lp['registros_validos'] + $mp['registros_validos'];
            $taxa = ($validos / $total) * 100;

            $this->line('═══════════════════════════════════════════════════════════════');
            $this->line("  📈 TOTAL GERAL:");
            $this->line("     Registros totais: $total");
            $this->line("     Registros válidos: $validos");
            $this->line("     Taxa geral de sucesso: " . number_format($taxa, 2) . "%");
            $this->line('═══════════════════════════════════════════════════════════════');
            $this->line('');

            if ($taxa == 100) {
                $this->line('✅ TUDO PRONTO! Todos os registros estão válidos para importação!');
                $this->line('');
                $this->line('Execute: php artisan import:kinghost');
            } else {
                $this->line('⚠ ATENÇÃO: Alguns registros podem falhar durante a importação.');
                $this->line('Verifique os erros acima.');
            }
        }

        $this->line('');
    }
}
