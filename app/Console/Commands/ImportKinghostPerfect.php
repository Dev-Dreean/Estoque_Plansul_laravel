<?php

namespace App\Console\Commands;

use App\Models\Tabfant;
use App\Models\LocalProjeto;
use App\Models\Patrimonio;
use App\Models\HistoricoMovimentacao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportKinghostPerfect extends Command
{
    protected $signature = 'import:kinghost-perfect {--clear : Limpar tabelas antes de importar}';
    protected $description = 'Importação 100% perfeita dos arquivos Kinghost em formato fixo';

    private $basePath;
    private $stats = [
        'total' => 0,
        'success' => 0,
        'errors' => 0,
        'skipped' => 0
    ];

    public function handle()
    {
        $this->basePath = storage_path('imports');
        
        $this->info("🚀 IMPORTAÇÃO PERFEITA KINGHOST");
        $this->info("================================\n");

        // Limpar tabelas se solicitado
        if ($this->option('clear')) {
            $this->warn("⚠️  Limpando tabelas existentes...");
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            HistoricoMovimentacao::truncate();
            Patrimonio::truncate();
            LocalProjeto::truncate();
            Tabfant::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->info("✓ Tabelas limpas\n");
        }

        DB::beginTransaction();
        
        try {
            // 1. PROJETOTABFANTASIA
            $this->newLine();
            $this->info("📄 [1/4] PROJETOTABFANTASIA.TXT");
            $this->importProjetoTabFantasia();
            
            // 2. LOCALPROJETO
            $this->newLine();
            $this->info("📄 [2/4] LOCALPROJETO.TXT");
            $this->importLocalProjeto();
            
            // 3. PATRIMONIO
            $this->newLine();
            $this->info("📄 [3/4] PATRIMONIO.TXT");
            $this->importPatrimonio();
            
            // 4. MOVPATRHISTORICO
            $this->newLine();
            $this->info("📄 [4/4] MOVPATRHISTORICO.TXT");
            $this->importMovPatrHistorico();

            DB::commit();
            
            $this->newLine(2);
            $this->info("✅ IMPORTAÇÃO CONCLUÍDA COM SUCESSO!");
            $this->displayStats();
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ ERRO: " . $e->getMessage());
            $this->error("Linha: " . $e->getLine());
            $this->error("Arquivo: " . $e->getFile());
            return 1;
        }
    }

    private function importProjetoTabFantasia()
    {
        $file = $this->basePath . '/PROJETOTABFANTASIA.TXT';
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        $imported = 0;
        $duplicates = 0;
        
        // Pula cabeçalhos (primeiras 3 linhas)
        for ($i = 3; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            // Formato: CDFANTASIA(10) NMFANTASIA(100) FLAT(1)
            $cdprojeto = intval(trim(substr($line, 0, 10)));
            $nomeprojeto = trim(substr($line, 10, 100));
            $flat = trim(substr($line, 110, 1));
            
            if ($cdprojeto == 0) continue;
            
            // Verifica se já existe
            if (Tabfant::where('CDPROJETO', $cdprojeto)->exists()) {
                $duplicates++;
                continue;
            }
            
            Tabfant::create([
                'id' => $cdprojeto,
                'CDPROJETO' => $cdprojeto,
                'NOMEPROJETO' => $nomeprojeto,
                'LOCAL' => $flat === '1' ? 'ATIVO' : 'INATIVO',
                'UF' => null
            ]);
            
            $imported++;
        }
        
        $this->line("  ✓ Importados: <info>{$imported}</info>");
        if ($duplicates > 0) {
            $this->line("  ⊘ Duplicados: <comment>{$duplicates}</comment>");
        }
        
        $this->stats['total'] += $imported;
        $this->stats['success'] += $imported;
        $this->stats['skipped'] += $duplicates;
    }

    private function importLocalProjeto()
    {
        $file = $this->basePath . '/LOCALPROJETO.TXT';
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        $imported = 0;
        $errors = 0;
        
        // Pula cabeçalhos (primeiras 3 linhas)
        for ($i = 3; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            // Formato: NUSEQLOCALPROJ(10) CDLOCAL(10) DELOCAL(100) CDFANTASIA(10) FLAT(7)
            $id = intval(trim(substr($line, 0, 10)));
            $cdlocal = intval(trim(substr($line, 10, 10)));
            $delocal = trim(substr($line, 20, 100));
            $cdfantasia = trim(substr($line, 120, 10));
            $flat = trim(substr($line, 130, 7));
            
            if ($id == 0) {
                $errors++;
                continue;
            }
            
            // Trata <null>
            if (strpos($cdfantasia, '<null>') !== false || trim($cdfantasia) === '') {
                $cdfantasia = null;
            } else {
                $cdfantasia = intval($cdfantasia);
            }
            
            // Verifica se já existe
            if (LocalProjeto::where('id', $id)->exists()) {
                continue;
            }
            
            LocalProjeto::create([
                'id' => $id,
                'cdlocal' => $cdlocal,
                'codigo_projeto' => $cdlocal,
                'delocal' => $delocal,
                'flativo' => 1,
                'tabfant_id' => $cdfantasia
            ]);
            
            $imported++;
        }
        
        $this->line("  ✓ Importados: <info>{$imported}</info>");
        if ($errors > 0) {
            $this->line("  ⚠ Linhas vazias: <comment>{$errors}</comment>");
        }
        
        $this->stats['total'] += $imported;
        $this->stats['success'] += $imported;
        $this->stats['errors'] += $errors;
    }

    private function importPatrimonio()
    {
        $file = $this->basePath . '/PATRIMONIO.TXT';
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        $imported = 0;
        $errors = 0;
        
        // Pula cabeçalhos (primeiras 3 linhas)
        for ($i = 3; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            try {
                // Posições fixas conforme arquivo original
                $nupatrimonio = intval(trim(substr($line, 0, 10)));
                
                if ($nupatrimonio == 0) {
                    $errors++;
                    continue;
                }
                
                // Extrai todos os campos com posições fixas
                $cdlocal = $this->extractInt($line, 10, 10);
                $dssituacao = $this->extractString($line, 20, 10);
                $cdfantasia = $this->extractInt($line, 30, 10);
                $nuseqlocalproj = $this->extractInt($line, 40, 10);
                $cdtipopatr = $this->extractInt($line, 50, 10);
                $dedescricao = $this->extractString($line, 60, 10);
                $dehistorico = $this->extractString($line, 70, 100);
                $deobjeto = $this->extractString($line, 170, 100);
                $dtaquisicao = $this->extractDate($line, 270, 12);
                $dtgarinicio = $this->extractDate($line, 282, 12);
                $dtgarfim = $this->extractDate($line, 294, 12);
                $dtultmanuten = $this->extractDate($line, 306, 12);
                $dtbaixa = $this->extractDate($line, 318, 12);
                $flsituacao = $this->extractString($line, 330, 1);
                $demarca = $this->extractString($line, 331, 100);
                $demodelo = $this->extractString($line, 431, 100);
                $deobservacao = $this->extractString($line, 531, 100);
                $nrnota = $this->extractInt($line, 631, 10);
                $nuseqpatrold = $this->extractInt($line, 641, 10);
                $nupatrtotem = $this->extractInt($line, 651, 10);
                $stlocal = $this->extractString($line, 661, 10);
                $flstatus = $this->extractString($line, 671, 100);
                $deimagem = $this->extractString($line, 771, 100);
                $nmfantasia = $this->extractString($line, 871, 100);
                
                // Verifica se já existe
                if (Patrimonio::where('nupatrimonio', $nupatrimonio)->exists()) {
                    continue;
                }
                
                Patrimonio::create([
                    'nupatrimonio' => $nupatrimonio,
                    'cdlocal' => $cdlocal,
                    'dssituacao' => $dssituacao,
                    'cdfantasia' => $cdfantasia,
                    'nuseqlocalproj' => $nuseqlocalproj,
                    'cdtipopatr' => $cdtipopatr,
                    'dedescricao' => $dedescricao,
                    'dehistorico' => $dehistorico,
                    'deobjeto' => $deobjeto,
                    'dtaquisicao' => $dtaquisicao,
                    'dtgarinicio' => $dtgarinicio,
                    'dtgarfim' => $dtgarfim,
                    'dtultmanuten' => $dtultmanuten,
                    'dtbaixa' => $dtbaixa,
                    'flsituacao' => $flsituacao,
                    'demarca' => $demarca,
                    'demodelo' => $demodelo,
                    'deobservacao' => $deobservacao,
                    'nrnota' => $nrnota,
                    'nuseqpatrold' => $nuseqpatrold,
                    'nupatrtotem' => $nupatrtotem,
                    'stlocal' => $stlocal,
                    'flstatus' => $flstatus,
                    'deimagem' => $deimagem,
                    'nmfantasia' => $nmfantasia
                ]);
                
                $imported++;
                
                // Progress bar
                if ($imported % 500 == 0) {
                    $this->line("  → Processados: {$imported}...");
                }
                
            } catch (\Exception $e) {
                $errors++;
                if ($errors < 5) {
                    $this->warn("  ⚠ Erro linha " . ($i + 1) . ": " . $e->getMessage());
                }
            }
        }
        
        $this->line("  ✓ Importados: <info>{$imported}</info>");
        if ($errors > 0) {
            $this->line("  ⚠ Erros: <comment>{$errors}</comment>");
        }
        
        $this->stats['total'] += $imported;
        $this->stats['success'] += $imported;
        $this->stats['errors'] += $errors;
    }

    private function importMovPatrHistorico()
    {
        $file = $this->basePath . '/MOVPATRHISTORICO.TXT';
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        $imported = 0;
        $errors = 0;
        
        // Pula cabeçalhos (primeiras 3 linhas)
        for ($i = 3; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            try {
                $nupatrimonio = intval(trim(substr($line, 0, 10)));
                
                if ($nupatrimonio == 0) {
                    $errors++;
                    continue;
                }
                
                $cdlocal = $this->extractInt($line, 10, 10);
                $dssituacao = $this->extractString($line, 20, 10);
                $dtmovimentacao = $this->extractDate($line, 30, 12);
                $deobservacao = $this->extractString($line, 42, 255);
                
                HistoricoMovimentacao::create([
                    'nupatrimonio' => $nupatrimonio,
                    'cdlocal' => $cdlocal,
                    'dssituacao' => $dssituacao,
                    'dtmovimentacao' => $dtmovimentacao,
                    'deobservacao' => $deobservacao
                ]);
                
                $imported++;
                
                if ($imported % 500 == 0) {
                    $this->line("  → Processados: {$imported}...");
                }
                
            } catch (\Exception $e) {
                $errors++;
            }
        }
        
        $this->line("  ✓ Importados: <info>{$imported}</info>");
        if ($errors > 0) {
            $this->line("  ⚠ Erros: <comment>{$errors}</comment>");
        }
        
        $this->stats['total'] += $imported;
        $this->stats['success'] += $imported;
        $this->stats['errors'] += $errors;
    }

    private function extractInt($line, $start, $length)
    {
        $value = trim(substr($line, $start, $length));
        
        if ($value === '' || strpos($value, '<null>') !== false || strpos($value, '?') !== false) {
            return null;
        }
        
        $int = intval($value);
        return $int == 0 ? null : $int;
    }

    private function extractString($line, $start, $length)
    {
        $value = trim(substr($line, $start, $length));
        
        if ($value === '' || strpos($value, '<null>') !== false) {
            return null;
        }
        
        return $value;
    }

    private function extractDate($line, $start, $length)
    {
        $value = trim(substr($line, $start, $length));
        
        if ($value === '' || strpos($value, '<null>') !== false || strpos($value, '?') !== false) {
            return null;
        }
        
        // Tenta parse DD/MM/YYYY
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $value, $matches)) {
            try {
                return Carbon::createFromFormat('d/m/Y', $matches[0])->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
        
        // Tenta parse YYYY-MM-DD
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $value, $matches)) {
            return $matches[0];
        }
        
        return null;
    }

    private function displayStats()
    {
        $this->table(
            ['Métrica', 'Quantidade'],
            [
                ['Total Processado', $this->stats['total']],
                ['✓ Sucesso', '<info>' . $this->stats['success'] . '</info>'],
                ['⚠ Erros', '<comment>' . $this->stats['errors'] . '</comment>'],
                ['⊘ Ignorados', '<comment>' . $this->stats['skipped'] . '</comment>'],
            ]
        );
        
        // Resumo por tabela
        $this->newLine();
        $this->info("📊 RESUMO POR TABELA:");
        $this->table(
            ['Tabela', 'Registros'],
            [
                ['tabfant', Tabfant::count()],
                ['locais_projeto', LocalProjeto::count()],
                ['patr', Patrimonio::count()],
                ['movpartr', HistoricoMovimentacao::count()],
            ]
        );
    }
}
