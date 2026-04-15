<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Desabilita sql_mode strict temporariamente para contornar datas 0000-00-00 existentes
        DB::statement("SET SESSION sql_mode=''");

        Schema::table('funcionarios', function (Blueprint $table) {
            $table->timestamp('synced_at')->nullable()->after('UFPROJ');
        });
    }

    public function down(): void
    {
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->dropColumn('synced_at');
        });
    }
};
