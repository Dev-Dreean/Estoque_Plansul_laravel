<?php
/**
 * SCRIPT DE CORREÇÃO DEFINITIVA - CDLOCAL E CDPROJETO
 * 
 * PROBLEMA IDENTIFICADO:
 * 1. Patrimônios com CDPROJETO=100001 (PLANSUL EMPRESA) estão em CDLOCAL=1
 * 2. Mas o local ID=1 (SEDE CIDASC) está associado ao projeto 686 (CIDASC-2)
 * 3. Não há um local específico para o projeto 100001
 * 
 * SOLUÇÃO:
 * 1. Verificar se todos os dados do arquivo TXT estão corretos
 * 2. Criar mapeamento correto entre locais e projetos
 * 3. Atualizar patrimônios conforme necessário
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use App\Models\LocalProjeto;
use App\Models\Tabfant;
use Illuminate\Support\Facades\DB;

echo "🔧 CORREÇÃO DEFINITIVA - CDLOCAL E CDPROJETO\n";
echo "════════════════════════════════════════════════════════════\n\n";

// ETAPA 1: Análise do problema
echo "📊 ETAPA 1: ANÁLISE DO PROBLEMA\n";
echo str_repeat("─", 60) . "\n\n";

$projeto100001 = Tabfant::where('CDPROJETO', 100001)->first();
echo "Projeto 100001:\n";
if ($projeto100001) {
    echo "   ✅ ID: {$projeto100001->id}, Nome: {$projeto100001->NOMEPROJETO}\n";
} else {
    echo "   ❌ NÃO ENCONTRADO\n";
}

$localSede = LocalProjeto::find(1);
echo "\nLocal ID=1 (SEDE CIDASC):\n";
if ($localSede) {
    echo "   cdlocal: {$localSede->cdlocal}\n";
    echo "   delocal: {$localSede->delocal}\n";
    echo "   tabfant_id: {$localSede->tabfant_id}\n";
    if ($localSede->tabfant_id) {
        $proj = Tabfant::find($localSede->tabfant_id);
        if ($proj) {
            echo "   Projeto atual: {$proj->CDPROJETO} - {$proj->NOMEPROJETO}\n";
        }
    }
}

$patrsComProblema = Patrimonio::where('CDPROJETO', 100001)
    ->where('CDLOCAL', 1)
    ->count();
echo "\n⚠️  Patrimônios com CDPROJETO=100001 e CDLOCAL=1: {$patrsComProblema}\n";

echo "\n" . str_repeat("═", 60) . "\n\n";

// ETAPA 2: Verificar se existe local para projeto 100001
echo "📍 ETAPA 2: VERIFICANDO LOCAL PARA PROJETO 100001\n";
echo str_repeat("─", 60) . "\n\n";

$localProjeto100001 = LocalProjeto::whereHas('projeto', function($q) {
    $q->where('CDPROJETO', 100001);
})->first();

if ($localProjeto100001) {
    echo "✅ Local encontrado para projeto 100001:\n";
    echo "   ID: {$localProjeto100001->id}\n";
    echo "   cdlocal: {$localProjeto100001->cdlocal}\n";
    echo "   delocal: {$localProjeto100001->delocal}\n";
    
    echo "\n💡 SOLUÇÃO: Atualizar patrimônios para usar este local\n";
    $idCorreto = $localProjeto100001->id;
    
} else {
    echo "❌ NÃO existe local específico para projeto 100001\n";
    echo "\n💡 SOLUÇÃO: Criar um novo local OU ajustar mapeamento\n";
    
    echo "\n📝 Opções:\n";
    echo "A) Criar novo local 'PLANSUL EMPRESA - SEDE' associado ao projeto 100001\n";
    echo "B) Os patrimônios ficam onde estão (SEDE CIDASC) mas com projeto diferente\n";
    echo "C) Atualizar o local SEDE CIDASC para ter o projeto 100001\n\n";
    
    echo "Escolha uma opção (A/B/C) ou Enter para cancelar: ";
    $opcao = trim(strtoupper(fgets(STDIN)));
    
    if ($opcao === 'A') {
        echo "\n🏗️  Criando novo local para PLANSUL EMPRESA...\n";
        
        // Buscar próximo ID disponível
        $maxId = LocalProjeto::max('id') ?? 0;
        $maxCdlocal = LocalProjeto::max('cdlocal') ?? 0;
        
        $novoLocal = LocalProjeto::create([
            'id' => $maxId + 1,
            'cdlocal' => $maxCdlocal + 1,
            'delocal' => 'PLANSUL EMPRESA - SEDE',
            'tabfant_id' => $projeto100001->id,
            'flativo' => 1
        ]);
        
        echo "✅ Local criado!\n";
        echo "   ID: {$novoLocal->id}\n";
        echo "   cdlocal: {$novoLocal->cdlocal}\n";
        echo "   delocal: {$novoLocal->delocal}\n";
        
        $idCorreto = $novoLocal->id;
        
    } elseif ($opcao === 'B') {
        echo "\n✅ Mantendo patrimônios onde estão. Nenhuma alteração necessária.\n";
        exit(0);
        
    } elseif ($opcao === 'C') {
        echo "\n⚠️  ATENÇÃO: Isso afetará TODOS os patrimônios da SEDE CIDASC!\n";
        echo "Confirma alteração do projeto do local SEDE CIDASC? (s/N): ";
        $confirma = trim(strtolower(fgets(STDIN)));
        
        if ($confirma === 's') {
            $localSede->tabfant_id = $projeto100001->id;
            $localSede->save();
            echo "✅ Local SEDE CIDASC atualizado para projeto 100001\n";
        }
        exit(0);
        
    } else {
        echo "\n❌ Operação cancelada\n";
        exit(0);
    }
}

echo "\n" . str_repeat("═", 60) . "\n\n";

// ETAPA 3: Atualizar patrimônios
echo "🔄 ETAPA 3: ATUALIZANDO PATRIMÔNIOS\n";
echo str_repeat("─", 60) . "\n\n";

echo "Será atualizado CDLOCAL dos patrimônios com:\n";
echo "• CDPROJETO = 100001\n";
echo "• CDLOCAL atual = 1\n";
echo "• Novo CDLOCAL = {$idCorreto}\n\n";

echo "Confirma atualização de {$patrsComProblema} patrimônios? (s/N): ";
$confirma = trim(strtolower(fgets(STDIN)));

if ($confirma !== 's') {
    echo "\n❌ Operação cancelada\n";
    exit(0);
}

echo "\n📦 Criando backup...\n";
$timestamp = date('Y_m_d_His');
DB::statement("CREATE TABLE patr_backup_{$timestamp} LIKE patr");
DB::statement("INSERT INTO patr_backup_{$timestamp} SELECT * FROM patr");
echo "✅ Backup: patr_backup_{$timestamp}\n\n";

echo "🔄 Atualizando patrimônios...\n";
DB::beginTransaction();

try {
    $atualizado = Patrimonio::where('CDPROJETO', 100001)
        ->where('CDLOCAL', 1)
        ->update(['CDLOCAL' => $idCorreto]);
    
    DB::commit();
    
    echo "✅ {$atualizado} patrimônios atualizados!\n\n";
    
    // Verificar patrimônio 17546
    $p17546 = Patrimonio::where('NUPATRIMONIO', 17546)->first();
    if ($p17546) {
        $local = LocalProjeto::find($p17546->CDLOCAL);
        echo "✅ Verificação - Patrimônio 17546:\n";
        echo "   CDLOCAL: {$p17546->CDLOCAL}\n";
        echo "   CDPROJETO: {$p17546->CDPROJETO}\n";
        if ($local) {
            echo "   Local: {$local->delocal}\n";
            if ($local->tabfant_id) {
                $proj = Tabfant::find($local->tabfant_id);
                if ($proj) {
                    echo "   Projeto: {$proj->CDPROJETO} - {$proj->NOMEPROJETO}\n";
                }
            }
        }
    }
    
    echo "\n" . str_repeat("═", 60) . "\n\n";
    echo "✅ CORREÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "\n💾 Para reverter:\n";
    echo "   DROP TABLE patr;\n";
    echo "   RENAME TABLE patr_backup_{$timestamp} TO patr;\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Rollback executado.\n";
    exit(1);
}
