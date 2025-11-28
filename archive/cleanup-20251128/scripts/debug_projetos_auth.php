<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

$user = DB::table('usuario')->where('PERFIL', 'SUP')->first();
if (!$user) {
    echo "No SUP user found\n";
    exit(1);
}

// Criar request e bind no container
$request = Request::create('/projetos', 'GET');
$app->instance('request', $request);

// Login programmatically (associado ao request)
Auth::setRequest($request);
Auth::loginUsingId($user->NUSEQUSUARIO);

$response = $kernel->handle($request);

file_put_contents(__DIR__ . '/../debug_projetos_auth.html', $response->getContent());
echo "Saved to debug_projetos_auth.html\n";
$kernel->terminate($request, $response);
?>