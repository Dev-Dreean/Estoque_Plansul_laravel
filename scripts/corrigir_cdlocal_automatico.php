<?php
/**
 * Script para CORRIGIR AUTOMATICAMENTE os CDLOCALs dos patrimônios
 * 
 * PROBLEMA IDENTIFICADO:
 * A coluna patr.CDLOCAL está armazenando valores que são interpretados como IDs da tabela locais_projeto,
 * mas na verdade deveriam referenciar o campo locais_projeto.cdlocal
 * 
 * SOLUÇÃO:
 * Para cada patrimônio:
 * - Se CDLOCAL = ID e esse registro tem cdlocal = ID: OK, está correto
 * - Se CDLOCAL = ID mas esse registro tem cdlocal diferente: 
 *   Precisamos achar o registro onde cdlocal = valor CDLOCAL e usar esse ID
 * 
 * EXEMPLO:
 * Patrimônio 17546: CDLOCAL=1
 * Buscamos na tabela locais_projeto onde cdlocal=1
 * Achamos ID=1, cdlocal=1, delocal='SEDE CIDASC'
 * Mas o esperado seria: ID=8, cdlocal=8, delocal='ARARANGUA'
 * 
 * ATENÇÃO: Este script fará backup e aplicará as correções!
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Patrimonio;
use App\Models\LocalProjeto;
use Illuminate\Support\Facades\DB;

echo "🔧 SCRIPT DE CORREÇÃO AUTOMÁTICA DE CDLOCAL\n";
echo "════════════════════════════════════════════════════════════\n\n";

// Pedir confirmação
echo "⚠️  ATENÇÃO: Este script irá:\n";
echo "1. Criar backup da tabela patr\n";
echo "2. Atualizar os CDLOCALs que estão incorretos\n\n";
echo "Deseja continuar? (s/N): ";
$resposta = trim(fgets(STDIN));

if (strtolower($resposta) !== 's') {
    echo "\n❌ Operação cancelada pelo usuário.\n";
    exit(0);
}

echo "\n📦 Criando backup da tabela patr...\n";
try {
    $timestamp = date('Y_m_d_His');
    DB::statement("CREATE TABLE patr_backup_{$timestamp} LIKE patr");
    DB::statement("INSERT INTO patr_backup_{$timestamp} SELECT * FROM patr");
    echo "✅ Backup criado: patr_backup_{$timestamp}\n\n";
} catch (Exception $e) {
    echo "❌ Erro ao criar backup: " . $e->getMessage() . "\n";
    echo "Operação cancelada.\n";
    exit(1);
}

// Criar mapa de locais: cdlocal => id
$locaisPorCdlocal = [];
$locais = LocalProjeto::all();
foreach ($locais as $local) {
    // Se já existe um registro com esse cdlocal, pular (pegar o primeiro)
    if (!isset($locaisPorCdlocal[$local->cdlocal])) {
        $locaisPorCdlocal[$local->cdlocal] = $local->id;
    }
}

echo "📊 Mapa de locais criado: " . count($locaisPorCdlocal) . " códigos únicos\n\n";

// Verificar patrimônios que precisam de correção
echo "🔍 Analisando patrimônios que precisam de correção...\n";

$totalVerificados = 0;
$corrigidos = 0;
$jaCorretos = 0;
$naoEncontrados = 0;
$erros = [];

DB::beginTransaction();

try {
    $patrimonios = Patrimonio::whereNotNull('CDLOCAL')->get();
    
    foreach ($patrimonios as $p) {
        $totalVerificados++;
        
        $cdlocalAtual = $p->CDLOCAL;
        
        // Verificar se esse CDLOCAL existe como cdlocal na tabela locais_projeto
        if (isset($locaisPorCdlocal[$cdlocalAtual])) {
            $idCorreto = $locaisPorCdlocal[$cdlocalAtual];
            
            // Se o ID correto é diferente do atual, corrigir
            if ($p->CDLOCAL != $idCorreto) {
                $p->CDLOCAL = $idCorreto;
                $p->save();
                $corrigidos++;
            } else {
                $jaCorretos++;
            }
        } else {
            // CDLOCAL não existe como cdlocal, tentar manter o valor atual
            // (pode ser um ID válido mesmo não sendo cdlocal)
            $localExiste = LocalProjeto::find($cdlocalAtual);
            if ($localExiste) {
                $jaCorretos++;
            } else {
                $naoEncontrados++;
                $erros[] = "Patrimônio {$p->NUPATRIMONIO}: CDLOCAL {$cdlocalAtual} não encontrado";
            }
        }
        
        if ($totalVerificados % 1000 == 0) {
            echo "  Processados: {$totalVerificados} | Corrigidos: {$corrigidos} | OK: {$jaCorretos} | Não encontrados: {$naoEncontrados}\n";
        }
    }
    
    DB::commit();
    
    echo "\n" . str_repeat("═", 80) . "\n\n";
    echo "✅ CORREÇÃO CONCLUÍDA!\n\n";
    echo "📊 ESTATÍSTICAS:\n";
    echo "Total verificados: {$totalVerificados}\n";
    echo "✅ Corrigidos: {$corrigidos}\n";
    echo "✓ Já estavam corretos: {$jaCorretos}\n";
    echo "⚠️ Não encontrados: {$naoEncontrados}\n";
    
    if (count($erros) > 0) {
        echo "\n⚠️  AVISOS:\n";
        foreach (array_slice($erros, 0, 20) as $erro) {
            echo "  • {$erro}\n";
        }
        if (count($erros) > 20) {
            echo "  ... e mais " . (count($erros) - 20) . " avisos\n";
        }
    }
    
    echo "\n💾 Backup disponível em: patr_backup_{$timestamp}\n";
    echo "\n🔄 Para reverter (se necessário):\n";
    echo "   DROP TABLE patr;\n";
    echo "   RENAME TABLE patr_backup_{$timestamp} TO patr;\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "\nRollback executado. Nenhuma alteração foi feita.\n";
    exit(1);
}
