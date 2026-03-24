<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SolicitacaoBem extends Model
{
    public const STATUS_PENDENTE = 'PENDENTE';
    public const STATUS_AGUARDANDO_CONFIRMACAO = 'AGUARDANDO_CONFIRMACAO';
    public const STATUS_LIBERACAO = 'LIBERACAO';
    public const STATUS_CONFIRMADO = 'CONFIRMADO';
    public const STATUS_NAO_ENVIADO = 'NAO_ENVIADO';
    public const STATUS_NAO_RECEBIDO = 'NAO_RECEBIDO';
    public const STATUS_RECEBIDO = 'RECEBIDO';
    public const STATUS_CANCELADO = 'CANCELADO';
    public const STATUS_ARQUIVADO = 'ARQUIVADO';
    
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
        'logistics_height_cm',
        'logistics_width_cm',
        'logistics_length_cm',
        'logistics_weight_kg',
        'logistics_notes',
        'logistics_registered_by_id',
        'logistics_registered_at',
        'quote_transporter',
        'quote_amount',
        'quote_deadline',
        'quote_notes',
        'quote_registered_by_id',
        'quote_registered_at',
        'quote_approved_by_id',
        'quote_approved_at',
        'invoice_number',
        'shipped_by_id',
        'shipped_at',
    ];

    protected $casts = [
        'confirmado_em' => 'datetime',
        'cancelado_em' => 'datetime',
        'email_confirmacao_enviado_em' => 'datetime',
        'logistics_height_cm' => 'decimal:2',
        'logistics_width_cm' => 'decimal:2',
        'logistics_length_cm' => 'decimal:2',
        'logistics_weight_kg' => 'decimal:3',
        'quote_amount' => 'decimal:2',
        'logistics_registered_at' => 'datetime',
        'quote_registered_at' => 'datetime',
        'quote_approved_at' => 'datetime',
        'shipped_at' => 'datetime',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDENTE,
            self::STATUS_AGUARDANDO_CONFIRMACAO,
            self::STATUS_LIBERACAO,
            self::STATUS_CONFIRMADO,
            self::STATUS_NAO_ENVIADO,
            self::STATUS_NAO_RECEBIDO,
            self::STATUS_RECEBIDO,
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

    public function ultimoHistoricoStatus(): HasOne
    {
        return $this->hasOne(SolicitacaoBemStatusHistorico::class, 'solicitacao_id')->latestOfMany();
    }

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Tabfant::class, 'projeto_id', 'id');
    }

    public function usuariosComPermissao(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'solicitacoes_bens_permissoes',
            'solicitacao_id',
            'usuario_id',
            'id',
            'NUSEQUSUARIO'
        )->withPivot(['liberado_por_id'])->withTimestamps();
    }

    public function hasLogisticsData(): bool
    {
        return $this->logistics_height_cm !== null
            || $this->logistics_width_cm !== null
            || $this->logistics_length_cm !== null
            || $this->logistics_weight_kg !== null
            || trim((string) ($this->logistics_notes ?? '')) !== ''
            || $this->logistics_registered_at !== null;
    }

    public function hasQuoteData(): bool
    {
        return trim((string) ($this->quote_transporter ?? '')) !== ''
            || $this->quote_amount !== null
            || trim((string) ($this->quote_deadline ?? '')) !== ''
            || trim((string) ($this->quote_notes ?? '')) !== ''
            || $this->quote_registered_at !== null;
    }

    public function hasShipmentData(): bool
    {
        return trim((string) ($this->tracking_code ?? '')) !== ''
            || trim((string) ($this->invoice_number ?? '')) !== ''
            || $this->shipped_at !== null;
    }

    public function isAwaitingRequesterDecision(): bool
    {
        return $this->status === self::STATUS_CONFIRMADO
            && $this->hasQuoteData()
            && $this->quote_approved_at === null
            && !$this->hasShipmentData();
    }

    public function isReadyToShip(): bool
    {
        return $this->status === self::STATUS_CONFIRMADO
            && $this->hasQuoteData()
            && $this->quote_approved_at !== null
            && !$this->hasShipmentData();
    }
}
