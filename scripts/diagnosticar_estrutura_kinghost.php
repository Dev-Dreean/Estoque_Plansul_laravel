<?php
/**
 * DIAGNÓSTICO: Validar estrutura de colunas KingHost
 * one-off: Executar apenas para diagnóstico
 */

$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        $parts = explode('=', $line, 2);
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        
        // Remove quotes if present
        if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
            (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
            $value = substr($value, 1, -1);
        }
        
        // Handle ${VAR} references
        $value = preg_replace_callback('/\$\{([^}]+)\}/', function($m) {
            return getenv($m[1]) ?: '';
        }, $value);
        
        putenv("$key=$value");
    }
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔍 DIAGNÓSTICO: Estrutura KingHost\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// KingHost Funcionários
try {
    $kh_func = new PDO(
        'mysql:host=' . getenv('FUNCIONARIOS_SOURCE_HOST') . ';dbname=' . getenv('FUNCIONARIOS_SOURCE_DB'),
        getenv('FUNCIONARIOS_SOURCE_USER'),
        getenv('FUNCIONARIOS_SOURCE_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    
    echo "✅ CONECTADO: KingHost Funcionários (plansul104)\n\n";
    
    echo "📋 COLUNAS DA TABELA 'funcionarios':\n";
    $cols = $kh_func->query('DESCRIBE funcionarios')->fetchAll();
    foreach ($cols as $col) {
        echo "   - {$col['Field']} ({$col['Type']}) - Null: {$col['Null']} - Key: {$col['Key']}\n";
    }
    
    $count = $kh_func->query('SELECT COUNT(*) as cnt FROM funcionarios')->fetch()['cnt'];
    echo "\n   TOTAL: $count registros\n\n";
    
} catch (Exception $e) {
    echo "❌ ERRO Funcionários: " . $e->getMessage() . "\n\n";
}

// KingHost Projetos
try {
    $kh_proj = new PDO(
        'mysql:host=' . getenv('TABFANTASIA_SOURCE_HOST') . ';dbname=' . getenv('TABFANTASIA_SOURCE_DB'),
        getenv('TABFANTASIA_SOURCE_USER'),
        getenv('TABFANTASIA_SOURCE_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    
    echo "✅ CONECTADO: KingHost Projetos (plansul263)\n\n";
    
    echo "📋 COLUNAS DA TABELA 'tabfant':\n";
    $cols = $kh_proj->query('DESCRIBE tabfant')->fetchAll();
    foreach ($cols as $col) {
        echo "   - {$col['Field']} ({$col['Type']}) - Null: {$col['Null']} - Key: {$col['Key']}\n";
    }
    
    $count = $kh_proj->query('SELECT COUNT(*) as cnt FROM tabfant')->fetch()['cnt'];
    echo "\n   TOTAL: $count registros\n\n";
    
} catch (Exception $e) {
    echo "❌ ERRO Projetos: " . $e->getMessage() . "\n\n";
}

// Local
try {
    $local = new PDO(
        'mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1') . ';dbname=' . (getenv('DB_DATABASE') ?: 'cadastros_plansul'),
        getenv('DB_USERNAME') ?: 'root',
        getenv('DB_PASSWORD') ?: '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    
    echo "✅ CONECTADO: Banco Local (cadastros_plansul)\n\n";
    
    echo "📋 COLUNAS DA TABELA 'funcionarios':\n";
    $cols = $local->query('DESCRIBE funcionarios')->fetchAll();
    foreach ($cols as $col) {
        echo "   - {$col['Field']} ({$col['Type']}) - Null: {$col['Null']} - Key: {$col['Key']}\n";
    }
    
    $count = $local->query('SELECT COUNT(*) as cnt FROM funcionarios')->fetch()['cnt'];
    echo "\n   TOTAL: $count registros\n\n";
    
} catch (Exception $e) {
    echo "❌ ERRO Local: " . $e->getMessage() . "\n\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "📝 CONCLUSÃO: Compare as colunas acima\n";
echo "═══════════════════════════════════════════════════════════\n\n";
