<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalProjeto extends Model
{
    public const TIPO_LOCAL_PADRAO = 'PADRAO';
    public const TIPO_LOCAL_ESTOQUE_TI = 'ESTOQUE_TI';
    public const TIPO_LOCAL_TI_EM_USO = 'TI_EM_USO';

    public const FLUXO_RESPONSAVEL_PADRAO = 'PADRAO';
    public const FLUXO_RESPONSAVEL_TI = 'TI';

    protected $table = 'locais_projeto';
    protected $guarded = [];

    public function setDelocalAttribute($value): void
    {
        $this->attributes['delocal'] = strtoupper((string) $value);
    }

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Tabfant::class, 'tabfant_id', 'id');
    }

    public function getLOCALAttribute()
    {
        return $this->delocal;
    }

    public function getCDPROJETOAttribute()
    {
        return $this->projeto ? $this->projeto->CDPROJETO : null;
    }

    public function getNOMEPROJETOAttribute()
    {
        return $this->projeto ? $this->projeto->NOMEPROJETO : null;
    }

    public function getUfEstadoAttribute(): ?string
    {
        if (!empty($this->UF)) {
            return $this->UF;
        }

        if ($this->projeto && !empty($this->projeto->UF)) {
            return $this->projeto->UF;
        }

        return null;
    }

    public function getUfAttribute(): ?string
    {
        return $this->uf_estado;
    }

    public static function tipoLocalOptions(): array
    {
        return [
            self::TIPO_LOCAL_PADRAO => 'Padrão',
            self::TIPO_LOCAL_ESTOQUE_TI => 'Estoque TI',
            self::TIPO_LOCAL_TI_EM_USO => 'TI em uso',
        ];
    }

    public static function fluxoResponsavelOptions(): array
    {
        return [
            self::FLUXO_RESPONSAVEL_PADRAO => 'Padrão',
            self::FLUXO_RESPONSAVEL_TI => 'TI',
        ];
    }

    public function getTipoLocalNormalizadoAttribute(): string
    {
        $tipo = mb_strtoupper(trim((string) ($this->attributes['tipo_local'] ?? self::TIPO_LOCAL_PADRAO)), 'UTF-8');

        return array_key_exists($tipo, self::tipoLocalOptions())
            ? $tipo
            : self::TIPO_LOCAL_PADRAO;
    }

    public function getFluxoResponsavelNormalizadoAttribute(): string
    {
        $fluxo = mb_strtoupper(trim((string) ($this->attributes['fluxo_responsavel'] ?? self::FLUXO_RESPONSAVEL_PADRAO)), 'UTF-8');

        return array_key_exists($fluxo, self::fluxoResponsavelOptions())
            ? $fluxo
            : self::FLUXO_RESPONSAVEL_PADRAO;
    }

    public function getTipoLocalLabelAttribute(): string
    {
        return self::tipoLocalOptions()[$this->tipo_local_normalizado]
            ?? self::tipoLocalOptions()[self::TIPO_LOCAL_PADRAO];
    }

    public function getFluxoResponsavelLabelAttribute(): string
    {
        return self::fluxoResponsavelOptions()[$this->fluxo_responsavel_normalizado]
            ?? self::fluxoResponsavelOptions()[self::FLUXO_RESPONSAVEL_PADRAO];
    }

    public function isFluxoTi(): bool
    {
        return $this->fluxo_responsavel_normalizado === self::FLUXO_RESPONSAVEL_TI;
    }

    public function isEstoqueTi(): bool
    {
        return $this->tipo_local_normalizado === self::TIPO_LOCAL_ESTOQUE_TI;
    }

    public function isTiEmUso(): bool
    {
        return $this->tipo_local_normalizado === self::TIPO_LOCAL_TI_EM_USO;
    }

    public static function resolveForProjeto(?int $projetoId, ?int $localId = null, ?string $localNome = null): ?self
    {
        if (!$projetoId) {
            return null;
        }

        if ($localId) {
            $local = static::query()
                ->where('id', $localId)
                ->where('tabfant_id', $projetoId)
                ->first();

            if ($local) {
                return $local;
            }
        }

        $localNome = trim((string) $localNome);
        if ($localNome === '') {
            return null;
        }

        $query = static::query()->where('tabfant_id', $projetoId);
        $nomeNormalizado = mb_strtoupper(preg_replace('/\s+/u', ' ', $localNome) ?: $localNome, 'UTF-8');

        $local = (clone $query)
            ->whereRaw('UPPER(TRIM(delocal)) = ?', [$nomeNormalizado])
            ->first();

        if ($local) {
            return $local;
        }

        if (preg_match('/^\s*(\d+)\s*(?:-|$)/', $localNome, $matches)) {
            return (clone $query)
                ->where('cdlocal', (int) $matches[1])
                ->orderBy('id')
                ->first();
        }

        return null;
    }
}
