<?php
/**
 * CONFIGURAÇÃO DE IMPORTAÇÃO - scripts/config-import.php
 * 
 * Este arquivo centraliza as configurações de caminho para importação
 * em diferentes ambientes (local, staging, produção)
 */

return [
    /**
     * AMBIENTES SUPORTADOS
     * local    = Máquina de desenvolvimento
     * server   = Servidor de produção (Kinghost)
     */
    'environment' => env('IMPORT_ENV', 'local'),
    
    /**
     * CAMINHOS DE ORIGEM DO ARQUIVO patrimonio.TXT
     */
    'source_paths' => [
        'local' => [
            'C:\\Users\\marketing\\Desktop\\Subir arquivos Kinghost\\patrimonio.TXT',
            // Alternativas se caminho acima falhar
            dirname(__DIR__) . '\\patrimonio.TXT',
        ],
        'server' => [
            '/home/plansul/Subir arquivos Kinghost/patrimonio.TXT',
            '/home/plansul/public_html/Subir arquivos Kinghost/patrimonio.TXT',
            '/var/www/plansul/Subir arquivos Kinghost/patrimonio.TXT',
            // Suporta arquivo colocado diretamente na raiz do projeto
            dirname(__DIR__) . '/patrimonio.TXT',
            // Adicione outros caminhos do servidor se necessário
        ]
    ],
    
    /**
     * DIRETÓRIO DE BACKUP
     * Será criado automaticamente se não existir
     */
    'backup_dir' => storage_path('backups'),
    
    /**
     * CONFIGURAÇÕES DE IMPORTAÇÃO
     */
    'import' => [
        // Fazer backup antes de importar
        'create_backup' => true,
        
        // Verificar relacionamentos antes de importar
        'validate_relationships' => true,
        
        // Número de linhas a processar por vez (0 = sem limite)
        'batch_size' => 0,
        
        // Registrar todas as transações
        'log_transactions' => true,
        
        // Diretório de logs
        'log_dir' => storage_path('logs/imports'),
    ],
    
    /**
     * DETECÇÃO AUTOMÁTICA DE AMBIENTE
     */
    'auto_detect' => true,
];
?>
