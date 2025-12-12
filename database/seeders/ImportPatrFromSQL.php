<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportPatrFromSQL extends Seeder
{
    public function run(): void
    {
        $sqlFile = database_path('imports/patr.sql');
        
        if (!file_exists($sqlFile)) {
            echo "❌ Arquivo SQL não encontrado: $sqlFile\n";
            return;
        }

        try {
            $sql = file_get_contents($sqlFile);
            
            // Dividir por ';' e executar cada statement
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn($stmt) => !empty($stmt) && !str_starts_with(trim($stmt), '--')
            );

            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    DB::statement($statement);
                }
            }

            echo "✅ Tabela patr importada com sucesso!\n";
        } catch (\Exception $e) {
            echo "❌ Erro ao importar patr: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
