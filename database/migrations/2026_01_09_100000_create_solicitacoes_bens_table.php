<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacoes_bens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('solicitante_id')->nullable();
            $table->string('solicitante_nome', 120)->nullable();
            $table->string('solicitante_matricula', 20)->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('setor', 120)->nullable();
            $table->string('local_destino', 150)->nullable();
            $table->string('status', 20)->default('PENDENTE');
            $table->text('observacao')->nullable();
            $table->text('observacao_controle')->nullable();
            $table->string('matricula_recebedor', 20)->nullable();
            $table->string('nome_recebedor', 120)->nullable();
            $table->unsignedBigInteger('separado_por_id')->nullable();
            $table->timestamp('separado_em')->nullable();
            $table->unsignedBigInteger('concluido_por_id')->nullable();
            $table->timestamp('concluido_em')->nullable();
            $table->timestamp('email_confirmacao_enviado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_bens');
    }
};
