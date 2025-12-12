<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sqlFile = database_path('imports/patr.sql');
        
        if (!file_exists($sqlFile)) {
            throw new \Exception("Arquivo SQL não encontrado: $sqlFile");
        }

        $sql = file_get_contents($sqlFile);
        
        // Remover comentários SQL
        $sql = preg_replace('/^--.*$/m', '', $sql);
        
        // Dividir por ';' e executar cada statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt)
        );

        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                DB::statement($statement);
            }
        }
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS `patr`');
    }
};
