<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObjetoPatr extends Model
{
    use HasFactory;

    protected $table = 'OBJETOPATR';
    // Eloquent não suporta chaves primárias compostas nativamente para find(), mas o model funciona para outras queries.
    protected $primaryKey = 'NUSEQOBJETO';
    public $timestamps = false;

    protected $fillable = [
        'NUSEQOBJETO',
        'NUSEQTIPOPATR',
        'DEOBJETO',
    ];
}
