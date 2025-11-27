<?php
// Verificar usuários

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\User;

$usuarios = User::orderBy('NMLOGIN')->get();

echo "=== USUÁRIOS NO BANCO ===\n";
echo "Total: " . count($usuarios) . "\n\n";

foreach ($usuarios as $u) {
    echo $u->NMLOGIN . " | " . $u->NOMEUSER . " | " . $u->PERFIL . "\n";
}
