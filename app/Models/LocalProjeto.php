<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class LocalProjeto extends Model
{
    protected $table = 'locais_projeto';
    protected $guarded = []; // Permite inserção em massa para todos os campos

    /**
     * Mutator para converter nome do local para UPPERCASE ao salvar
     */
    public function setDelocalAttribute($value)
    {
        $this->attributes['delocal'] = strtoupper($value);
    }

    /**
     * Um Local pertence a um Projeto (Tabfant).
     */
    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Tabfant::class, 'tabfant_id', 'id');
    }

    /**
     * Accessor para compatibilidade: retorna o nome do local.
     * Permite usar $localProjeto->LOCAL
     */
    public function getLOCALAttribute()
    {
        return $this->delocal;
    }

    /**
     * Accessor para compatibilidade: retorna o código do projeto.
     * Permite usar $localProjeto->CDPROJETO
     */
    public function getCDPROJETOAttribute()
    {
        return $this->projeto ? $this->projeto->CDPROJETO : null;
    }

    /**
     * Accessor para compatibilidade: retorna o nome do projeto.
     * Permite usar $localProjeto->NOMEPROJETO
     */
    public function getNOMEPROJETOAttribute()
    {
        return $this->projeto ? $this->projeto->NOMEPROJETO : null;
    }

    /**
     * Accessor para retornar a UF (Estado) do local
     * 1️⃣  Usa UF armazenada no local
     * 2️⃣  Usa UF do projeto vinculado
     * 
     * Permite usar: $local->uf_estado ou $local->uf
     */
    public function getUfEstadoAttribute(): ?string
    {
        // 1. Se tem UF armazenada no local, usar
        if (!empty($this->UF)) {
            return $this->UF;
        }

        // 2. Tentar obter do projeto vinculado
        if ($this->projeto && !empty($this->projeto->UF)) {
            return $this->projeto->UF;
        }

        return null;
    }

    /**
     * Alias para getUfEstadoAttribute
     */
    public function getUfAttribute(): ?string
    {
        return $this->uf_estado;
    }
}

