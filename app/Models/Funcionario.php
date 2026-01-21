<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Funcionario extends Model
{
    use HasFactory;

    protected $table = 'funcionarios';
    protected $primaryKey = 'CDMATRFUNCIONARIO';
    public $incrementing = false; // A chave primária não é auto-incremento
    protected $keyType = 'string'; // A chave primária é uma string
    public $timestamps = false;

    protected $fillable = [
        'CDMATRFUNCIONARIO',
        'NMFUNCIONARIO',
        'DTADMISSAO',
        'CDCARGO',
        'CODFIL',
        'UFPROJ',
    ];
}

