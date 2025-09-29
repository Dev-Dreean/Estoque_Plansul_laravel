<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Desabilita as FKs para permitir truncates em tabelas referenciadas
        Schema::disableForeignKeyConstraints();

        try {
            $this->call([
                // Adicione o novo seeder aqui, preferencialmente no início
                FuncionarioSeeder::class,

                // Seeders que já tínhamos
                TipoPatrSeeder::class,
                ObjetoPatrSeeder::class,
                TabfantSeeder::class,
                LocaisProjetoSeeder::class,
            ]);
        } finally {
            // Reabilita as FKs após o seed
            Schema::enableForeignKeyConstraints();
        }
    }
}
