<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitacaoBemItem extends Model
{
    protected $table = 'solicitacao_bens_itens';

    protected $fillable = [
        'solicitacao_id',
        'descricao',
        'quantidade',
        'unidade',
        'observacao',
    ];

    protected $casts = [
        'quantidade' => 'integer',
    ];

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoBem::class, 'solicitacao_id');
    }
}

