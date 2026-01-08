<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adiciona índices para otimizar queries frequentes em patrimônios
     * Problema: buscar por NUPATRIMONIO levava 5 segundos sem índice
     * Solução: índices compostos e simples para as colunas mais consultadas
     */
    public function up(): void
    {
        Schema::table('patr', function (Blueprint $table) {
            // Índice simples para busca por número de patrimônio (mais frequente)
            // Melhora em até 100x quando combinado com cache
            if (!Schema::hasColumn('patr', 'NUPATRIMONIO')) {
                // Tabela pode não ter a coluna, skip
                return;
            }
            
            // Verificar se índice já existe antes de criar (evita erro)
            $indexes = Schema::getIndexes('patr');
            $indexNames = collect($indexes)->pluck('name')->toArray();
            
            // Índice para NUPATRIMONIO (busca rápida)
            if (!in_array('idx_patr_nupatrimonio', $indexNames)) {
                $table->index('NUPATRIMONIO', 'idx_patr_nupatrimonio');
            }
            
            // Índice composto para filtro de situação + projeto (muito usado)
            if (!in_array('idx_patr_situacao_cdprojeto', $indexNames)) {
                $table->index(['SITUACAO', 'CDPROJETO'], 'idx_patr_situacao_cdprojeto');
            }
            
            // Índice para busca por local (usado no filtro)
            if (!in_array('idx_patr_cdlocal', $indexNames)) {
                $table->index('CDLOCAL', 'idx_patr_cdlocal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patr', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_patr_nupatrimonio');
            $table->dropIndexIfExists('idx_patr_situacao_cdprojeto');
            $table->dropIndexIfExists('idx_patr_cdlocal');
        });
    }
};
