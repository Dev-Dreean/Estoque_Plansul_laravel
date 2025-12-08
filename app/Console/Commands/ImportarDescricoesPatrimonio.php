<?php

namespace App\Console\Commands;

use App\Models\Patrimonio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportarDescricoesPatrimonio extends Command
{
    protected $signature = 'patrimonios:importar-descricoes 
                            {arquivo? : Caminho do arquivo TXT (padrão: PATRIMONIO.txt no Desktop)}
                            {--dry-run : Simular sem salvar no banco}
                            {--force : Atualizar TODOS mesmo os que já têm descrição}';

    protected $description = 'Importa descrições (DEPATRIMONIO) do arquivo TXT original';

    public function handle()
    {
        $this->info('🚀 [IMPORTAR DESCRIÇÕES] Iniciando importação de DEPATRIMONIO');
        $this->newLine();

        // Caminho do arquivo
        $arquivo = $this->argument('arquivo') ?? 'C:\\Users\\marketing\\Desktop\\PATRIMONIO.txt';
        
        if (!file_exists($arquivo)) {
            $this->error("❌ Arquivo não encontrado: $arquivo");
            return 1;
        }

        $this->info("📄 Arquivo: $arquivo");
        
        // Criar backup antes de modificar
        if (!$this->option('dry-run')) {
            $backupFile = storage_path('backups/patr_before_import_descricoes_' . date('Y_m_d_His') . '.json');
            
            $this->info('💾 Criando backup dos patrimônios que serão atualizados...');
            
            // Backup baseado em --force
            if ($this->option('force')) {
                $patrimoniosParaBackup = Patrimonio::all();
            } else {
                $patrimoniosParaBackup = Patrimonio::whereNull('DEPATRIMONIO')
                    ->orWhere('DEPATRIMONIO', '')
                    ->get();
            }
            
            if (!is_dir(dirname($backupFile))) {
                mkdir(dirname($backupFile), 0755, true);
            }
            
            file_put_contents($backupFile, json_encode($patrimoniosParaBackup->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("✅ Backup criado: $backupFile (" . $patrimoniosParaBackup->count() . " registros)");
            $this->newLine();
        }

        // Ler arquivo
        $this->info('📖 Lendo arquivo...');
        $conteudo = file_get_contents($arquivo);
        
        // Detectar encoding (provavelmente CP1252 ou ISO-8859-1)
        $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'Windows-1252');
        
        $linhas = explode("\n", $conteudo);
        $this->info("📊 Total de linhas no arquivo: " . count($linhas));
        $this->newLine();

        // Pular as primeiras 2 linhas (cabeçalho e separador)
        $linhasPatrimonios = array_slice($linhas, 2);

        $totalProcessados = 0;
        $totalAtualizados = 0;
        $totalErros = 0;
        $patrimoniosAtualizados = [];

        $this->info('🔄 Processando patrimônios...');
        $this->newLine();

        foreach ($linhasPatrimonios as $linha) {
            $linha = trim($linha);
            
            // Pular linhas vazias
            if (empty($linha)) {
                continue;
            }

            // Pular linhas de separador (============)
            if (strpos($linha, '====') !== false) {
                continue;
            }

            // Extrair NUPATRIMONIO (primeiro campo)
            // Dividir linha por múltiplos espaços (2 ou mais)
            $colunas = preg_split('/\s{2,}/', $linha);
            
            // Ignorar se não tiver colunas suficientes
            if (count($colunas) < 2) {
                continue;
            }

            $nupatrimonio = trim($colunas[0]);
            
            // Verificar se é número válido
            if (!is_numeric($nupatrimonio)) {
                continue;
            }

            // A descrição SEMPRE está na ÚLTIMA coluna
            $depatrimonio = trim($colunas[count($colunas) - 1]);

            // Ignorar se DEPATRIMONIO estiver vazio ou for "<null>"
            if (empty($depatrimonio) || $depatrimonio === '<null>') {
                continue;
            }

            $totalProcessados++;

            // Buscar patrimônio no banco
            $patrimonio = Patrimonio::where('NUPATRIMONIO', $nupatrimonio)->first();

            if (!$patrimonio) {
                $this->warn("⚠️  Patrimônio #{$nupatrimonio} não encontrado no banco");
                $totalErros++;
                continue;
            }

            // Verificar se já tem descrição (a menos que --force esteja ativo)
            if (!$this->option('force') && !empty($patrimonio->DEPATRIMONIO)) {
                continue;
            }

            // Verificar se a descrição é diferente (evitar updates desnecessários)
            if ($patrimonio->DEPATRIMONIO === $depatrimonio) {
                continue;
            }

            // Atualizar
            $patrimoniosAtualizados[] = [
                'NUPATRIMONIO' => $nupatrimonio,
                'DEPATRIMONIO_ANTIGO' => $patrimonio->DEPATRIMONIO,
                'DEPATRIMONIO_NOVO' => $depatrimonio,
                'NUSEQPATR' => $patrimonio->NUSEQPATR
            ];

            if (!$this->option('dry-run')) {
                $patrimonio->DEPATRIMONIO = $depatrimonio;
                $patrimonio->save();
                
                $antigoTxt = empty($patrimonio->DEPATRIMONIO) ? 'VAZIO' : "'{$patrimonio->DEPATRIMONIO}'";
                $this->info("✅ Patrimônio #{$nupatrimonio}: $antigoTxt → '{$depatrimonio}'");
            } else {
                $antigoTxt = empty($patrimonio->DEPATRIMONIO) ? 'VAZIO' : "'{$patrimonio->DEPATRIMONIO}'";
                $this->info("🔍 [DRY-RUN] Patrimônio #{$nupatrimonio}: $antigoTxt → '{$depatrimonio}'");
            }

            $totalAtualizados++;
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('📊 RESUMO DA IMPORTAÇÃO');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->line("📝 Total processados: {$totalProcessados}");
        $this->line("✅ Total atualizados: {$totalAtualizados}");
        $this->line("❌ Total erros: {$totalErros}");
        
        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('⚠️  MODO DRY-RUN: Nenhuma alteração foi salva no banco!');
            $this->info('💡 Execute sem --dry-run para aplicar as alterações.');
        }

        $this->newLine();

        return 0;
    }
}
