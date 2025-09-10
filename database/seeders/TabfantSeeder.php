<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TabfantSeeder extends Seeder {
    public function run(): void {
        DB::table('tabfant')->insert([
            ['CDPROJETO' => 101, 'NOMEPROJETO' => 'Implantação Fibra Óptica SC', 'LOCAL' => 'Sede Florianópolis'],
            ['CDPROJETO' => 101, 'NOMEPROJETO' => 'Implantação Fibra Óptica SC', 'LOCAL' => 'Data Center São José'],
            ['CDPROJETO' => 202, 'NOMEPROJETO' => 'Renovação Contrato Cliente X', 'LOCAL' => 'Matriz Curitiba'],
            ['CDPROJETO' => 202, 'NOMEPROJETO' => 'Renovação Contrato Cliente X', 'LOCAL' => 'Filial São Paulo'],
        ]);
    }
}