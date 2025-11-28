<?php
/**
 * Remove TODOS os "(PRE)" dos usuários da tabela patr
 * E deixa apenas a versão sem PRE
 * 
 * Uso: php scripts/clean_patr_usuario_pre.php
 */

$host = '127.0.0.1';
$database = 'cadastros_plansul';
$user = 'root';
$password = '';
$port = 3306;

echo "[1] Conectando ao banco local...\n";

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("Erro ao conectar: " . $e->getMessage() . "\n");
}

// Buscar todos os usuários com (PRE)
$stmt = $pdo->prepare("SELECT DISTINCT USUARIO FROM patr WHERE USUARIO LIKE '% (PRE)' ORDER BY USUARIO");
$stmt->execute();
$usuariosComPre = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "[2] Usuários com (PRE) encontrados: " . count($usuariosComPre) . "\n";
foreach ($usuariosComPre as $user) {
    echo "  - $user\n";
}

if (empty($usuariosComPre)) {
    echo "\n✓ Nenhum usuário com (PRE) encontrado. Banco já está limpo!\n";
    exit;
}

// Gerar SQL que UNIFICA: substitui versão (PRE) pela versão sem PRE
$sqlFile = __DIR__ . '/../storage/output/clean_patr_usuario_pre.sql';
$handle = fopen($sqlFile, 'w');

fwrite($handle, "-- Script para remover todos os (PRE) dos usuários em patr\n");
fwrite($handle, "-- Gerado em: " . date('Y-m-d H:i:s') . "\n");
fwrite($handle, "-- Remove duplicatas: substitui ' (PRE)' pelo usuário sem PRE\n\n");

fwrite($handle, "USE plansul04;\n\n");

fwrite($handle, "START TRANSACTION;\n\n");

// Backup
fwrite($handle, "-- Backup\n");
fwrite($handle, "CREATE TABLE IF NOT EXISTS patr_backup_pre_cleanup AS SELECT * FROM patr;\n\n");

// Para cada usuário com (PRE)
foreach ($usuariosComPre as $usuarioComPre) {
    $usuarioSemPre = str_replace(' (PRE)', '', $usuarioComPre);
    
    // Substituir (PRE) pelo versão sem PRE
    $usuarioComPreEscaped = str_replace("'", "''", $usuarioComPre);
    $usuarioSemPreEscaped = str_replace("'", "''", $usuarioSemPre);
    
    fwrite($handle, "-- Unificando: '$usuarioComPre' → '$usuarioSemPre'\n");
    fwrite($handle, "UPDATE patr SET USUARIO = '$usuarioSemPreEscaped' WHERE USUARIO = '$usuarioComPreEscaped';\n\n");
}

fwrite($handle, "COMMIT;\n\n");

// Validação
fwrite($handle, "-- Validação: nenhum usuário deve ter (PRE)\n");
fwrite($handle, "SELECT COUNT(*) AS total_com_pre FROM patr WHERE USUARIO LIKE '% (PRE)';\n");
fwrite($handle, "SELECT USUARIO, COUNT(*) as total FROM patr WHERE USUARIO IS NOT NULL AND USUARIO != '' GROUP BY USUARIO ORDER BY USUARIO;\n");

fclose($handle);

echo "\n✓ SQL de limpeza gerado: $sqlFile\n";
echo "\nPróximas etapas:\n";
echo "1. Copie TODO o conteúdo de $sqlFile\n";
echo "2. No phpMyAdmin do KingHost (SQL):\n";
echo "   - Cole o SQL\n";
echo "   - Execute\n";
echo "3. Após sucesso, execute: php artisan cache:clear\n";
