<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LimparHistorico extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'historico:limpar {--force : Executa sem pedir confirmação}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa todos os registros da tabela de histórico (movpartr).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force')) {
            $confirm = $this->confirm('Tem certeza que deseja limpar TODOS os registros da tabela movpartr? Esta ação não pode ser desfeita.', false);
            if (!$confirm) {
                $this->info('Operação cancelada.');
                return self::SUCCESS;
            }
        }

        try {
            Schema::disableForeignKeyConstraints();
            DB::table('movpartr')->truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('Tabela movpartr limpa com sucesso.');
        return self::SUCCESS;
    }
}
