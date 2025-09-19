<?php
// DENTRO DE app/Models/HistoricoMovimentacao.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoMovimentacao extends Model
{
    use HasFactory;

    // A MUDANÇA É AQUI:
    protected $table = 'movpartr'; // Antes era 'historico_movimentacoes'

    public $timestamps = false;

    protected $fillable = [
        'NUPATR',
        'CODPROJ',
        'USUARIO',
        'DTOPERACAO',
        'TIPO',
        'CAMPO',
        'VALOR_ANTIGO',
        'VALOR_NOVO',
        'CO_AUTOR',
    ];
}
