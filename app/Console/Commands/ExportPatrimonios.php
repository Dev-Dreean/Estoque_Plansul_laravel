<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportPatrimonios extends Command
{
    protected $signature = 'export:patrimonios';
    protected $description = 'Exporta tabela patr para arquivo SQL';

    public function handle()
    {
        $this->info("📊 Exportando tabela patrimonios...\n");
        
        // Obter todos os registros
        $patrimonios = DB::table('patr')->get();
        
        $this->info("Total de registros: " . count($patrimonios));
        
        // Criar arquivo SQL
        $filename = storage_path('app/patrimonios_dump.sql');
        $file = fopen($filename, 'w');
        
        // Header SQL - USAR O BANCO DO SERVIDOR (plansul04)
        fwrite($file, "-- Dump de patrimônios\n");
        fwrite($file, "-- Data: " . date('Y-m-d H:i:s') . "\n");
        fwrite($file, "-- Total de registros: " . count($patrimonios) . "\n");
        fwrite($file, "-- Banco de destino: plansul04\n\n");
        fwrite($file, "USE `plansul04`;\n\n");
        fwrite($file, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($file, "DELETE FROM patr;\n");
        fwrite($file, "SET FOREIGN_KEY_CHECKS=1;\n\n");
        
        // Insert statements
        foreach ($patrimonios as $patr) {
            $insert = "INSERT INTO patr VALUES (";
            $values = [];
            
            foreach ((array)$patr as $value) {
                if ($value === null) {
                    $values[] = "NULL";
                } else {
                    $values[] = "'" . addslashes($value) . "'";
                }
            }
            
            $insert .= implode(',', $values) . ");\n";
            fwrite($file, $insert);
        }
        
        fclose($file);
        
        if (file_exists($filename)) {
            $size = filesize($filename) / 1024 / 1024;
            $this->info("✅ Dump criado com sucesso!");
            $this->info("📁 Arquivo: storage/app/patrimonios_dump.sql");
            $this->info("📊 Tamanho: " . round($size, 2) . " MB");
        } else {
            $this->error("❌ Erro ao criar dump");
        }
    }
}
