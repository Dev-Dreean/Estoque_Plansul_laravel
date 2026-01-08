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
        // Usar SQL direto para evitar geração_expression do MySQL antigo
        try {
            \DB::statement('ALTER TABLE patr ADD INDEX idx_patr_nupatrimonio (NUPATRIMONIO)');
        } catch (\Exception $e) {
            // Índice já existe
        }
        
        try {
            \DB::statement('ALTER TABLE patr ADD INDEX idx_patr_situacao_cdprojeto (SITUACAO, CDPROJETO)');
        } catch (\Exception $e) {
            // Índice já existe
        }
        
        try {
            \DB::statement('ALTER TABLE patr ADD INDEX idx_patr_cdlocal (CDLOCAL)');
        } catch (\Exception $e) {
            // Índice já existe
        }
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
