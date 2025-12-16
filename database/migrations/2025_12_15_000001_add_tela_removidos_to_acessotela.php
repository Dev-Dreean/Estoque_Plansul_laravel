<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verificar se tabela existe usando query raw
        try {
            $tableExists = DB::selectOne("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'acessotela'");
        } catch (\Exception $e) {
            return; // MySQL antigo, fallback silencioso
        }

        if (!$tableExists) {
            return;
        }

        // Usar raw SQL para compatibilidade com MySQL antigo (sem generation_expression)
        try {
            DB::statement("INSERT INTO acessotela (NUSEQTELA, DETELA, NMSISTEMA, FLACESSO) VALUES (1009, 'Removidos', 'Sistema Principal', 'S') ON DUPLICATE KEY UPDATE DETELA='Removidos', NMSISTEMA='Sistema Principal', FLACESSO='S'");
        } catch (\Exception $e) {
            // Fallback: tentar INSERT simples depois DELETE
            try {
                DB::statement("DELETE FROM acessotela WHERE NUSEQTELA = 1009");
            } catch (\Exception $e2) {}
            
            try {
                DB::statement("INSERT INTO acessotela (NUSEQTELA, DETELA, NMSISTEMA, FLACESSO) VALUES (1009, 'Removidos', 'Sistema Principal', 'S')");
            } catch (\Exception $e3) {}
        }
    }

    public function down(): void
    {
        try {
            DB::statement("DELETE FROM acessotela WHERE NUSEQTELA = 1009");
        } catch (\Exception $e) {}
    }
};

