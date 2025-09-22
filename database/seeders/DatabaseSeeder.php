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
            // Adicione o novo seeder aqui, preferencialmente no início
            FuncionarioSeeder::class,

            // Seeders que já tínhamos
            TipoPatrSeeder::class,
            ObjetoPatrSeeder::class,
            TabfantSeeder::class,
            LocaisProjetoSeeder::class,
        ]);
    }
}
