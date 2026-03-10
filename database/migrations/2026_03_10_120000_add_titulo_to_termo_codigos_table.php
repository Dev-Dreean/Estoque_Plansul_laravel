<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('termo_codigos')) {
            return;
        }

        $colunaExiste = DB::select("SHOW COLUMNS FROM termo_codigos LIKE 'titulo'");

        if (empty($colunaExiste)) {
            DB::statement("ALTER TABLE termo_codigos ADD COLUMN titulo VARCHAR(120) NULL AFTER codigo");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('termo_codigos')) {
            return;
        }

        $colunaExiste = DB::select("SHOW COLUMNS FROM termo_codigos LIKE 'titulo'");

        if (!empty($colunaExiste)) {
            DB::statement("ALTER TABLE termo_codigos DROP COLUMN titulo");
        }
    }
};
