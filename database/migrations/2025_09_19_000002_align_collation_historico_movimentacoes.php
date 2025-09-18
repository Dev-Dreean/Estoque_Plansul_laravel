<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Align table and its text columns to MySQL 8 default utf8mb4_0900_ai_ci to match usuario.NMLOGIN
        DB::statement('ALTER TABLE historico_movimentacoes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
    }

    public function down(): void
    {
        // Revert to utf8mb4_unicode_ci if needed
        DB::statement('ALTER TABLE historico_movimentacoes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }
};
