<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ⚡ Adiciona índices críticos para performance de buscas
     * Executa: php artisan migrate
     */
    public function up(): void
    {
        // Índices na tabela objeto_patr para busca de códigos
        if (Schema::hasTable('objeto_patr')) {
            try {
                DB::statement('ALTER TABLE `objeto_patr` ADD INDEX `idx_nuseqobjeto` (`NUSEQOBJETO`)');
            } catch (\Exception $e) {
                // Índice já existe
            }
        }

        // Índices na tabela tabfant para busca de projetos
        if (Schema::hasTable('tabfant')) {
            try {
                // Índice crítico para LIKE em CDPROJETO (código)
                DB::statement('ALTER TABLE `tabfant` ADD INDEX `idx_cdprojeto` (`CDPROJETO`)');
            } catch (\Exception $e) {
                // Índice já existe
            }
            try {
                // Índice com prefix para NOMEPROJETO (para evitar erro de key length)
                DB::statement('ALTER TABLE `tabfant` ADD INDEX `idx_nomeprojeto` (`NOMEPROJETO`(100))');
            } catch (\Exception $e) {
                // Índice já existe
            }
        }

        // Índices na tabela locais_projetos para relações
        if (Schema::hasTable('locais_projetos')) {
            try {
                DB::statement('ALTER TABLE `locais_projetos` ADD INDEX `idx_cdlocal_flativo` (`cdlocal`, `flativo`)');
            } catch (\Exception $e) {
                // Índice já existe
            }
            try {
                DB::statement('ALTER TABLE `locais_projetos` ADD INDEX `idx_tabfant_flativo` (`tabfant_id`, `flativo`)');
            } catch (\Exception $e) {
                // Índice já existe
            }
            try {
                DB::statement('ALTER TABLE `locais_projetos` ADD INDEX `idx_delocal` (`delocal`(100))');
            } catch (\Exception $e) {
                // Índice já existe
            }
        }
    }
    /**
     * Reverter migração
     */
    public function down(): void
    {
        // Remover índices criados
        if (Schema::hasTable('objeto_patr')) {
            try {
                DB::statement('ALTER TABLE `objeto_patr` DROP INDEX `idx_nuseqobjeto`');
            } catch (\Exception $e) {}
        }

        if (Schema::hasTable('tabfant')) {
            try {
                DB::statement('ALTER TABLE `tabfant` DROP INDEX `idx_cdprojeto`');
            } catch (\Exception $e) {}
            try {
                DB::statement('ALTER TABLE `tabfant` DROP INDEX `idx_nomeprojeto`');
            } catch (\Exception $e) {}
        }

        if (Schema::hasTable('locais_projetos')) {
            try {
                DB::statement('ALTER TABLE `locais_projetos` DROP INDEX `idx_cdlocal_flativo`');
            } catch (\Exception $e) {}
            try {
                DB::statement('ALTER TABLE `locais_projetos` DROP INDEX `idx_tabfant_flativo`');
            } catch (\Exception $e) {}
            try {
                DB::statement('ALTER TABLE `locais_projetos` DROP INDEX `idx_delocal`');
            } catch (\Exception $e) {}
        }
    }
};

