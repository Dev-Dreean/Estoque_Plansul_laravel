<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoMovimentacao extends Model
{
    use HasFactory;

    protected $table = 'historico_movimentacoes';
    public $timestamps = false;

    protected $fillable = [
        'TIPO',
        'CAMPO',
        'VALOR_ANTIGO',
        'VALOR_NOVO',
        'NUPATR',
        'CODPROJ',
        'USUARIO',
        'CO_AUTOR',
        'DTOPERACAO',
    ];
}
