<?php
// Usar conexão LOCAL para ver estrutura da tabela usuario
$local_config = [
    'host'     => '127.0.0.1',
    'database' => 'cadastros_plansul',
    'username' => 'root',
    'password' => ''
];

try {
    $pdo = new PDO(
        "mysql:host={$local_config['host']};dbname={$local_config['database']};charset=utf8mb4",
        $local_config['username'],
        $local_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "=== ESTRUTURA DA TABELA 'usuario' (LOCAL) ===\n\n";
    $result = $pdo->query('DESCRIBE usuario');
    $columns = [];
    foreach ($result as $col) {
        $columns[] = $col['Field'];
        echo "{$col['Field']} ({$col['Type']}) - Key: {$col['Key']}\n";
    }
    
    echo "\n=== AMOSTRA DE USUÁRIOS (LOCAL) ===\n";
    $result = $pdo->query('SELECT * FROM usuario LIMIT 5');
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
?>
