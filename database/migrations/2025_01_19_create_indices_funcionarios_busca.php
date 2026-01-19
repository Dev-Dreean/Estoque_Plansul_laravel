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
     * 🚀 Optimiza indices para busca de funcionários
     * - Matrícula: prefix search (CDMATRFUNCIONARIO LIKE '123%')
     * - Nome: wildcard search (NMFUNCIONARIO LIKE '%JOÃO%')
     */
    public function up(): void
    {
        // Verificar se índices já existem
        if (Schema::hasTable('funcionarios')) {
            $indexes = DB::select("SHOW INDEXES FROM funcionarios WHERE Key_name IN ('idx_cdmatrfuncionario', 'idx_nmfuncionario_search')");
            
            // Se não existem, criar
            if (empty($indexes)) {
                Schema::table('funcionarios', function (Blueprint $table) {
                    // ✅ Índice para busca por matrícula (prefix search)
                    $table->index('CDMATRFUNCIONARIO', 'idx_cdmatrfuncionario');
                    
                    // ✅ Índice para busca por nome (wildcard search)
                    $table->index('NMFUNCIONARIO', 'idx_nmfuncionario_search');
                });
                
                echo "✅ Índices criados com sucesso para busca otimizada\n";
            } else {
                echo "ℹ️  Índices já existem, pulando criação\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('funcionarios')) {
            Schema::table('funcionarios', function (Blueprint $table) {
                $table->dropIndex('idx_cdmatrfuncionario');
                $table->dropIndex('idx_nmfuncionario_search');
            });
        }
    }
};
