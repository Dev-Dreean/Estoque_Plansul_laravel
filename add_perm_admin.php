<?php
// one-off: Adicionar permissão ADMIN para tela 1010 no KingHost
$host = 'mysql07-farm10.kinghost.net';
$user = 'plansul004_add2';
$pass = 'A33673170a';
$db = 'plansul04';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("❌ Conexão falhou: " . $conn->connect_error);
}

$sql = "INSERT INTO acessousuario (NUSEQTELA, CDMATRFUNCIONARIO, INACESSO) VALUES (1010, 'ADMIN', 'S') ON DUPLICATE KEY UPDATE INACESSO='S'";
if ($conn->query($sql) === TRUE) {
    echo "✅ Permissão criada/atualizada para ADMIN em tela 1010\n";
    
    // Verificar
    $result = $conn->query("SELECT * FROM acessousuario WHERE NUSEQTELA=1010 AND CDMATRFUNCIONARIO='ADMIN'");
    if ($row = $result->fetch_assoc()) {
        echo "✅ Verificado: " . json_encode($row) . "\n";
    }
} else {
    echo "❌ Erro: " . $conn->error . "\n";
}

$conn->close();
?>
