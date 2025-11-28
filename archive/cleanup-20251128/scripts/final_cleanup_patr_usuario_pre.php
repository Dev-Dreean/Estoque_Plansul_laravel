<?php
/**
 * SCRIPT FINAL - Remove TODOS os (PRE) do servidor
 * Consolida usuários duplicados
 * 
 * Estratégia:
 * 1. Identifica TODOS os usuários com (PRE) no servidor
 * 2. Move patrimônios de versão (PRE) para versão sem PRE
 * 3. Remove o usuário (PRE) da tabela usuario
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

// Buscar usuários do banco local (de referência)
$stmt = $pdo->prepare("SELECT DISTINCT USUARIO FROM patr WHERE USUARIO IS NOT NULL AND USUARIO != '' AND USUARIO NOT LIKE '%PRE%' ORDER BY USUARIO");
$stmt->execute();
$usuariosLocais = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "[2] Usuários encontrados no banco local: " . count($usuariosLocais) . "\n";

// Gerar mapa de conversão automático
$usuarioMap = [];
foreach ($usuariosLocais as $user) {
    $userPreVersion = $user . ' (PRE)';
    $usuarioMap[$userPreVersion] = $user;
}

$sqlFile = __DIR__ . '/../storage/output/final_cleanup_patr_usuario_pre.sql';
$handle = fopen($sqlFile, 'w');

fwrite($handle, "-- ====================================================================\n");
fwrite($handle, "-- SCRIPT FINAL: Remove TODOS os usuários (PRE) do servidor KingHost\n");
fwrite($handle, "-- ====================================================================\n");
fwrite($handle, "-- Gerado em: " . date('Y-m-d H:i:s') . "\n");
fwrite($handle, "-- Operações:\n");
fwrite($handle, "--   1. Substitui patrimônios (PRE) pelos usuários corretos\n");
fwrite($handle, "--   2. Remove usuários (PRE) da tabela usuario\n");
fwrite($handle, "-- ====================================================================\n\n");

fwrite($handle, "USE plansul04;\n\n");

fwrite($handle, "START TRANSACTION;\n\n");

// Backup
fwrite($handle, "-- BACKUP\n");
fwrite($handle, "CREATE TABLE IF NOT EXISTS patr_backup_final_cleanup AS SELECT * FROM patr;\n");
fwrite($handle, "CREATE TABLE IF NOT EXISTS usuario_backup_final_cleanup AS SELECT * FROM usuario;\n\n");

// ETAPA 1: Substituir patrimônios
fwrite($handle, "-- ETAPA 1: Substituir patrimônios com usuário (PRE) pelo usuário correto\n");
foreach ($usuarioMap as $preUser => $correctUser) {
    $preUserEsc = str_replace("'", "''", $preUser);
    $correctUserEsc = str_replace("'", "''", $correctUser);
    fwrite($handle, "UPDATE patr SET USUARIO = '$correctUserEsc' WHERE USUARIO = '$preUserEsc';\n");
}
fwrite($handle, "\n");

// ETAPA 2: Remover usuários (PRE) da tabela usuario
fwrite($handle, "-- ETAPA 2: Remover usuários (PRE) da tabela usuario\n");
foreach ($usuarioMap as $preUser => $correctUser) {
    $preUserEsc = str_replace("'", "''", $preUser);
    fwrite($handle, "DELETE FROM usuario WHERE NMLOGIN = '$preUserEsc';\n");
}
fwrite($handle, "\n");

fwrite($handle, "COMMIT;\n\n");

// VALIDAÇÃO
fwrite($handle, "-- VALIDAÇÃO\n");
fwrite($handle, "-- 1. Verificar se ainda existem (PRE) em patrimônios\n");
fwrite($handle, "SELECT COUNT(*) AS total_patr_com_pre FROM patr WHERE USUARIO LIKE '%PRE%';\n\n");

fwrite($handle, "-- 2. Verificar se ainda existem (PRE) em usuários\n");
fwrite($handle, "SELECT COUNT(*) AS total_usuario_com_pre FROM usuario WHERE NMLOGIN LIKE '%PRE%';\n\n");

fwrite($handle, "-- 3. Distribuição final de patrimônios por usuário\n");
fwrite($handle, "SELECT USUARIO, COUNT(*) as total FROM patr WHERE USUARIO IS NOT NULL AND USUARIO != '' GROUP BY USUARIO ORDER BY total DESC;\n\n");

fwrite($handle, "-- 4. Listar usuários (PRE) restantes (se houver)\n");
fwrite($handle, "SELECT NMLOGIN FROM usuario WHERE NMLOGIN LIKE '%PRE%' ORDER BY NMLOGIN;\n");

fclose($handle);

echo "\n✓ SQL FINAL gerado com sucesso!\n";
echo "Arquivo: $sqlFile\n\n";

echo "=== CONVERSÕES QUE SERÃO FEITAS ===\n";
$count = 0;
foreach ($usuarioMap as $preUser => $correctUser) {
    echo "  ✓ $preUser → $correctUser\n";
    $count++;
}
echo "\nTotal de conversões: $count\n";

echo "\n=== ⚠️  INSTRUÇÕES CRÍTICAS ===\n";
echo "1. Abra phpMyAdmin do KingHost (ftp.plansul.info)\n";
echo "2. Selecione banco: plansul04\n";
echo "3. Aba SQL\n";
echo "4. Cole TODO o conteúdo de:\n";
echo "   storage/output/final_cleanup_patr_usuario_pre.sql\n";
echo "5. Clique em EXECUTE\n";
echo "6. Aguarde 30-60 segundos\n";
echo "7. Veja as validações confirmar que não há mais (PRE)\n";
echo "8. Depois execute: php artisan cache:clear\n";
