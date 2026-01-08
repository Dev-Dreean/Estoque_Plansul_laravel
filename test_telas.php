<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

$telas = DB::table('acessotela')->whereRaw("TRIM(UPPER(FLACESSO)) = 'S'")->where('NUSEQTELA', '!=', 1005)->orderBy('NUSEQTELA')->get();
echo "Total de telas: " . count($telas) . "\n";
foreach ($telas as $t) {
    echo "  - {$t->NUSEQTELA}: {$t->DETELA}\n";
}

$kernel->terminate($request, $response);
