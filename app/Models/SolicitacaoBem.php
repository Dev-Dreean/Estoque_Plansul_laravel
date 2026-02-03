<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitacaoBem extends Model
{
    public const STATUS_PENDENTE = 'PENDENTE';
    public const STATUS_AGUARDANDO_CONFIRMACAO = 'AGUARDANDO_CONFIRMACAO';
    public const STATUS_CONFIRMADO = 'CONFIRMADO';
    public const STATUS_CANCELADO = 'CANCELADO';
    
    public const DESTINATION_FILIAL = 'FILIAL';
    public const DESTINATION_PROJETO = 'PROJETO';

    protected $table = 'solicitacoes_bens';

    protected $fillable = [
        'solicitante_id',
        'solicitante_nome',
        'solicitante_matricula',
        'email_origem',
        'email_assunto',
        'projeto_id',
        'uf',
        'setor',
        'local_destino',
        'status',
        'observacao',
        'observacao_controle',
        'matricula_recebedor',
        'nome_recebedor',
        'tracking_code',
        'destination_type',
        'justificativa_cancelamento',
        'confirmado_por_id',
        'cancelado_por_id',
        'email_confirmacao_enviado_em',
    ];

    protected $casts = [
        'confirmado_em' => 'datetime',
        'cancelado_em' => 'datetime',
        'email_confirmacao_enviado_em' => 'datetime',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDENTE,
            self::STATUS_AGUARDANDO_CONFIRMACAO,
            self::STATUS_CONFIRMADO,
            self::STATUS_CANCELADO,
        ];
    }
    
    public static function destinationTypeOptions(): array
    {
        return [
            self::DESTINATION_FILIAL => 'Filial',
            self::DESTINATION_PROJETO => 'Projeto',
        ];
    }

    public function itens(): HasMany
    {
        return $this->hasMany(SolicitacaoBemItem::class, 'solicitacao_id');
    }

    public function historicoStatus(): HasMany
    {
        return $this->hasMany(SolicitacaoBemStatusHistorico::class, 'solicitacao_id');
    }

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Tabfant::class, 'projeto_id', 'id');
    }
}
