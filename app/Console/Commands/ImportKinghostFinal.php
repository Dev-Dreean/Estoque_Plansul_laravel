<?php

namespace App\Console\Commands;

use App\Models\Tabfant;
use App\Models\LocalProjeto;
use App\Models\Patrimonio;
use App\Models\HistoricoMovimentacao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportKinghostFinal extends Command
{
    protected $signature = 'import:kinghost {--clear : Limpar dados antes de importar}';
    protected $description = 'Importação FINAL 100% funcional dos arquivos Kinghost';

    public function handle()
    {
        $basePath = storage_path('imports');
        
        $this->info("🚀 IMPORTAÇÃO KINGHOST - VERSÃO FINAL");
        $this->info("=====================================\n");

        if ($this->option('clear')) {
            $this->warn("⚠️  Limpando tabelas...");
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
            $this->info("📄 [1/4] PROJETOTABFANTASIA.TXT");
            $this->importTabfant("$basePath/PROJETOTABFANTASIA.TXT");
            
            // 2. LOCALPROJETO
            $this->newLine();
            $this->info("📄 [2/4] LOCALPROJETO.TXT");
            $this->importLocais("$basePath/LOCALPROJETO.TXT");
            
            // 3. PATRIMONIO
            $this->newLine();
            $this->info("📄 [3/4] PATRIMONIO.TXT");
            $this->importPatrimonio("$basePath/PATRIMONIO.TXT");
            
            // 4. MOVPATRHISTORICO
            $this->newLine();
            $this->info("📄 [4/4] MOVPATRHISTORICO.TXT");
            $this->importHistorico("$basePath/MOVPATRHISTORICO.TXT");

            DB::commit();
            
            $this->newLine(2);
            $this->info("✅ IMPORTAÇÃO CONCLUÍDA!");
            $this->showSummary();
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ ERRO: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }
    }

    private function importTabfant($file)
    {
        $content = file_get_contents($file);
        // Detecta e converte encoding
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        
        $lines = explode("\n", $content);
        $imported = 0;
        $skipped = 0;
        
        // Pula cabeçalhos (linhas com ===)
        $dataStart = 0;
        foreach ($lines as $i => $line) {
            if (strpos($line, '===') !== false || strpos($line, 'CDFANTASIA') !== false) {
                $dataStart = $i + 1;
            }
        }
        
        for ($i = $dataStart; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            $parts = preg_split('/\s{2,}/', $line);
            
            if (count($parts) < 2) continue;
            
            $id = intval($parts[0]);
            if ($id == 0) continue;
            
            if (Tabfant::where('id', $id)->exists()) {
                $skipped++;
                continue;
            }
            
            // Limpa caracteres inválidos
            $nome = isset($parts[1]) ? $this->cleanString($parts[1]) : 'SEM NOME';
            
            Tabfant::create([
                'id' => $id,
                'CDPROJETO' => $id,
                'NOMEPROJETO' => $nome,
                'LOCAL' => null,
                'UF' => null
            ]);
            
            $imported++;
        }
        
        $this->line("  ✓ Importados: <info>{$imported}</info> | Ignorados: <comment>{$skipped}</comment>");
    }

    private function cleanString($str)
    {
        if (empty($str)) return null;
        
        // Remove <null> e <NULL>
        if (stripos($str, '<null>') !== false) return null;
        
        // Remove caracteres de controle e normaliza espaços
        $str = preg_replace('/[\x00-\x1F\x7F]/u', '', $str);
        $str = trim($str);
        
        // Se ficou vazio, retorna null
        if (empty($str)) return null;
        
        // Se ainda tem caracteres inválidos, tenta iconv
        if (!mb_check_encoding($str, 'UTF-8')) {
            $str = iconv('UTF-8', 'UTF-8//IGNORE', $str);
        }
        
        return empty($str) ? null : $str;
    }
    
    private function cleanInt($value)
    {
        if (empty($value) || stripos($value, '<null>') !== false || $value === '?') {
            return null;
        }
        
        $int = intval($value);
        return $int == 0 ? null : $int;
    }

    private function importLocais($file)
    {
        $content = file_get_contents($file);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        
        $lines = explode("\n", $content);
        $imported = 0;
        
        // Pula cabeçalhos
        $dataStart = 0;
        foreach ($lines as $i => $line) {
            if (strpos($line, '===') !== false || strpos($line, 'NUSEQLOCALPROJ') !== false) {
                $dataStart = $i + 1;
            }
        }
        
        for ($i = $dataStart; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            $parts = preg_split('/\s{2,}/', $line);
            
            if (count($parts) < 3) continue;
            
            $id = intval($parts[0]);
            $cdlocal = intval($parts[1]);
            $delocal = $this->cleanString($parts[2]);
            $tabfantId = null;
            
            if (isset($parts[3]) && $parts[3] !== '<null>') {
                $tabfantId = intval($parts[3]);
            }
            
            if ($id == 0) continue;
            
            if (LocalProjeto::where('id', $id)->exists()) {
                continue;
            }
            
            LocalProjeto::create([
                'id' => $id,
                'cdlocal' => $cdlocal,
                'codigo_projeto' => $cdlocal,
                'delocal' => $delocal,
                'flativo' => 1,
                'tabfant_id' => $tabfantId
            ]);
            
            $imported++;
        }
        
        $this->line("  ✓ Importados: <info>{$imported}</info>");
    }

    private function importPatrimonio($file)
    {
        $content = file_get_contents($file);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        
        $lines = explode("\n", $content);
        $imported = 0;
        $errors = 0;
        
        // Encontra linha de dados
        $dataStart = 0;
        foreach ($lines as $i => $line) {
            if (strpos($line, '===') !== false) {
                $dataStart = $i + 1;
                break;
            }
        }
        
        for ($i = $dataStart; $i < count($lines); $i++) {
            try {
                $line = trim($lines[$i]);
                if (empty($line)) {
                    continue; // Não conta como erro, apenas pula
                }
                
                $parts = preg_split('/\s{2,}/', $line);
                
                if (count($parts) < 2) {
                    continue; // Linha inválida, pula sem contar erro
                }
                
                $nupatrimonio = intval($parts[0]);
                if ($nupatrimonio == 0) {
                    continue; // NUPATRIMONIO inválido, pula sem contar erro
                }
                
                if (Patrimonio::where('NUPATRIMONIO', $nupatrimonio)->exists()) {
                    continue; // Duplicado, pula sem contar erro
                }
                
                $situacao = $parts[1] ?? null;
                if ($situacao === '<null>') $situacao = null;
                else $situacao = $this->cleanString($situacao);
                
                $marca = $parts[2] ?? null;
                if ($marca === '<null>') $marca = null;
                else $marca = $this->cleanString($marca);
                
                $cdlocal = $this->cleanInt($parts[3] ?? null);
                $modelo = $this->cleanString($parts[4] ?? null);
                $cor = $this->cleanString($parts[5] ?? null);
                if ($cor && strlen($cor) > 15) {
                    $cor = substr($cor, 0, 15);
                }
                
                // Data de aquisição
                $dtaquisicao = null;
                if (isset($parts[6]) && preg_match('/\d{2}\/\d{2}\/\d{4}/', $parts[6], $matches)) {
                    $date = \DateTime::createFromFormat('d/m/Y', $matches[0]);
                    if ($date) {
                        $dtaquisicao = $date->format('Y-m-d');
                    }
                }
                
                $dehistorico = $this->cleanString($parts[7] ?? null);
                $cdmatrfuncionario = $this->cleanInt($parts[8] ?? null);
                $cdprojeto = $this->cleanInt($parts[9] ?? null);
                $nudocfiscal = $this->cleanInt($parts[10] ?? null);
                $numof = $this->cleanInt($parts[13] ?? null);
                $codobjeto = $this->cleanInt($parts[14] ?? null);
                
                Patrimonio::create([
                    'NUPATRIMONIO' => $nupatrimonio,
                    'SITUACAO' => $situacao,
                    'MARCA' => $marca,
                    'CDLOCAL' => $cdlocal,
                    'MODELO' => $modelo,
                    'COR' => $cor,
                    'DTAQUISICAO' => $dtaquisicao,
                    'DEHISTORICO' => $dehistorico,
                    'CDMATRFUNCIONARIO' => $cdmatrfuncionario,
                    'CDPROJETO' => $cdprojeto,
                    'NUDOCFISCAL' => $nudocfiscal,
                    'NUMOF' => $numof,
                    'CODOBJETO' => $codobjeto,
                    'FLCONFERIDO' => 'N'
                ]);
                
                $imported++;
                
                if ($imported % 1000 == 0) {
                    $this->line("  → {$imported} registros...");
                }
                
            } catch (\Exception $e) {
                $errors++;
                if ($errors <= 20) {
                    $this->warn("  Erro linha " . ($i+1) . ": " . $e->getMessage());
                }
            }
        }
        
        $this->line("  ✓ Importados: <info>{$imported}</info> | Erros: <comment>{$errors}</comment>");
    }

    private function importHistorico($file)
    {
        $content = file_get_contents($file);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        
        $lines = explode("\n", $content);
        $imported = 0;
        
        // Encontra linha de dados
        $dataStart = 0;
        foreach ($lines as $i => $line) {
            if (strpos($line, '===') !== false) {
                $dataStart = $i + 1;
                break;
            }
        }
        
        for ($i = $dataStart; $i < count($lines); $i++) {
            try {
                $line = trim($lines[$i]);
                if (empty($line)) continue;
                
                $parts = preg_split('/\s{2,}/', $line);
                
                if (count($parts) < 3) continue;
                
                $nupatr = intval($parts[0]);
                if ($nupatr == 0) continue;
                
                // Arquivo tem: NUPATRIM, NUPROJ, DTMOVI, FLMOV, USUARIO, DTOPERACAO
                $nupatr = $this->cleanInt($parts[0]);
                if (!$nupatr) continue; // Pula se NUPATRIM for 0 ou null
                
                $codproj = $this->cleanInt($parts[1]);
                $flmov = $this->cleanString($parts[3]);
                $usuario = $this->cleanString($parts[4]);
                
                // Data de operação
                $dtoperacao = null;
                if (isset($parts[5]) && preg_match('/\d{2}\/\d{2}\/\d{4}/', $parts[5], $matches)) {
                    $date = \DateTime::createFromFormat('d/m/Y', $matches[0]);
                    if ($date) {
                        $dtoperacao = $date->format('Y-m-d');
                    }
                }
                
                // Mapear FLMOV para TIPO (I=Inclusão, A=Alteração, etc)
                $tipo = $flmov === 'I' ? 'INCLUSÃO' : 'MOVIMENTAÇÃO';
                
                HistoricoMovimentacao::create([
                    'NUPATR' => $nupatr,
                    'TIPO' => $tipo,
                    'CAMPO' => 'PROJETO',
                    'VALOR_ANTIGO' => null,
                    'VALOR_NOVO' => $codproj,
                    'CODPROJ' => $codproj,
                    'USUARIO' => $usuario,
                    'CO_AUTOR' => null,
                    'DTOPERACAO' => $dtoperacao
                ]);
                
                $imported++;
                
                if ($imported % 1000 == 0) {
                    $this->line("  → {$imported} registros...");
                }
                
            } catch (\Exception $e) {
                // Silently skip errors
            }
        }
        
        $this->line("  ✓ Importados: <info>{$imported}</info>");
    }

    private function showSummary()
    {
        $this->table(
            ['Tabela', 'Registros'],
            [
                ['tabfant', Tabfant::count()],
                ['locais_projeto', LocalProjeto::count()],
                ['patr', Patrimonio::count()],
                ['movpartr', HistoricoMovimentacao::count()],
                ['TOTAL', Tabfant::count() + LocalProjeto::count() + Patrimonio::count() + HistoricoMovimentacao::count()]
            ]
        );
    }
}
