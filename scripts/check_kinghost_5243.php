<?php
$pdo = new PDO('mysql:host=mysql07-farm10.kinghost.net;dbname=plansul04', 'plansul004_add2', 'A33673170a');

echo "VERIFICANDO PATRIMONIO #5243 KINGHOST:\n";
$r = $pdo->query("SELECT MARCA, MODELO, USUARIO, CDMATRFUNCIONARIO, SITUACAO, CDLOCAL, DEPATRIMONIO FROM patr WHERE NUPATRIMONIO = 5243 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($r) {
    echo "MARCA: '" . $r['MARCA'] . "'\n";
    echo "MODELO: '" . $r['MODELO'] . "'\n";
    echo "USUARIO: '" . $r['USUARIO'] . "'\n";
    echo "CDMATRFUNCIONARIO: " . $r['CDMATRFUNCIONARIO'] . "\n";
    echo "SITUACAO: '" . $r['SITUACAO'] . "'\n";
    echo "CDLOCAL: " . $r['CDLOCAL'] . "\n";
    echo "DEPATRIMONIO: '" . substr($r['DEPATRIMONIO'], 0, 50) . "'\n";
}
