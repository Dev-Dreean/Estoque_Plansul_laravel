<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = DB::select("SHOW COLUMNS FROM `solicitacoes_bens` LIKE 'logistics_asset_number'");
        if (!empty($columns)) {
            return;
        }

        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            $table->string('logistics_asset_number', 50)
                ->nullable()
                ->after('logistics_volume_count')
                ->comment('Número do patrimônio informado na etapa de logística');
        });
    }

    public function down(): void
    {
        $columns = DB::select("SHOW COLUMNS FROM `solicitacoes_bens` LIKE 'logistics_asset_number'");
        if (empty($columns)) {
            return;
        }

        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            $table->dropColumn('logistics_asset_number');
        });
    }
};
