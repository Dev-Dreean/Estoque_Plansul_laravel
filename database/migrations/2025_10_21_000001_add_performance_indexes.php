<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ⚡ Adiciona índices críticos para performance de buscas
     * Executa: php artisan migrate
     */
    public function up(): void
    {
        // Índices na tabela objeto_patr para busca de códigos
        Schema::table('objeto_patr', function (Blueprint $table) {
            // Índice para LIKE em NUSEQOBJETO (código)
            if (!Schema::hasIndex('objeto_patr', 'idx_nuseqobjeto')) {
                $table->index('NUSEQOBJETO', 'idx_nuseqobjeto');
            }
            // Índice para busca em DEOBJETO (descrição)
            if (!Schema::hasIndex('objeto_patr', 'idx_deobjeto')) {
                $table->index('DEOBJETO', 'idx_deobjeto');
            }
        });

        // Índices na tabela tabfant para busca de projetos
        Schema::table('tabfant', function (Blueprint $table) {
            // Índice crítico para LIKE em CDPROJETO (código)
            if (!Schema::hasIndex('tabfant', 'idx_cdprojeto')) {
                $table->index('CDPROJETO', 'idx_cdprojeto');
            }
            // Índice para busca em NOMEPROJETO
            if (!Schema::hasIndex('tabfant', 'idx_nomeprojeto')) {
                $table->index('NOMEPROJETO', 'idx_nomeprojeto');
            }
        });

        // Índices na tabela locais_projetos para relações
        Schema::table('locais_projetos', function (Blueprint $table) {
            // Índices compostos para filtros comuns
            if (!Schema::hasIndex('locais_projetos', 'idx_cdlocal_flativo')) {
                $table->index(['cdlocal', 'flativo'], 'idx_cdlocal_flativo');
            }
            if (!Schema::hasIndex('locais_projetos', 'idx_tabfant_flativo')) {
                $table->index(['tabfant_id', 'flativo'], 'idx_tabfant_flativo');
            }
            // Índice para busca em delocal
            if (!Schema::hasIndex('locais_projetos', 'idx_delocal')) {
                $table->index('delocal', 'idx_delocal');
            }
        });
    }

    /**
     * Reverter migração
     */
    public function down(): void
    {
        Schema::table('objeto_patr', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_nuseqobjeto');
            $table->dropIndexIfExists('idx_deobjeto');
        });

        Schema::table('tabfant', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_cdprojeto');
            $table->dropIndexIfExists('idx_nomeprojeto');
        });

        Schema::table('locais_projetos', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_cdlocal_flativo');
            $table->dropIndexIfExists('idx_tabfant_flativo');
            $table->dropIndexIfExists('idx_delocal');
        });
    }
};
