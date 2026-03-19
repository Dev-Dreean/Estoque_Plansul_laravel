<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('termos_responsabilidade_arquivos')) {
            Schema::create('termos_responsabilidade_arquivos', function (Blueprint $table) {
                $table->id();
                $table->string('cdprojeto', 20)->index();
                $table->string('cdmatrfuncionario', 30)->index();
                $table->string('nome_arquivo', 180);
                $table->string('caminho_arquivo', 255);
                $table->unsignedInteger('total_itens')->default(0);
                $table->string('origem', 20)->nullable();
                $table->string('gerado_por', 80)->nullable();
                $table->dateTime('gerado_em')->nullable()->index();
            });
        }

        if (!Schema::hasTable('termos_responsabilidade_arquivo_itens')) {
            Schema::create('termos_responsabilidade_arquivo_itens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('termo_responsabilidade_arquivo_id');
                $table->unsignedInteger('nuseqpatr')->index();

                $table->index(['termo_responsabilidade_arquivo_id', 'nuseqpatr'], 'idx_termo_resp_arquivo_item');
                $table->foreign('termo_responsabilidade_arquivo_id', 'fk_termo_resp_arquivo_item')
                    ->references('id')
                    ->on('termos_responsabilidade_arquivos')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('termos_responsabilidade_arquivo_itens')) {
            Schema::table('termos_responsabilidade_arquivo_itens', function (Blueprint $table) {
                $table->dropForeign('fk_termo_resp_arquivo_item');
            });

            Schema::dropIfExists('termos_responsabilidade_arquivo_itens');
        }

        Schema::dropIfExists('termos_responsabilidade_arquivos');
    }
};
