<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Verificar usando query raw (compatível com MySQL antigo)
        try {
            $hasColumn = DB::selectOne("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'supervisor_de'");
        } catch (\Exception $e) {
            return; // MySQL antigo, fallback silencioso
        }

        if ($hasColumn) {
            try {
                // Usar ALTER TABLE raw SQL para compatibilidade
                DB::statement("ALTER TABLE usuario DROP COLUMN supervisor_de");
            } catch (\Exception $e) {
                // Ignorar se coluna não existe ou já foi dropada
            }
        }
    }

    public function down(): void
    {
        // Verificar se coluna existe
        try {
            $hasColumn = DB::selectOne("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'supervisor_de'");
        } catch (\Exception $e) {
            return;
        }

        if (!$hasColumn) {
            try {
                DB::statement("ALTER TABLE usuario ADD COLUMN supervisor_de LONGTEXT NULL AFTER CDMATRFUNCIONARIO");
            } catch (\Exception $e) {
                // Ignorar se coluna já existe
            }
        }
    }
};
