<?php
/**
 * Remove (PRE) dos nomes de usuários na tabela usuario
 * Mantém os usuários, apenas remove o sufixo (PRE)
 */

$host = '127.0.0.1';
$database = 'cadastros_plansul';
$user = 'root';
$password = '';
$port = 3306;

echo "[1] Conectando ao banco local para referenciar usuários...\n";

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

// Buscar usuários do banco local
$stmt = $pdo->prepare("SELECT DISTINCT USUARIO FROM patr WHERE USUARIO IS NOT NULL AND USUARIO != '' AND USUARIO NOT LIKE '%PRE%' ORDER BY USUARIO");
$stmt->execute();
$usuariosLocais = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "[2] Usuários encontrados no banco local: " . count($usuariosLocais) . "\n";

// Gerar mapa
$usuarioMap = [];
foreach ($usuariosLocais as $user) {
    $userPreVersion = $user . ' (PRE)';
    $usuarioMap[$userPreVersion] = $user;
}

$sqlFile = __DIR__ . '/../storage/output/remove_pre_from_usuario_table.sql';
$handle = fopen($sqlFile, 'w');

fwrite($handle, "-- Script para remover (PRE) dos nomes de usuários\n");
fwrite($handle, "-- E consolidar usuários duplicados na tabela usuario\n");
fwrite($handle, "-- Gerado em: " . date('Y-m-d H:i:s') . "\n\n");

fwrite($handle, "USE plansul04;\n\n");

fwrite($handle, "START TRANSACTION;\n\n");

// Backup
fwrite($handle, "-- BACKUP\n");
fwrite($handle, "CREATE TABLE IF NOT EXISTS usuario_backup_pre_remove AS SELECT * FROM usuario;\n\n");

// Operação: UPDATE para remover (PRE) do NOME do usuário
fwrite($handle, "-- OPERAÇÃO: Remover ' (PRE)' dos nomes de usuários\n");
fwrite($handle, "-- Isso vai unificar ABIGAIL (PRE) com ABIGAIL, etc.\n\n");

foreach ($usuarioMap as $preUser => $correctUser) {
    $preUserEsc = str_replace("'", "''", $preUser);
    $correctUserEsc = str_replace("'", "''", $correctUser);
    
    fwrite($handle, "-- Consolidando: '$preUser' → '$correctUser'\n");
    fwrite($handle, "UPDATE usuario SET NMLOGIN = '$correctUserEsc' WHERE NMLOGIN = '$preUserEsc';\n");
}

fwrite($handle, "\nCOMMIT;\n\n");

// Validação
fwrite($handle, "-- VALIDAÇÃO\n");
fwrite($handle, "SELECT COUNT(*) AS usuarios_com_pre FROM usuario WHERE NMLOGIN LIKE '%PRE%';\n");
fwrite($handle, "SELECT NMLOGIN, NOMEUSER FROM usuario ORDER BY NMLOGIN;\n");

fclose($handle);

echo "\n✓ SQL para remover (PRE) dos usuários gerado!\n";
echo "Arquivo: $sqlFile\n\n";

echo "=== CONSOLIDAÇÕES ===\n";
foreach ($usuarioMap as $preUser => $correctUser) {
    echo "  ✓ $preUser → $correctUser\n";
}

echo "\n=== PRÓXIMOS PASSOS ===\n";
echo "1. Abra phpMyAdmin do KingHost\n";
echo "2. Aba SQL\n";
echo "3. Cole TODO o conteúdo de:\n";
echo "   storage/output/remove_pre_from_usuario_table.sql\n";
echo "4. Execute\n";
echo "5. Depois: php artisan cache:clear\n";
