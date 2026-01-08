<?php
/**
 * Script: Sincronizar Telas do KingHost
 * Objetivo: Importar todas as telas (acessotela) do KingHost para o banco local
 * Execução: php scripts/sync_telas_kinghost.php
 */

require __DIR__ . '/../bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app = app();

// Dados KingHost
$khConfig = [
    'host'     => 'mysql07-farm10.kinghost.net',
    'username' => 'plansul004_add2',
    'password' => 'A33673170a',
    'database' => 'plansul04',
];

echo "🔄 [SYNC] Conectando ao KingHost...\n";

try {
    // Conectar KingHost
    $pdo = new PDO(
        "mysql:host={$khConfig['host']};dbname={$khConfig['database']};charset=utf8mb4",
        $khConfig['username'],
        $khConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "✅ Conectado ao KingHost\n";

    // Buscar telas do KingHost
    echo "📥 [SYNC] Buscando telas do KingHost...\n";
    $stmt = $pdo->query("SELECT * FROM acessotela ORDER BY NUSEQTELA");
    $telasKH = $stmt->fetchAll();

    if (empty($telasKH)) {
        echo "❌ Nenhuma tela encontrada no KingHost\n";
        exit(1);
    }

    echo "✅ Encontradas " . count($telasKH) . " telas no KingHost\n\n";

    // Limpar tabela local
    echo "🗑️  [SYNC] Limpando tabela local de telas...\n";
    DB::table('acessotela')->truncate();
    echo "✅ Tabela limpa\n\n";

    // Inserir telas
    echo "📝 [SYNC] Inserindo telas no banco local...\n";
    
    $inserted = 0;
    foreach ($telasKH as $tela) {
        try {
            DB::table('acessotela')->insert([
                'NUSEQTELA'  => $tela['NUSEQTELA'],
                'DESTELA'    => $tela['DESTELA'],
                'FLACESSO'   => $tela['FLACESSO'] ?? 'S',
                'INACESSO'   => $tela['INACESSO'] ?? 'S',
            ]);
            $inserted++;
            echo "  ✓ Tela {$tela['NUSEQTELA']}: {$tela['DESTELA']}\n";
        } catch (Exception $e) {
            echo "  ⚠️  Erro ao inserir tela {$tela['NUSEQTELA']}: {$e->getMessage()}\n";
        }
    }

    echo "\n✅ [SYNC] Sincronização concluída!\n";
    echo "   Total de telas importadas: $inserted\n";

    // Verificar resultado
    $count = DB::table('acessotela')->count();
    echo "   Total de telas no banco local: $count\n";

    if ($count === 0) {
        echo "\n❌ ERRO: Nenhuma tela foi importada!\n";
        exit(1);
    }

    echo "\n✅ Tudo pronto! As telas estão sincronizadas.\n";

} catch (PDOException $e) {
    echo "❌ Erro de conexão ao KingHost: {$e->getMessage()}\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Erro geral: {$e->getMessage()}\n";
    exit(1);
}
