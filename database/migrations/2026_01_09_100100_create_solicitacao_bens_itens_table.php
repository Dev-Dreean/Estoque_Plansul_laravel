<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacao_bens_itens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('solicitacao_id');
            $table->string('descricao', 200);
            $table->unsignedInteger('quantidade')->default(1);
            $table->string('unidade', 20)->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index('solicitacao_id', 'idx_solicitacao_itens');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacao_bens_itens');
    }
};
