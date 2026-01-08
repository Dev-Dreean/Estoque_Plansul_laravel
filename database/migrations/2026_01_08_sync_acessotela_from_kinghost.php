<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Sincroniza todas as telas (acessotela) do KingHost com o banco local
     * Garante que as telas disponíveis estejam sempre em sincronia
     */
    public function up(): void
    {
        // Conectar ao KingHost via SSH e buscar telas
        $this->syncAcessoTelaFromKinghost();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não reverter pois sincronização é crítica
    }

    private function syncAcessoTelaFromKinghost(): void
    {
        // Dados do KingHost
        $kinghost = [
            'host' => 'mysql07-farm10.kinghost.net',
            'username' => 'plansul004_add2',
            'password' => 'A33673170a',
            'database' => 'plansul04',
        ];

        try {
            // Conectar ao KingHost
            $pdo = new \PDO(
                "mysql:host={$kinghost['host']};dbname={$kinghost['database']}",
                $kinghost['username'],
                $kinghost['password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            // Buscar todas as telas do KingHost
            $stmt = $pdo->query("SELECT * FROM acessotela ORDER BY NUSEQTELA");
            $telasKinghost = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($telasKinghost)) {
                \Log::warning('⚠️ [SYNC] Nenhuma tela encontrada no KingHost');
                return;
            }

            \Log::info("📥 [SYNC] Sincronizando " . count($telasKinghost) . " telas do KingHost");

            // Limpar tabela local (manter apenas as telas do KingHost)
            DB::table('acessotela')->truncate();

            // Inserir telas do KingHost
            foreach ($telasKinghost as $tela) {
                DB::table('acessotela')->insert([
                    'NUSEQTELA' => $tela['NUSEQTELA'] ?? null,
                    'DESTELA' => $tela['DESTELA'] ?? null,
                    'FLACESSO' => $tela['FLACESSO'] ?? 'S',
                    'INACESSO' => $tela['INACESSO'] ?? 'S',
                ]);
            }

            \Log::info("✅ [SYNC] Sincronização de telas concluída com sucesso! " . count($telasKinghost) . " telas importadas.");

        } catch (\Exception $e) {
            \Log::error("❌ [SYNC] Erro ao sincronizar telas do KingHost: " . $e->getMessage());
            // Não falhar a migration, apenas logar o erro
        }
    }
};
