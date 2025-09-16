<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historico_movimentacoes', function (Blueprint $table) {
            $table->string('TIPO', 30)->nullable()->after('id'); // projeto|situacao|termo
            $table->string('CAMPO', 30)->nullable()->after('TIPO'); // CDPROJETO|SITUACAO|NMPLANTA
            $table->string('VALOR_ANTIGO', 191)->nullable()->after('CAMPO');
            $table->string('VALOR_NOVO', 191)->nullable()->after('VALOR_ANTIGO');
            $table->index(['TIPO']);
            $table->index(['CAMPO']);
        });
    }

    public function down(): void
    {
        Schema::table('historico_movimentacoes', function (Blueprint $table) {
            $table->dropIndex(['TIPO']);
            $table->dropIndex(['CAMPO']);
            $table->dropColumn(['TIPO', 'CAMPO', 'VALOR_ANTIGO', 'VALOR_NOVO']);
        });
    }
};
