<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verificar se a coluna já existe (compatível com MySQL antigo do KingHost)
        $columns = DB::select("SHOW COLUMNS FROM `solicitacoes_bens`");
        $columnNames = collect($columns)->pluck('Field')->toArray();
        
        if (!in_array('projeto_id', $columnNames)) {
            // Usar SQL direto para adicionar coluna (evita generation_expression error no MySQL antigo)
            DB::statement("ALTER TABLE `solicitacoes_bens` ADD `projeto_id` BIGINT UNSIGNED NULL AFTER `uf`");
        }
    }

    public function down(): void
    {
        // Remover coluna se existir
        $columns = DB::select("SHOW COLUMNS FROM `solicitacoes_bens`");
        $columnNames = collect($columns)->pluck('Field')->toArray();
        
        if (in_array('projeto_id', $columnNames)) {
            try {
                DB::statement("ALTER TABLE `solicitacoes_bens` DROP COLUMN `projeto_id`");
            } catch (\Exception $e) {
                // Coluna pode não existir
            }
        }
    }
};

