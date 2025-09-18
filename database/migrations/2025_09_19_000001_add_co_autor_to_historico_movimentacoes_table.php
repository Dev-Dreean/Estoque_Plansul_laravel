<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historico_movimentacoes', function (Blueprint $table) {
            $table->string('CO_AUTOR', 100)->nullable()->after('USUARIO');
            $table->index(['CO_AUTOR']);
        });
    }

    public function down(): void
    {
        Schema::table('historico_movimentacoes', function (Blueprint $table) {
            $table->dropIndex(['CO_AUTOR']);
            $table->dropColumn('CO_AUTOR');
        });
    }
};
