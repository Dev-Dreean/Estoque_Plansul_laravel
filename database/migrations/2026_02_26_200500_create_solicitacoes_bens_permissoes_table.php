<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('solicitacoes_bens_permissoes')) {
            return;
        }

        Schema::create('solicitacoes_bens_permissoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitacao_id');
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('liberado_por_id')->nullable();
            $table->timestamps();

            $table->unique(['solicitacao_id', 'usuario_id'], 'uniq_sol_bem_usuario');
            $table->index('usuario_id', 'idx_sol_bem_perm_usuario');
            $table->index('liberado_por_id', 'idx_sol_bem_perm_liberador');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_bens_permissoes');
    }
};

