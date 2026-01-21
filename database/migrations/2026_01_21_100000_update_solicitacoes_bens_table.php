<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            // Campo de rastreio
            $table->string('tracking_code', 100)->nullable()->comment('Código de rastreio do item');
            
            // Tipo de destino: FILIAL ou PROJETO
            $table->enum('destination_type', ['FILIAL', 'PROJETO'])->default('PROJETO')->comment('Para onde o item será enviado');
            
            // Justificativa de cancelamento
            $table->text('justificativa_cancelamento')->nullable()->comment('Motivo do cancelamento');
            
            // Segundo nível de confirmação (aprovador final)
            $table->unsignedBigInteger('confirmado_por_id')->nullable()->comment('Usuário que fez confirmação final');
            $table->timestamp('confirmado_em')->nullable()->comment('Data da confirmação final');
            
            // Rastreamento de cancelamento
            $table->unsignedBigInteger('cancelado_por_id')->nullable()->comment('Usuário que cancelou');
            $table->timestamp('cancelado_em')->nullable()->comment('Data do cancelamento');
        });
    }

    public function down(): void
    {
        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            $table->dropColumn([
                'tracking_code',
                'destination_type',
                'justificativa_cancelamento',
                'confirmado_por_id',
                'confirmado_em',
                'cancelado_por_id',
                'cancelado_em',
            ]);
        });
    }
};
