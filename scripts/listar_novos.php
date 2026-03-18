<?php
// one-off: Listar últimos funcionários e projetos adicionados
require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== ÚLTIMOS 40 FUNCIONÁRIOS ADICIONADOS ===\n";
$novos = DB::table('funcionarios')
    ->where('CODFIL','=','')
    ->where('UFPROJ','=','')
    ->orderBy('CDMATRFUNCIONARIO','DESC')
    ->limit(40)
    ->get(['CDMATRFUNCIONARIO','NMFUNCIONARIO','DTADMISSAO']);

foreach($novos as $f) {
  echo sprintf('[%6s] %s (adm: %s)'.PHP_EOL, 
    $f->CDMATRFUNCIONARIO, 
    mb_strtoupper(substr($f->NMFUNCIONARIO, 0, 45)), 
    $f->DTADMISSAO ?? 'N/A'
  );
}

echo "\n✅ Total de funcionários: ".DB::table('funcionarios')->count().PHP_EOL;
echo "📊 Novos do plansul104 (sem CODFIL/UFPROJ): ".DB::table('funcionarios')->where('CODFIL','=','')->count().PHP_EOL;

echo "\n=== PROJETOS ===\n";
$projs = DB::table('tabfant')->orderBy('id','DESC')->limit(10)->get(['id','CDPROJETO','NOMEPROJETO']);
foreach($projs as $p) {
  echo sprintf("[ID: %d] %s (CD: %s)\n", $p->id, $p->NOMEPROJETO, $p->CDPROJETO);
}
echo "✅ Total de projetos: ".DB::table('tabfant')->count().PHP_EOL;
