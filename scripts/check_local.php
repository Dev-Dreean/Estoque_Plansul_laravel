<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\LocalProjeto;
$locals = LocalProjeto::where('cdlocal', 402)->get(['id', 'cdlocal', 'tabfant_id']);
echo json_encode($locals->toArray(), JSON_PRETTY_PRINT);
