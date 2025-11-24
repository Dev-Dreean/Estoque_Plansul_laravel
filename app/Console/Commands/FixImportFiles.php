<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixImportFiles extends Command
{
    protected $signature = 'import:fix-files {--path= : Caminho da pasta com os arquivos TXT}';

    protected $description = 'Corrige linhas quebradas nos arquivos TXT antes da importação';

    public function handle()
    {
        $basePath = $this->option('path') ?: storage_path('imports');
        
        if (!is_dir($basePath)) {
            $this->error("❌ Pasta não encontrada: $basePath");
            return 1;
        }

        $this->info('🔧 Corrigindo arquivos TXT quebrados...');
        $this->newLine();

        // Foca no PATRIMONIO.TXT que tem os problemas
        $file = 'PATRIMONIO.TXT';
        $filepath = $basePath . DIRECTORY_SEPARATOR . $file;

        if (!file_exists($filepath)) {
            $this->error("❌ Arquivo não encontrado: $file");
            return 1;
        }

        $this->fixPatrimonioFile($filepath);

        $this->info('✅ Arquivos corrigidos com sucesso!');
        return 0;
    }

    private function fixPatrimonioFile($filepath)
    {
        $this->info("📄 Processando: PATRIMONIO.TXT");
        
        $content = file_get_contents($filepath);
        
        // Encontra o padrão de linhas quebradas
        // Linhas que começam com espaços ou com descrição ao invés de número
        
        $lines = file($filepath, FILE_IGNORE_NEW_LINES);
        
        // Encontra header
        $headerLine = 0;
        for ($i = 0; $i < count($lines); $i++) {
            if (strpos($lines[$i], '===') !== false) {
                $headerLine = $i;
                break;
            }
        }
        
        $dataStartLine = $headerLine + 1;
        
        $fixedLines = [];
        
        // Copia header
        for ($i = 0; $i <= $dataStartLine; $i++) {
            $fixedLines[] = $lines[$i];
        }
        
        $currentRecord = null;
        
        // Processa dados
        for ($i = $dataStartLine + 1; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            // Verifica se é linha de continuação (não começa com número)
            if (!empty(trim($line))) {
                $firstChar = trim($line)[0] ?? '';
                
                // Se começa com número ou é uma continuação de registro
                if (ctype_digit($firstChar) || preg_match('/^\d+\s/', trim($line))) {
                    // É um novo registro
                    if ($currentRecord !== null) {
                        $fixedLines[] = $currentRecord;
                    }
                    $currentRecord = $line;
                } else {
                    // É continuação do registro anterior
                    if ($currentRecord !== null) {
                        // Tira espaços e junta na mesma linha
                        $currentRecord .= ' ' . trim($line);
                    }
                }
            }
        }
        
        // Adiciona último registro
        if ($currentRecord !== null) {
            $fixedLines[] = $currentRecord;
        }
        
        // Salva arquivo corrigido com backup
        $backupFile = $filepath . '.backup';
        copy($filepath, $backupFile);
        $this->line("  ✓ Backup criado: $backupFile");
        
        // Escreve arquivo corrigido
        file_put_contents($filepath, implode("\n", $fixedLines));
        
        $this->line("  ✓ Arquivo corrigido");
        $this->line("  ✓ Linhas originais: " . count($lines));
        $this->line("  ✓ Linhas corrigidas: " . count($fixedLines));
    }
}
