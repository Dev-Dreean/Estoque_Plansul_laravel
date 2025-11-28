<?php
// Credenciais KingHost do projeto
$kh_config = [
    'host'     => 'ftp.plansul.info',
    'database' => 'plansul04',
    'username' => 'plansul04',
    'password' => 'Xp@ssw0rd#2024'
];

try {
    $pdo = new PDO(
        "mysql:host={$kh_config['host']};dbname={$kh_config['database']};charset=utf8mb4",
        $kh_config['username'],
        $kh_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "=== ESTRUTURA DA TABELA 'usuario' NO SERVIDOR ===\n\n";
    $result = $pdo->query('DESCRIBE usuario');
    $columns = [];
    foreach ($result as $col) {
        $columns[] = $col['Field'];
        echo "{$col['Field']} ({$col['Type']}) - Null: {$col['Null']} - Key: {$col['Key']}\n";
    }
    
    echo "\n=== AMOSTRA: PRIMEIROS 5 USUÁRIOS ===\n";
    $result = $pdo->query('SELECT * FROM usuario LIMIT 5');
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    echo "\n\n=== BUSCANDO USUÁRIOS COM 'PRE' ===\n";
    $result = $pdo->query("SELECT * FROM usuario WHERE NMLOGIN LIKE '%PRE%' OR NMUSUARIO LIKE '%PRE%'");
    $pre_users = $result->fetchAll(PDO::FETCH_ASSOC);
    if ($pre_users) {
        echo json_encode($pre_users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo "Nenhum usuário com PRE encontrado\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
?>
