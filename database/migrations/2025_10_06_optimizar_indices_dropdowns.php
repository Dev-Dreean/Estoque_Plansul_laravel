<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        // Locais
        if (Schema::hasTable('locais_projeto')) {
            try {
                Schema::table('locais_projeto', function (Blueprint $table) {
                    $table->index('cdlocal', 'idx_cdlocal');
                    $table->index('delocal', 'idx_delocal');
                });
            } catch (\Illuminate\Database\QueryException $e) {
                // Ignora erro de índice já existente
                if (stripos($e->getMessage(), 'duplicate') === false && stripos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
        // Projetos
        if (Schema::hasTable('tabfant')) {
            try {
                Schema::table('tabfant', function (Blueprint $table) {
                    $table->index('NOMEPROJETO', 'idx_nomeprojeto');
                    $table->index('CDPROJETO', 'idx_cdprojeto');
                });
            } catch (\Illuminate\Database\QueryException $e) {
                if (stripos($e->getMessage(), 'duplicate') === false && stripos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
        // Códigos
        try {
            if (Schema::hasTable('codigos')) {
                Schema::table('codigos', function (Blueprint $table) {
                    $table->index('CODOBJETO', 'idx_codobjeto');
                    $table->index('DESCRICAO', 'idx_descricao');
                });
            }
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'duplicate') === false && stripos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
        // Funcionários/Usuários
        try {
            if (Schema::hasTable('funcionarios')) {
                Schema::table('funcionarios', function (Blueprint $table) {
                    $table->index('CDMATRFUNCIONARIO', 'idx_cdmatrfuncionario');
                    $table->index('NMFUNCIONARIO', 'idx_nmfuncionario');
                });
            }
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'duplicate') === false && stripos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('locais_projeto')) {
            try {
                Schema::table('locais_projeto', function (Blueprint $table) {
                    $table->dropIndex('idx_cdlocal');
                    $table->dropIndex('idx_delocal');
                });
            } catch (\Illuminate\Database\QueryException $e) {
                if (stripos($e->getMessage(), 'doesn\'t exist') === false && stripos($e->getMessage(), 'unknown') === false) {
                    throw $e;
                }
            }
        }

        if (Schema::hasTable('tabfant')) {
            try {
                Schema::table('tabfant', function (Blueprint $table) {
                    $table->dropIndex('idx_nomeprojeto');
                    $table->dropIndex('idx_cdprojeto');
                });
            } catch (\Illuminate\Database\QueryException $e) {
                if (stripos($e->getMessage(), 'doesn\'t exist') === false && stripos($e->getMessage(), 'unknown') === false) {
                    throw $e;
                }
            }
        }

        try {
            if (Schema::hasTable('codigos')) {
                Schema::table('codigos', function (Blueprint $table) {
                    $table->dropIndex('idx_codobjeto');
                    $table->dropIndex('idx_descricao');
                });
            }
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'doesn\'t exist') === false && stripos($e->getMessage(), 'unknown') === false) {
                throw $e;
            }
        }

        try {
            if (Schema::hasTable('funcionarios')) {
                Schema::table('funcionarios', function (Blueprint $table) {
                    $table->dropIndex('idx_cdmatrfuncionario');
                    $table->dropIndex('idx_nmfuncionario');
                });
            }
        } catch (\Illuminate\Database\QueryException $e) {
            if (stripos($e->getMessage(), 'doesn\'t exist') === false && stripos($e->getMessage(), 'unknown') === false) {
                throw $e;
            }
        }
    }
};
