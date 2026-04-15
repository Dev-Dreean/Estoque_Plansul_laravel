<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitacaoBemNotificacaoUsuario extends Model
{
    protected $table = 'solicitacoes_bens_notificacao_usuarios';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'papel',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id', 'NUSEQUSUARIO');
    }
}
