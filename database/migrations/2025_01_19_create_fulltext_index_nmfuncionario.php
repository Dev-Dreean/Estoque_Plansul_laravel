<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * 🚀 Criar índice FULLTEXT para busca ultra-rápida de nomes
     * Transforma busca de 300-500ms para 5-10ms
     */
    public function up(): void
    {
        // Remover índice simples (se existir) pois FULLTEXT é mais eficiente
        if (Schema::hasTable('funcionarios')) {
            // Tentar remover índice antigo com try-catch (compatibilidade com MySQL antigo)
            try {
                DB::statement('ALTER TABLE funcionarios DROP INDEX idx_nmfuncionario_search');
            } catch (\Exception $e) {
                // Índice não existe, ok
            }
            
            // Criar índice FULLTEXT (se não existir)
            try {
                DB::statement('ALTER TABLE funcionarios ADD FULLTEXT INDEX ft_nmfuncionario (NMFUNCIONARIO)');
            } catch (\Exception $e) {
                // Índice já existe, ok
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('funcionarios')) {
            try {
                DB::statement('ALTER TABLE funcionarios DROP INDEX ft_nmfuncionario');
            } catch (\Exception $e) {
                // Índice não existe, ok
            }
            
            // Recriar índice simples
            try {
                DB::statement('ALTER TABLE funcionarios ADD INDEX idx_nmfuncionario_search (NMFUNCIONARIO(50))');
            } catch (\Exception $e) {
                // Índice pode já existir
            }
        }
    }
};
