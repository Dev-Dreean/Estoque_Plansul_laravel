<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // PRIMEIRO, popula a tabela de projetos 'tabfant'
            TabfantSeeder::class,

            // DEPOIS, popula a tabela de locais e cria a relação
            LocaisProjetoSeeder::class,
        ]);
    }
}
