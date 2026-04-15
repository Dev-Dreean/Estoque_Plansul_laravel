<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMySqlMode extends Command
{
    protected $signature = 'fix:mysql-mode';
    protected $description = 'Fix MySQL sql_mode for Laravel compatibility';

    public function handle()
    {
        try {
            DB::statement("SET SESSION sql_mode=''");
            DB::statement("SET GLOBAL sql_mode=''");
            $this->info('✓ SQL_MODE configurado para vazio (compatível com Laravel)');
        } catch (\Exception $e) {
            $this->error('Erro ao configurar sql_mode: ' . $e->getMessage());
        }
    }
}
