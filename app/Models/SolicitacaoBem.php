<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitacaoBem extends Model
{
    public const STATUS_PENDENTE = 'PENDENTE';
    public const STATUS_SEPARADO = 'SEPARADO';
    public const STATUS_CONCLUIDO = 'CONCLUIDO';
    public const STATUS_CANCELADO = 'CANCELADO';

    protected $table = 'solicitacoes_bens';

    protected $fillable = [
        'solicitante_id',
        'solicitante_nome',
        'solicitante_matricula',
        'projeto_id',
        'uf',
        'setor',
        'local_destino',
        'status',
        'observacao',
        'observacao_controle',
        'matricula_recebedor',
        'nome_recebedor',
        'separado_por_id',
        'separado_em',
        'concluido_por_id',
        'concluido_em',
        'email_confirmacao_enviado_em',
    ];

    protected $casts = [
        'separado_em' => 'datetime',
        'concluido_em' => 'datetime',
        'email_confirmacao_enviado_em' => 'datetime',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDENTE,
            self::STATUS_SEPARADO,
            self::STATUS_CONCLUIDO,
            self::STATUS_CANCELADO,
        ];
    }

    public function itens(): HasMany
    {
        return $this->hasMany(SolicitacaoBemItem::class, 'solicitacao_id');
    }

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Tabfant::class, 'projeto_id', 'id');
    }
}
