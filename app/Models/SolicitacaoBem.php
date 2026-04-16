<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class SolicitacaoBem extends Model
{
    private static array $cachedColumnSupport = [];

    public const STATUS_PENDENTE = 'PENDENTE';
    public const STATUS_AGUARDANDO_CONFIRMACAO = 'AGUARDANDO_CONFIRMACAO';
    public const STATUS_LIBERACAO = 'LIBERACAO';
    public const STATUS_CONFIRMADO = 'CONFIRMADO';
    public const STATUS_NAO_ENVIADO = 'NAO_ENVIADO';
    public const STATUS_NAO_RECEBIDO = 'NAO_RECEBIDO';
    public const STATUS_RECEBIDO = 'RECEBIDO';
    public const STATUS_CANCELADO = 'CANCELADO';
    public const STATUS_ARQUIVADO = 'ARQUIVADO';

    public const TRACKING_TYPE_RASTREIO = 'RASTREIO';
    public const TRACKING_TYPE_NOTA_FISCAL = 'NOTA_FISCAL';
    public const TRACKING_TYPE_RASTREIO_E_NOTA = 'RASTREIO_E_NOTA';
    
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
        'local_projeto_id',
        'uf',
        'setor',
        'local_destino',
        'fluxo_responsavel',
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
        'logistics_volume_count',
        'logistics_asset_number',
        'logistics_notes',
        'logistics_registered_by_id',
        'logistics_registered_at',
        'quote_transporter',
        'quote_amount',
        'quote_deadline',
        'quote_notes',
        'quote_options_payload',
        'quote_selected_index',
        'quote_tracking_type',
        'quote_registered_by_id',
        'quote_registered_at',
        'release_authorized_by_id',
        'release_authorized_at',
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
        'logistics_volume_count' => 'integer',
        'quote_amount' => 'decimal:2',
        'quote_options_payload' => 'array',
        'quote_selected_index' => 'integer',
        'logistics_registered_at' => 'datetime',
        'quote_registered_at' => 'datetime',
        'release_authorized_at' => 'datetime',
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

    public static function trackingTypeOptions(): array
    {
        return [
            self::TRACKING_TYPE_RASTREIO => 'Código de rastreio',
            self::TRACKING_TYPE_NOTA_FISCAL => 'Número da nota fiscal',
            self::TRACKING_TYPE_RASTREIO_E_NOTA => 'Rastreio e nota fiscal',
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

    public function destinoLocalProjeto(): BelongsTo
    {
        return $this->belongsTo(LocalProjeto::class, 'local_projeto_id');
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
            || $this->logistics_volume_count !== null
            || trim((string) ($this->logistics_notes ?? '')) !== ''
            || $this->logistics_registered_at !== null;
    }

    public function hasQuoteData(): bool
    {
        if ($this->hasQuoteOptions()) {
            return true;
        }

        return trim((string) ($this->quote_transporter ?? '')) !== ''
            || $this->quote_amount !== null
            || trim((string) ($this->quote_deadline ?? '')) !== ''
            || trim((string) ($this->quote_notes ?? '')) !== ''
            || $this->quote_registered_at !== null;
    }

    public function quoteOptions(): array
    {
        $items = $this->quote_options_payload;
        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $transporter = trim((string) ($item['transporter'] ?? ''));
            $deadline = trim((string) ($item['deadline'] ?? ''));
            $trackingType = trim((string) ($item['tracking_type'] ?? ''));
            $notes = trim((string) ($item['notes'] ?? ''));
            $amount = $item['amount'] ?? null;

            if ($transporter === '' && $deadline === '' && $trackingType === '' && $notes === '' && $amount === null) {
                return null;
            }

            return [
                'transporter' => $transporter,
                'amount' => $amount !== null && $amount !== '' ? (float) $amount : null,
                'deadline' => $deadline,
                'tracking_type' => $trackingType,
                'notes' => $notes !== '' ? $notes : null,
            ];
        }, $items)));
    }

    public function hasQuoteOptions(): bool
    {
        return count($this->quoteOptions()) > 0;
    }

    public function selectedQuote(): ?array
    {
        $options = $this->quoteOptions();
        $index = $this->quote_selected_index;

        if ($index === null || !array_key_exists($index, $options)) {
            return null;
        }

        return $options[$index];
    }

    public function requiresTrackingCode(): bool
    {
        return in_array($this->quote_tracking_type, [
            self::TRACKING_TYPE_RASTREIO,
            self::TRACKING_TYPE_RASTREIO_E_NOTA,
        ], true);
    }

    public function requiresInvoiceNumber(): bool
    {
        return in_array($this->quote_tracking_type, [
            self::TRACKING_TYPE_NOTA_FISCAL,
            self::TRACKING_TYPE_RASTREIO_E_NOTA,
        ], true);
    }

    public function hasShipmentData(): bool
    {
        return trim((string) ($this->tracking_code ?? '')) !== ''
            || trim((string) ($this->invoice_number ?? '')) !== ''
            || $this->shipped_at !== null;
    }

    public function isAwaitingRequesterDecision(): bool
    {
        return $this->isAwaitingBrunoRelease();
    }

    public function isAwaitingTheoAuthorization(): bool
    {
        if (!self::supportsTheoReleaseAuthorization()) {
            return false;
        }

        return $this->status === self::STATUS_LIBERACAO
            && $this->hasQuoteOptions()
            && $this->release_authorized_at === null
            && !$this->hasShipmentData();
    }

    public function isAwaitingBrunoRelease(): bool
    {
        $isTheoFlowEnabled = self::supportsTheoReleaseAuthorization();

        return $this->status === self::STATUS_LIBERACAO
            && $this->hasQuoteOptions()
            && ($isTheoFlowEnabled ? $this->release_authorized_at !== null : true)
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

    public function getFluxoResponsavelNormalizadoAttribute(): string
    {
        $fluxo = mb_strtoupper(trim((string) ($this->attributes['fluxo_responsavel'] ?? '')), 'UTF-8');
        if ($fluxo !== '' && array_key_exists($fluxo, LocalProjeto::fluxoResponsavelOptions())) {
            return $fluxo;
        }

        if ($this->relationLoaded('destinoLocalProjeto') && $this->destinoLocalProjeto) {
            return $this->destinoLocalProjeto->fluxo_responsavel_normalizado;
        }

        return LocalProjeto::FLUXO_RESPONSAVEL_PADRAO;
    }

    public function getFluxoResponsavelLabelAttribute(): string
    {
        return LocalProjeto::fluxoResponsavelOptions()[$this->fluxo_responsavel_normalizado]
            ?? LocalProjeto::fluxoResponsavelOptions()[LocalProjeto::FLUXO_RESPONSAVEL_PADRAO];
    }

    public function isFluxoTi(): bool
    {
        return $this->fluxo_responsavel_normalizado === LocalProjeto::FLUXO_RESPONSAVEL_TI;
    }

    public static function supportsTheoReleaseAuthorization(): bool
    {
        return self::hasDatabaseColumn('release_authorized_at');
    }

    public static function forgetCachedColumnSupport(): void
    {
        self::$cachedColumnSupport = [];
    }

    private static function hasDatabaseColumn(string $column): bool
    {
        $instance = new static();
        $connection = $instance->getConnectionName() ?: config('database.default');
        $key = $connection . '|' . $instance->getTable() . '|' . $column;

        if (!array_key_exists($key, self::$cachedColumnSupport)) {
            // A KingHost usa um MySQL antigo que quebra em Schema::hasColumn().
            // Esta checagem direta evita o uso de metadados incompatíveis.
            $table = $instance->getTable();
            $database = DB::connection($connection)->getDatabaseName();

            $row = DB::connection($connection)->table('information_schema.COLUMNS')
                ->selectRaw('1')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->limit(1)
                ->first();

            self::$cachedColumnSupport[$key] = $row !== null;
        }

        return self::$cachedColumnSupport[$key];
    }
}
