<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalProjeto extends Model
{
    protected $table = 'locais_projeto';
    protected $guarded = []; // Permite inserção em massa para todos os campos

    /**
     * Um Local pertence a um Projeto (Tabfant).
     */
    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Tabfant::class, 'tabfant_id', 'id');
    }
}
