<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class SyncTelasKinghost extends Command
{
    protected $signature = 'sync:telas-kinghost';
    protected $description = 'Sincroniza todas as telas (acessotela) do KingHost';

    public function handle()
    {
        $this->info('🔄 Sincronizando telas do KingHost...');

        $khConfig = [
            'host'     => 'mysql07-farm10.kinghost.net',
            'username' => 'plansul004_add2',
            'password' => 'A33673170a',
            'database' => 'plansul04',
        ];

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

            $this->info('✅ Conectado ao KingHost');

            // Buscar telas
            $this->info('📥 Buscando telas do KingHost...');
            $stmt = $pdo->query('SELECT * FROM acessotela ORDER BY NUSEQTELA');
            $telasKH = $stmt->fetchAll();

            if (empty($telasKH)) {
                $this->error('❌ Nenhuma tela encontrada no KingHost');
                return 1;
            }

            $this->info("✅ Encontradas " . count($telasKH) . " telas");

            // Limpar e sincronizar
            $this->info('🗑️  Limpando tabela local...');
            DB::table('acessotela')->truncate();

            $this->info('📝 Inserindo telas...');
            $inserted = 0;
            
            foreach ($telasKH as $tela) {
                DB::table('acessotela')->insert([
                    'NUSEQTELA'          => $tela['NUSEQTELA'],
                    'DETELA'             => $tela['DETELA'] ?? $tela['DESTELA'] ?? null,
                    'NMSISTEMA'          => $tela['NMSISTEMA'] ?? 'Sistema Principal',
                    'FLACESSO'           => $tela['FLACESSO'] ?? 'S',
                    'NIVEL_VISIBILIDADE' => $tela['NIVEL_VISIBILIDADE'] ?? 'TODOS',
                ]);
                $inserted++;
                $desc = $tela['DETELA'] ?? $tela['DESTELA'] ?? '';
                $this->line("  ✓ {$tela['NUSEQTELA']}: {$desc}");
            }

            $count = DB::table('acessotela')->count();
            $this->info("✅ Sincronização concluída!");
            $this->info("   Telas importadas: $count");

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erro: ' . $e->getMessage());
            return 1;
        }
    }
}
