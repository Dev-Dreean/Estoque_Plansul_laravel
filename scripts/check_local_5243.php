<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;

echo "VERIFICANDO PATRIMONIO #5243 LOCAL:\n";
$p = Patrimonio::where('NUPATRIMONIO', 5243)->first();
if ($p) {
    echo "MARCA: " . $p->MARCA . "\n";
    echo "MODELO: " . $p->MODELO . "\n";
    echo "USUARIO: " . $p->USUARIO . "\n";
    echo "CDMATRFUNCIONARIO: " . $p->CDMATRFUNCIONARIO . "\n";
    echo "SITUACAO: " . $p->SITUACAO . "\n";
    echo "CDLOCAL: " . $p->CDLOCAL . "\n";
    echo "DEPATRIMONIO: " . substr($p->DEPATRIMONIO, 0, 50) . "\n";
}
