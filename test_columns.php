<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Listar todas as colunas da tabela patr
echo "=== Colunas da tabela patr ===\n";
$columns = DB::getSchemaBuilder()->getColumnListing('patr');
echo "Total de colunas: " . count($columns) . "\n";
foreach ($columns as $col) {
    echo "  - " . $col . "\n";
}

// Verificar dados específicos para entender a estrutura
echo "\n=== Dados de um registro completo ===\n";
$registro = DB::table('patr')->where('NUPATRIMONIO', '>', 0)->first();
if ($registro) {
    foreach (get_object_vars($registro) as $col => $value) {
        echo "{$col}: " . ($value ? (is_string($value) ? substr($value, 0, 100) : $value) : 'NULL') . "\n";
    }
}

// Procurar por quaisquer colunas que possam conter informação de cadastrador/usuário
echo "\n=== Buscando colunas que possam ser de usuário ===\n";
$columnos_usuario = array_filter($columns, function($col) {
    return stripos($col, 'usuario') !== false || 
           stripos($col, 'cadastr') !== false ||
           stripos($col, 'login') !== false ||
           stripos($col, 'user') !== false;
});
print_r($columnos_usuario);
