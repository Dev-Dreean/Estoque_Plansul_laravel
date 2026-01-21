<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjetoFilial extends Model
{
    protected $table = 'projeto_filiais';
    protected $fillable = ['tabfant_id', 'NOMEFILIAL'];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Tabfant::class, 'tabfant_id');
    }
}

