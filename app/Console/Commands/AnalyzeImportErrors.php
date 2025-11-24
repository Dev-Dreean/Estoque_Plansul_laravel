<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AnalyzeImportErrors extends Command
{
    protected $signature = 'import:analyze {--path= : Caminho da pasta com os arquivos TXT}';

    protected $description = 'Analisa os arquivos TXT e identifica linhas com problemas';

    public function handle()
    {
        $basePath = $this->option('path') ?: storage_path('imports');
        
        if (!is_dir($basePath)) {
            $this->error("❌ Pasta não encontrada: $basePath");
            return 1;
        }

        $this->info('🔍 Analisando arquivos TXT para identificar erros...');
        $this->newLine();

        $files = [
            'PROJETOTABFANTASIA.TXT',
            'LOCALPROJETO.TXT',
            'PATRIMONIO.TXT',
            'MOVPATRHISTORICO.TXT',
        ];

        foreach ($files as $filename) {
            $filepath = $basePath . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($filepath)) {
                $this->analyzeFile($filename, $filepath);
                $this->newLine();
            }
        }

        return 0;
    }

    private function analyzeFile($filename, $filepath)
    {
        $this->info("📄 Analisando: $filename");
        $lines = file($filepath, FILE_IGNORE_NEW_LINES);
        
        $totalLines = count($lines);
        $this->line("   Total de linhas: $totalLines");
        
        // Identifica header
        $headerLine = 0;
        for ($i = 0; $i < count($lines); $i++) {
            if (strpos($lines[$i], '===') !== false) {
                $headerLine = $i;
                break;
            }
        }
        
        $dataStartLine = $headerLine + 1;
        $this->line("   Header na linha: " . ($headerLine + 1));
        $this->line("   Dados começam na linha: " . ($dataStartLine + 1));
        
        $problematicLines = [];

        // Analisa linhas de dados
        for ($i = $dataStartLine; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            if (empty(trim($line))) {
                continue;
            }

            // Split por 2+ espaços
            $parts = preg_split('/\s{2,}/', trim($line));
            $partCount = count($parts);

            // Validações específicas por arquivo
            $isProblematic = false;
            $reason = '';

            if ($filename === 'PROJETOTABFANTASIA.TXT') {
                if ($partCount < 4) {
                    $isProblematic = true;
                    $reason = "Esperado 4+ campos, encontrado $partCount";
                }
                // Valida CDFANTASIA (deve ser número)
                if (!ctype_digit(trim($parts[0]))) {
                    $isProblematic = true;
                    $reason = "CDFANTASIA não é número: '{$parts[0]}'";
                }
            }
            elseif ($filename === 'LOCALPROJETO.TXT') {
                if ($partCount < 3) {
                    $isProblematic = true;
                    $reason = "Esperado 3+ campos, encontrado $partCount";
                }
                // Valida NUSEQLOCALPROJ
                if (!ctype_digit(trim($parts[0]))) {
                    $isProblematic = true;
                    $reason = "NUSEQLOCALPROJ não é número: '{$parts[0]}'";
                }
                // Valida CDLOCAL
                if (!ctype_digit(trim($parts[1]))) {
                    $isProblematic = true;
                    $reason = "CDLOCAL não é número: '{$parts[1]}'";
                }
            }
            elseif ($filename === 'PATRIMONIO.TXT') {
                if ($partCount < 5) {
                    $isProblematic = true;
                    $reason = "Esperado 5+ campos, encontrado $partCount";
                }
                // Valida NUPATRIMONIO
                if (!ctype_digit(trim($parts[0]))) {
                    $isProblematic = true;
                    $reason = "NUPATRIMONIO não é número: '{$parts[0]}'";
                }
            }
            elseif ($filename === 'MOVPATRHISTORICO.TXT') {
                if ($partCount < 5) {
                    $isProblematic = true;
                    $reason = "Esperado 5+ campos, encontrado $partCount";
                }
            }

            if ($isProblematic) {
                $problematicLines[] = [
                    'linha' => $i + 1,
                    'campos' => $partCount,
                    'reason' => $reason,
                    'conteudo' => substr($line, 0, 100),
                ];
            }
        }

        if (empty($problematicLines)) {
            $this->info("   ✅ Nenhuma linha problemática encontrada!");
        } else {
            $this->warn("   ⚠️  Encontradas " . count($problematicLines) . " linhas com problemas:");
            $this->newLine();
            
            foreach ($problematicLines as $problem) {
                $this->line("   Linha " . $problem['linha'] . " (campos: " . $problem['campos'] . ")");
                $this->line("   └─ Razão: " . $problem['reason']);
                $this->line("   └─ Conteúdo: " . $problem['conteudo']);
                $this->newLine();
            }
        }
    }
}
