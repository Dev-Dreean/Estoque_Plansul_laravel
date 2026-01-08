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
            // Índices para otimizar queries frequentes
            // KingHost MySQL antigo: criar sem verificação de schema (que falha)
            try {
                $table->index('NUPATRIMONIO', 'idx_patr_nupatrimonio');
            } catch (\Exception $e) {
                // Índice já existe ou erro MySQL antigo
            }
            
            try {
                $table->index(['SITUACAO', 'CDPROJETO'], 'idx_patr_situacao_cdprojeto');
            } catch (\Exception $e) {
                // Índice já existe ou erro MySQL antigo
            }
            
            try {
                $table->index('CDLOCAL', 'idx_patr_cdlocal');
            } catch (\Exception $e) {
                // Índice já existe ou erro MySQL antigo
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patr', function (Blueprint $table) {
            // Usar dropIndex em vez de dropIndexIfExists (compatível com Laravel 11)
            try {
                $table->dropIndex('idx_patr_nupatrimonio');
            } catch (\Exception $e) {
                // Index não existe, ignorar
            }
            
            try {
                $table->dropIndex('idx_patr_situacao_cdprojeto');
            } catch (\Exception $e) {
                // Index não existe, ignorar
            }
            
            try {
                $table->dropIndex('idx_patr_cdlocal');
            } catch (\Exception $e) {
                // Index não existe, ignorar
            }
        });
    }
};
