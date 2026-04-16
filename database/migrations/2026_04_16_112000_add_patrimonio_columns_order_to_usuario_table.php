<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Usar SQL raw para compatibilidade com MySQL antigo do KingHost
        // Usar LONGTEXT em vez de JSON (KingHost é muito antigo e não suporta JSON type)
        $result = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'patrimonio_columns_order'");
        
        if (empty($result)) {
            DB::statement('ALTER TABLE usuario ADD COLUMN patrimonio_columns_order LONGTEXT NULL AFTER theme');
        }
    }

    public function down(): void
    {
        $result = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'patrimonio_columns_order'");
        
        if (!empty($result)) {
            DB::statement('ALTER TABLE usuario DROP COLUMN patrimonio_columns_order');
        }
    }
};
