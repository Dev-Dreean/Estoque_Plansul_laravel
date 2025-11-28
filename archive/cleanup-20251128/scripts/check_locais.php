<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\LocalProjeto;
use App\Models\Tabfant;

echo "LocalProjeto::count(): " . LocalProjeto::count() . PHP_EOL;
echo "Tabfant::count(): " . Tabfant::count() . PHP_EOL;
$first = LocalProjeto::with('projeto')->first();
if ($first) {
    echo "First Local: id={$first->id}, cdlocal={$first->cdlocal}, delocal={$first->delocal}, projeto_codigo=" . ($first->projeto?->CDPROJETO ?? 'null') . ", projeto_nome=" . ($first->projeto?->NOMEPROJETO ?? 'null') . PHP_EOL;
} else {
    echo "No LocalProjeto records found\n";
}

?>