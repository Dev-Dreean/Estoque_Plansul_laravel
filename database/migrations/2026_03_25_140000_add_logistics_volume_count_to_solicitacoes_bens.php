<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            $table->unsignedInteger('logistics_volume_count')
                ->nullable()
                ->after('logistics_weight_kg')
                ->comment('Quantidade de volumes da logística');
        });
    }

    public function down(): void
    {
        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            $table->dropColumn('logistics_volume_count');
        });
    }
};
