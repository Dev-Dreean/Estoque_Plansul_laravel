<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            if (!Schema::hasColumn('solicitacoes_bens', 'projeto_id')) {
                $table->unsignedBigInteger('projeto_id')->nullable()->after('uf');
                $table->foreign('projeto_id')->references('id')->on('tabfant')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            $table->dropForeign(['projeto_id']);
            $table->dropColumn('projeto_id');
        });
    }
};
