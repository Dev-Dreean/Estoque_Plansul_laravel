<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Correção do patrimônio 17466:
     * - CDLOCAL incorreto: 1632 (SERRO - projeto 736)
     * - CDLOCAL correto: 1642 (ALMOXARIFADO CENTRAL - projeto 999915)
     */
    public function up(): void
    {
        DB::table('patr')
            ->where('NUPATRIMONIO', 17466)
            ->update(['CDLOCAL' => 1642]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('patr')
            ->where('NUPATRIMONIO', 17466)
            ->update(['CDLOCAL' => 1632]);
    }
};
