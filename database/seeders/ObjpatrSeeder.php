<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ObjpatrSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('objpatr')->insert([
            ['NUSEQOBJ' => 1, 'NUSEQTIPOPATR' => 1, 'DEOBJETO' => 'Cadeira de Escritório Giratória'],
            ['NUSEQOBJ' => 2, 'NUSEQTIPOPATR' => 1, 'DEOBJETO' => 'Mesa de Reunião 6 Lugares'],
            ['NUSEQOBJ' => 101, 'NUSEQTIPOPATR' => 2, 'DEOBJETO' => 'Notebook Dell Vostro 15'],
            ['NUSEQOBJ' => 102, 'NUSEQTIPOPATR' => 2, 'DEOBJETO' => 'Monitor 24 polegadas LG'],
        ]);
    }
}