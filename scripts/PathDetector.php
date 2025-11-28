<?php
/**
 * UTILITÁRIO DE DETECÇÃO DE CAMINHO
 * scripts/PathDetector.php
 * 
 * Detecta automaticamente o caminho correto do arquivo patrimonio.TXT
 * em diferentes ambientes (local, servidor, alternativas)
 */

class PathDetector {
    private $config;
    private $environment;
    
    public function __construct() {
        $this->config = require __DIR__ . '/config-import.php';
        $this->environment = $this->detectEnvironment();
    }
    
    /**
     * Detecta o ambiente atual (local vs servidor)
     */
    private function detectEnvironment() {
        if (defined('LARAVEL_START')) {
            $env = env('IMPORT_ENV');
            if ($env) return $env;
        }
        
        // Heurística: se está em /home/plansul, é servidor
        if (strpos(__DIR__, '/home/plansul') !== false) {
            return 'server';
        }
        
        // Se está em C:\Users, é local
        if (strpos(__DIR__, 'C:\\Users') !== false) {
            return 'local';
        }
        
        // Se tem C:\ é Windows (local)
        if (strpos(__DIR__, 'C:\\') === 0) {
            return 'local';
        }
        
        // Padrão: servidor
        return 'server';
    }
    
    /**
     * Encontra o arquivo patrimonio.TXT
     * Retorna [bool, string] => [encontrado, caminho_ou_erro]
     */
    public function findPatrimonioFile() {
        echo "🔍 Detectando ambiente...\n";
        echo "📍 Ambiente: " . strtoupper($this->environment) . "\n";
        echo "🗂️  Procurando arquivo patrimonio.TXT...\n\n";
        
        $caminhos = $this->config['source_paths'][$this->environment] ?? [];
        
        if (empty($caminhos)) {
            return [false, "❌ Nenhum caminho configurado para ambiente: {$this->environment}"];
        }
        
        foreach ($caminhos as $i => $caminho) {
            $numero = $i + 1;
            echo "   [$numero] Testando: $caminho\n";
            
            if (file_exists($caminho)) {
                $tamanho = filesize($caminho);
                $tamanhoMB = round($tamanho / (1024 * 1024), 2);
                
                echo "\n✅ ARQUIVO ENCONTRADO!\n";
                echo "   Caminho: $caminho\n";
                echo "   Tamanho: {$tamanhoMB} MB\n\n";
                
                return [true, $caminho];
            }
        }
        
        return [false, $this->gerarMensagemErro($caminhos)];
    }
    
    /**
     * Gera mensagem de erro detalhada
     */
    private function gerarMensagemErro($caminhos) {
        $msg = "❌ ARQUIVO NÃO ENCONTRADO em nenhum dos caminhos testados:\n\n";
        
        foreach ($caminhos as $i => $caminho) {
            $msg .= sprintf("   [%d] %s\n", $i + 1, $caminho);
        }
        
        $msg .= "\n⚠️  INSTRUÇÕES PARA {$this->environment}:\n";
        
        if ($this->environment === 'local') {
            $msg .= <<<'EOT'
   1. Verifique se o arquivo patrimonio.TXT está em:
      C:\Users\marketing\Desktop\Subir arquivos Kinghost\

   2. Se o caminho for diferente, edite scripts/config-import.php
      na seção 'source_paths' > 'local'

   3. Ou passe o caminho como argumento:
      php scripts/import_patrimonio_completo.php --arquivo="C:\Seu\Caminho\patrimonio.TXT"
EOT;
        } else {
            $msg .= <<<'EOT'
   1. Envie o arquivo patrimonio.TXT para o servidor em:
      /home/plansul/Subir arquivos Kinghost/patrimonio.TXT

   2. Via SFTP ou SCP usando credenciais de acesso

   3. Se o diretório não existir, crie com:
      mkdir -p "/home/plansul/Subir arquivos Kinghost"

   4. Se o caminho for diferente, edite scripts/config-import.php
      na seção 'source_paths' > 'server'
      
   5. Ou passe o caminho como argumento:
      php scripts/import_patrimonio_completo.php --arquivo="/seu/caminho/patrimonio.TXT"
EOT;
        }
        
        return $msg;
    }
    
    /**
     * Obtém o diretório de backup
     */
    public function getBackupDir() {
        $backupDir = $this->config['backup_dir'];
        
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }
        
        return $backupDir;
    }
    
    /**
     * Obtém configurações de importação
     */
    public function getImportConfig() {
        return $this->config['import'];
    }
    
    /**
     * Obtém o ambiente detectado
     */
    public function getEnvironment() {
        return $this->environment;
    }
}

?>
