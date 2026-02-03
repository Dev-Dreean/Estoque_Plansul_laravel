<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacoes_bens_status_historico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitacao_id');
            $table->string('status_anterior', 40)->nullable();
            $table->string('status_novo', 40);
            $table->string('acao', 40)->nullable();
            $table->text('motivo')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();

            $table->index('solicitacao_id');
            $table->index('status_novo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_bens_status_historico');
    }
};
