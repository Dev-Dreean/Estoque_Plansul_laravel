<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Apenas renomeia a tabela existente.
        Schema::rename('historico_movimentacoes', 'movpartr');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Permite reverter a alteração, se necessário.
        Schema::rename('movpartr', 'historico_movimentacoes');
    }
};
