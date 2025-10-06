<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OptimizarIndicesDropdowns extends Migration
{
    public function up()
    {
        // Locais
        Schema::table('locais_projeto', function (Blueprint $table) {
            $table->index('cdlocal', 'idx_cdlocal');
            $table->index('delocal', 'idx_delocal');
        });
        // Projetos
        Schema::table('tabfant', function (Blueprint $table) {
            $table->index('NOMEPROJETO', 'idx_nomeprojeto');
            $table->index('CDPROJETO', 'idx_cdprojeto');
        });
        // Códigos
        if (Schema::hasTable('codigos')) {
            Schema::table('codigos', function (Blueprint $table) {
                $table->index('CODOBJETO', 'idx_codobjeto');
                $table->index('DESCRICAO', 'idx_descricao');
            });
        }
        // Funcionários/Usuários
        if (Schema::hasTable('funcionarios')) {
            Schema::table('funcionarios', function (Blueprint $table) {
                $table->index('CDMATRFUNCIONARIO', 'idx_cdmatrfuncionario');
                $table->index('NMFUNCIONARIO', 'idx_nmfuncionario');
            });
        }
    }

    public function down()
    {
        Schema::table('locais_projeto', function (Blueprint $table) {
            $table->dropIndex('idx_cdlocal');
            $table->dropIndex('idx_delocal');
        });
        Schema::table('tabfant', function (Blueprint $table) {
            $table->dropIndex('idx_nomeprojeto');
            $table->dropIndex('idx_cdprojeto');
        });
        if (Schema::hasTable('codigos')) {
            Schema::table('codigos', function (Blueprint $table) {
                $table->dropIndex('idx_codobjeto');
                $table->dropIndex('idx_descricao');
            });
        }
        if (Schema::hasTable('funcionarios')) {
            Schema::table('funcionarios', function (Blueprint $table) {
                $table->dropIndex('idx_cdmatrfuncionario');
                $table->dropIndex('idx_nmfuncionario');
            });
        }
    }
}
