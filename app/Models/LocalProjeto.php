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
}
