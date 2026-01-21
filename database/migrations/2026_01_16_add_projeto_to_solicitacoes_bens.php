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
            // Usar SQL direto para adicionar coluna (evita geração_expression error no MySQL antigo)
            DB::statement("ALTER TABLE `solicitacoes_bens` ADD `projeto_id` BIGINT UNSIGNED NULL AFTER `uf`");
            
            // Adicionar foreign key via schema (isso não lê information_schema)
            Schema::table('solicitacoes_bens', function (Blueprint $table) {
                try {
                    $table->foreign('projeto_id')->references('id')->on('tabfant')->onDelete('set null');
                } catch (\Exception $e) {
                    // FK pode já existir
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('solicitacoes_bens', function (Blueprint $table) {
            try {
                $table->dropForeign(['projeto_id']);
            } catch (\Exception $e) {
                // FK pode não existir
            }
            
            try {
                $table->dropColumn('projeto_id');
            } catch (\Exception $e) {
                // Coluna pode não existir
            }
        });
    }
};

