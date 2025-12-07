<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$db = $app->make('db');

echo "\n=== USUÁRIOS BEA/BEATRIZ ===\n\n";

$users = $db->table('usuario')
    ->whereRaw("UPPER(NOMEUSER) LIKE '%BEA%'")
    ->get();

foreach($users as $u) {
    echo $u->NOMEUSER . " (CDMATR: " . $u->CDMATRFUNCIONARIO . ")\n";
}

echo "\n=== REGISTROS EM PATR ===\n\n";

$patrimonios = $db->table('patr')
    ->whereRaw("UPPER(USUARIO) LIKE '%BEA%'")
    ->distinct()
    ->pluck('USUARIO');

foreach($patrimonios as $user) {
    echo "  - " . $user . "\n";
}
?>
