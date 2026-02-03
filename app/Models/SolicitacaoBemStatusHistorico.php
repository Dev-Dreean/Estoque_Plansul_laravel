<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitacaoBemStatusHistorico extends Model
{
    protected $table = 'solicitacoes_bens_status_historico';

    protected $fillable = [
        'solicitacao_id',
        'status_anterior',
        'status_novo',
        'acao',
        'motivo',
        'usuario_id',
    ];

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoBem::class, 'solicitacao_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id', 'NUSEQUSUARIO');
    }
}
