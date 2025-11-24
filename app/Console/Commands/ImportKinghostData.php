<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LocalProjeto;
use App\Models\Patrimonio;
use App\Models\HistoricoMovimentacao;
use App\Models\Tabfant;
use Illuminate\Support\Facades\DB;
use Exception;

class ImportKinghostData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:kinghost {--path= : Caminho da pasta com os arquivos TXT}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa dados dos arquivos TXT da Kinghost (LOCALPROJETO, PATRIMONIO, MOVPATRHISTORICO, PROJETOTABFANTASIA)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $basePath = $this->option('path') ?: storage_path('imports');
            
            if (!is_dir($basePath)) {
                $this->error("❌ Pasta não encontrada: $basePath");
                return 1;
            }

            $this->info('🚀 Iniciando importação dos dados da Kinghost...');
            $this->newLine();

            // Arquivos a importar na ordem correta (dependências)
            $files = [
                'PROJETOTABFANTASIA.TXT' => 'importProjetoTabFantasia',
                'LOCALPROJETO.TXT'       => 'importLocalProjeto',
                'PATRIMONIO.TXT'         => 'importPatrimonio',
                'MOVPATRHISTORICO.TXT'   => 'importMovPatrHistorico',
            ];

            foreach ($files as $filename => $method) {
                $filepath = $basePath . DIRECTORY_SEPARATOR . $filename;
                
                if (!file_exists($filepath)) {
                    $this->warn("⚠️  Arquivo não encontrado: $filename (pulando)");
                    continue;
                }

                $this->info("📄 Processando: $filename");
                $this->{$method}($filepath);
                $this->newLine();
            }

            $this->info('✅ Importação concluída com sucesso!');
            return 0;

        } catch (Exception $e) {
            $this->error("❌ Erro durante a importação: " . $e->getMessage());
            $this->line($e->getFile() . ':' . $e->getLine());
            return 1;
        }
    }

    /**
     * Importa dados de PROJETOTABFANTASIA.TXT
     */
    private function importProjetoTabFantasia($filepath)
    {
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $count = 0;
        $errors = 0;

        // Pula header (linhas com === ou nomes de coluna)
        $dataStartLine = 2;

        for ($i = $dataStartLine; $i < count($lines); $i++) {
            try {
                $line = $lines[$i];
                $parts = preg_split('/\s{2,}/', trim($line));

                if (count($parts) < 4) continue;

                $cdfantasia = trim($parts[0]);
                $defantasia = trim($parts[1]);
                $cdfilial = trim($parts[2]) ?: 1;
                $ufproj = trim($parts[3]) ?: null;

                Tabfant::updateOrCreate(
                    ['id' => $cdfantasia],
                    [
                        'CDPROJETO' => $cdfantasia,
                        'NOMEPROJETO' => $defantasia,
                        'CDFILIAL' => $cdfilial,
                        'UFPROJ' => $ufproj,
                    ]
                );
                $count++;
            } catch (Exception $e) {
                $errors++;
                $this->warn("  ⚠️  Linha " . ($i + 1) . " ignorada: {$e->getMessage()}");
            }
        }

        $this->line("  ✓ $count registros importados, $errors erros ignorados");
    }

    /**
     * Importa dados de LOCALPROJETO.TXT
     */
    private function importLocalProjeto($filepath)
    {
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $count = 0;
        $errors = 0;

        $dataStartLine = 2;

        for ($i = $dataStartLine; $i < count($lines); $i++) {
            try {
                $line = $lines[$i];
                $parts = preg_split('/\s{2,}/', trim($line));

                if (count($parts) < 3) continue;

                $nuseqlocalproj = (int)trim($parts[0]);
                $cdlocal = (int)trim($parts[1]);
                $delocal = trim($parts[2]);
                $cdfantasia = isset($parts[3]) ? (int)trim($parts[3]) : null;

                // Encontra o Tabfant correspondente
                $tabfant = Tabfant::where('id', $cdfantasia)->first();
                $tabfantId = $tabfant ? $tabfant->id : null;

                LocalProjeto::updateOrCreate(
                    ['cdlocal' => $cdlocal],
                    [
                        'delocal' => $delocal,
                        'tabfant_id' => $tabfantId,
                        'NUSEQLOCALPROJ' => $nuseqlocalproj,
                    ]
                );
                $count++;
            } catch (Exception $e) {
                $errors++;
                $this->warn("  ⚠️  Linha " . ($i + 1) . " ignorada: {$e->getMessage()}");
            }
        }

        $this->line("  ✓ $count registros importados, $errors erros ignorados");
    }

    /**
     * Importa dados de PATRIMONIO.TXT
     */
    private function importPatrimonio($filepath)
    {
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $count = 0;
        $errors = 0;

        $dataStartLine = 2;

        for ($i = $dataStartLine; $i < count($lines); $i++) {
            try {
                $line = $lines[$i];
                $parts = preg_split('/\s{2,}/', trim($line), 14); // Máximo 14 partes

                if (count($parts) < 5) continue;

                $nupatrimonio = (int)trim($parts[0]);
                $situacao = trim($parts[1]) ?: null;
                $marca = trim($parts[2]) ?: null;
                $cdlocal = (int)trim($parts[3]);
                $modelo = trim($parts[4]) ?: null;
                $cor = isset($parts[5]) ? trim($parts[5]) : null;
                $dtaquisicao = isset($parts[6]) ? $this->parseDate(trim($parts[6])) : null;
                $dehistorico = isset($parts[7]) ? trim($parts[7]) : null;
                $cdmatrfuncionario = isset($parts[8]) ? (int)trim($parts[8]) : null;
                $cdprojeto = isset($parts[9]) ? (int)trim($parts[9]) : null;
                $nudocfiscal = isset($parts[10]) ? trim($parts[10]) : null;
                $usuario = isset($parts[11]) ? trim($parts[11]) : null;
                $dtoperacao = isset($parts[12]) ? $this->parseDate(trim($parts[12])) : null;
                $numof = isset($parts[13]) ? trim($parts[13]) : null;

                Patrimonio::updateOrCreate(
                    ['NUPATRIMONIO' => $nupatrimonio],
                    [
                        'SITUACAO' => $situacao,
                        'MARCA' => $marca,
                        'CDLOCAL' => $cdlocal,
                        'MODELO' => $modelo,
                        'COR' => $cor,
                        'DTAQUISICAO' => $dtaquisicao,
                        'DEHISTORICO' => $dehistorico,
                        'CDMATRFUNCIONARIO' => $cdmatrfuncionario,
                        'CDPROJETO' => $cdprojeto,
                        'USUARIO' => $usuario,
                        'DTOPERACAO' => $dtoperacao,
                        'NUMOF' => $numof,
                        'FLCONFERIDO' => 'N',
                    ]
                );
                $count++;
            } catch (Exception $e) {
                $errors++;
                $this->warn("  ⚠️  Linha " . ($i + 1) . " ignorada: {$e->getMessage()}");
            }
        }

        $this->line("  ✓ $count registros importados, $errors erros ignorados");
    }

    /**
     * Importa dados de MOVPATRHISTORICO.TXT
     */
    private function importMovPatrHistorico($filepath)
    {
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $count = 0;
        $errors = 0;

        $dataStartLine = 2;

        for ($i = $dataStartLine; $i < count($lines); $i++) {
            try {
                $line = $lines[$i];
                $parts = preg_split('/\s{2,}/', trim($line));

                if (count($parts) < 5) continue;

                $nupatrim = (int)trim($parts[0]);
                $nuproj = (int)trim($parts[1]);
                $dtmovi = $this->parseDate(trim($parts[2]));
                $flmov = trim($parts[3]);
                $usuario = trim($parts[4]);
                $dtoperacao = isset($parts[5]) ? $this->parseDate(trim($parts[5])) : now();

                HistoricoMovimentacao::create([
                    'NUPATR' => $nupatrim,
                    'CODPROJ' => $nuproj,
                    'DTOPERACAO' => $dtoperacao,
                    'USUARIO' => $usuario,
                    'TIPO' => $flmov,
                ]);
                $count++;
            } catch (Exception $e) {
                $errors++;
                $this->warn("  ⚠️  Linha " . ($i + 1) . " ignorada: {$e->getMessage()}");
            }
        }

        $this->line("  ✓ $count registros importados, $errors erros ignorados");
    }

    /**
     * Converte data do formato DD/MM/YYYY para Y-m-d
     */
    private function parseDate($dateStr)
    {
        if (!$dateStr || strtolower($dateStr) === '<null>' || $dateStr === 'null') {
            return null;
        }

        try {
            $parts = explode('/', $dateStr);
            if (count($parts) === 3) {
                $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                $year = $parts[2];
                return "$year-$month-$day";
            }
        } catch (Exception $e) {
            return null;
        }

        return null;
    }
}
