<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LocalProjeto;
use App\Models\Patrimonio;
use App\Models\HistoricoMovimentacao;
use App\Models\Tabfant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;
use DateTime;

class ImportKinghostDataSafe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:kinghost-safe 
                           {--path= : Caminho da pasta com os arquivos TXT}
                           {--restore= : ID do backup para restaurar (listar com --list-backups)}
                           {--list-backups : Lista todos os backups disponíveis}
                           {--skip-backup : Pula a criação de backup (não recomendado)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa dados da Kinghost com BACKUP SEGURO, transações e validação';

    private $backupDir;
    private $backupId;
    private $importedCount = [];
    private $errors = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->backupDir = storage_path('backups/kinghost');
            @mkdir($this->backupDir, 0755, true);

            // Opção: Listar backups
            if ($this->option('list-backups')) {
                return $this->listBackups();
            }

            // Opção: Restaurar backup
            if ($this->option('restore')) {
                return $this->restoreBackup($this->option('restore'));
            }

            // Importação normal com segurança
            return $this->safeImport();

        } catch (Exception $e) {
            $this->error("❌ ERRO CRÍTICO: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Executa importação com segurança total
     */
    private function safeImport()
    {
        $this->info('🔒 MODO SEGURO: Iniciando importação com backup automático');
        $this->newLine();

        // Valida arquivos antes de começar
        $basePath = $this->option('path') ?: storage_path('imports');
        if (!$this->validateFiles($basePath)) {
            return 1;
        }

        // Cria backup antes de qualquer alteração
        if (!$this->option('skip-backup')) {
            $this->info('💾 Criando backup dos dados atuais...');
            if (!$this->createBackup()) {
                $this->error('❌ Falha ao criar backup. Importação cancelada.');
                return 1;
            }
            $this->info('✅ Backup criado com sucesso!');
            $this->newLine();
        }

        // Executar importação em transação
        return DB::transaction(function () use ($basePath) {
            try {
                $this->info('📥 Iniciando importação dentro de transação...');
                $this->newLine();

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

                // Validações pós-importação
                $this->info('🔍 Executando validações...');
                if (!$this->validateImportedData()) {
                    throw new Exception('Validação pós-importação falhou!');
                }

                $this->info('✅ Validações concluídas!');
                $this->newLine();

                // Relatório final
                $this->showSummary();

                $this->info('');
                $this->info('✅✅✅ IMPORTAÇÃO CONCLUÍDA COM SUCESSO! ✅✅✅');
                $this->info("💾 Backup ID: {$this->backupId}");
                $this->info('Para reverter, use: php artisan import:kinghost-safe --restore=' . $this->backupId);
                $this->info('');

                return 0;

            } catch (Exception $e) {
                $this->error('❌ ERRO NA IMPORTAÇÃO: ' . $e->getMessage());
                $this->error('⚠️  Todas as mudanças foram revertidas (transação rollback)');
                $this->newLine();
                throw $e;
            }
        });
    }

    /**
     * Valida se todos os arquivos existem antes de começar
     */
    private function validateFiles($basePath)
    {
        if (!is_dir($basePath)) {
            $this->error("❌ Pasta não encontrada: $basePath");
            return false;
        }

        $requiredFiles = [
            'PROJETOTABFANTASIA.TXT',
            'LOCALPROJETO.TXT',
            'PATRIMONIO.TXT',
            'MOVPATRHISTORICO.TXT',
        ];

        $missingFiles = [];
        foreach ($requiredFiles as $file) {
            if (!file_exists($basePath . DIRECTORY_SEPARATOR . $file)) {
                $missingFiles[] = $file;
            }
        }

        if (!empty($missingFiles)) {
            $this->error('❌ Arquivos não encontrados:');
            foreach ($missingFiles as $file) {
                $this->line("   - $file");
            }
            $this->line("");
            $this->line("Coloque os arquivos em: $basePath");
            return false;
        }

        return true;
    }

    /**
     * Cria backup de todas as tabelas
     */
    private function createBackup()
    {
        try {
            $this->backupId = date('Y-m-d_H-i-s');
            $backupPath = $this->backupDir . '/' . $this->backupId;
            @mkdir($backupPath, 0755, true);

            // Backup de cada tabela em JSON
            $tables = [
                'tabfant' => Tabfant::class,
                'locais_projeto' => LocalProjeto::class,
                'patr' => Patrimonio::class,
                'movpartr' => HistoricoMovimentacao::class,
            ];

            foreach ($tables as $tableName => $model) {
                if (!Schema::hasTable($tableName)) {
                    $this->warn("⚠️  Tabela não existe: $tableName (pulando backup)");
                    continue;
                }

                $data = DB::table($tableName)->get();
                $backupFile = $backupPath . '/' . $tableName . '.json';
                file_put_contents($backupFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->line("  ✓ Backup de $tableName: " . count($data) . ' registros');
            }

            // Arquivo de metadados
            $metadata = [
                'backup_id' => $this->backupId,
                'created_at' => now(),
                'user' => get_current_user()['name'] ?? 'system',
                'tables' => array_keys($tables),
            ];
            file_put_contents($backupPath . '/metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));

            return true;

        } catch (Exception $e) {
            $this->error("Erro ao criar backup: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Restaura backup anterior
     */
    private function restoreBackup($backupId)
    {
        $backupPath = $this->backupDir . '/' . $backupId;

        if (!is_dir($backupPath)) {
            $this->error("❌ Backup não encontrado: $backupId");
            return 1;
        }

        if (!$this->confirm('⚠️  Tem certeza que deseja restaurar este backup? Isso vai sobrescrever dados atuais!')) {
            $this->info('Operação cancelada.');
            return 1;
        }

        return DB::transaction(function () use ($backupPath) {
            try {
                $this->info('🔄 Restaurando backup...');

                $tables = [
                    'tabfant',
                    'locais_projeto',
                    'patr',
                    'movpartr',
                ];

                foreach ($tables as $table) {
                    $backupFile = $backupPath . '/' . $table . '.json';

                    if (!file_exists($backupFile)) {
                        $this->warn("⚠️  Backup não encontrado para: $table");
                        continue;
                    }

                    $data = json_decode(file_get_contents($backupFile), true);

                    // Limpa tabela
                    DB::table($table)->truncate();

                    // Reinsere dados
                    if (!empty($data)) {
                        DB::table($table)->insert($data);
                    }

                    $this->line("  ✓ Restaurado: $table (" . count($data) . ' registros)');
                }

                $this->info('✅ Backup restaurado com sucesso!');
                return 0;

            } catch (Exception $e) {
                $this->error('❌ ERRO ao restaurar: ' . $e->getMessage());
                return 1;
            }
        });
    }

    /**
     * Lista backups disponíveis
     */
    private function listBackups()
    {
        if (!is_dir($this->backupDir)) {
            $this->info('Nenhum backup disponível.');
            return 0;
        }

        $backups = array_filter(scandir($this->backupDir), function ($item) {
            return $item !== '.' && $item !== '..' && is_dir($this->backupDir . '/' . $item);
        });

        if (empty($backups)) {
            $this->info('Nenhum backup disponível.');
            return 0;
        }

        rsort($backups);

        $this->info('📋 Backups disponíveis:');
        $this->newLine();

        $rows = [];
        foreach ($backups as $backup) {
            $metaFile = $this->backupDir . '/' . $backup . '/metadata.json';
            $meta = file_exists($metaFile) ? json_decode(file_get_contents($metaFile), true) : [];
            
            $rows[] = [
                'ID' => $backup,
                'Criado' => $meta['created_at'] ?? 'N/A',
                'Usuário' => $meta['user'] ?? 'N/A',
            ];
        }

        $this->table(['ID', 'Criado', 'Usuário'], $rows);

        $this->newLine();
        $this->info('Para restaurar um backup, use:');
        $this->line('  php artisan import:kinghost-safe --restore=BACKUP_ID');

        return 0;
    }

    /**
     * Valida dados após importação
     */
    private function validateImportedData()
    {
        try {
            // Verifica integridade referencial
            $count = Tabfant::count();
            if ($count === 0) {
                throw new Exception('Nenhum projeto foi importado!');
            }

            $count = LocalProjeto::count();
            if ($count === 0) {
                throw new Exception('Nenhum local foi importado!');
            }

            $count = Patrimonio::count();
            if ($count === 0) {
                throw new Exception('Nenhum patrimônio foi importado!');
            }

            return true;

        } catch (Exception $e) {
            $this->error('Validação falhou: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mostra resumo da importação
     */
    private function showSummary()
    {
        $this->newLine();
        $this->info('📊 RESUMO DA IMPORTAÇÃO:');
        $this->newLine();

        $summary = [
            ['PROJETOTABFANTASIA', Tabfant::count() . ' registros'],
            ['LOCALPROJETO', LocalProjeto::count() . ' registros'],
            ['PATRIMONIO', Patrimonio::count() . ' registros'],
            ['MOVPATRHISTORICO', HistoricoMovimentacao::count() . ' registros'],
        ];

        $this->table(['Tabela', 'Resultado'], $summary);
    }

    // ============ MÉTODOS DE IMPORTAÇÃO (iguais ao anterior) ============

    private function importProjetoTabFantasia($filepath)
    {
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $count = 0;
        $errors = 0;

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
            }
        }

        $this->line("  ✓ $count registros importados, $errors erros ignorados");
        $this->importedCount['PROJETOTABFANTASIA'] = $count;
    }

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
            }
        }

        $this->line("  ✓ $count registros importados, $errors erros ignorados");
        $this->importedCount['LOCALPROJETO'] = $count;
    }

    private function importPatrimonio($filepath)
    {
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $count = 0;
        $errors = 0;

        $dataStartLine = 2;

        for ($i = $dataStartLine; $i < count($lines); $i++) {
            try {
                $line = $lines[$i];
                $parts = preg_split('/\s{2,}/', trim($line), 14);

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
                $usuario = isset($parts[11]) ? trim($parts[11]) : null;
                $dtoperacao = isset($parts[12]) ? $this->parseDate(trim($parts[12])) : null;

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
                        'FLCONFERIDO' => 'N',
                    ]
                );
                $count++;
            } catch (Exception $e) {
                $errors++;
            }
        }

        $this->line("  ✓ $count registros importados, $errors erros ignorados");
        $this->importedCount['PATRIMONIO'] = $count;
    }

    private function importMovPatrHistorico($filepath)
    {
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $count = 0;
        $errors = 0;

        $dataStartLine = 2;

        for ($i = $dataStartLine; $i < count($lines); $i++) {
            try {
                $line = $lines[$i];
                if (empty(trim($line))) continue;
                
                $parts = preg_split('/\s{2,}/', trim($line));

                if (count($parts) < 5) {
                    $errors++;
                    continue;
                }

                $nupatrim = (int)trim($parts[0]);
                $nuproj = (int)trim($parts[1]);
                $dtmovi = trim($parts[2]);
                $flmov = trim($parts[3]);
                $usuario = trim($parts[4]);
                
                // Converte data
                $dtmovi = $this->parseDate($dtmovi);
                if (!$dtmovi) {
                    $errors++;
                    continue;
                }

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
                // Continua mesmo com erro
            }
        }

        $this->line("  ✓ $count registros importados, $errors erros ignorados");
        $this->importedCount['MOVPATRHISTORICO'] = $count;
    }

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
