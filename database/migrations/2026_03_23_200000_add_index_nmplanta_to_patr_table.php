<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Verificar se o índice já existe antes de criar
        $indexExists = collect(DB::select("SHOW INDEX FROM patr WHERE Key_name = 'patr_nmplanta_index'"))
            ->isNotEmpty();

        if (!$indexExists) {
            Schema::table('patr', function (Blueprint $table) {
                $table->index('NMPLANTA', 'patr_nmplanta_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('patr', function (Blueprint $table) {
            $table->dropIndexIfExists('patr_nmplanta_index');
        });
    }
};
