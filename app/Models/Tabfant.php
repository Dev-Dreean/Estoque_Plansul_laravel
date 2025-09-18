<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tabfant extends Model
{
    protected $table = 'tabfant';

    /**
     * Um Projeto (Tabfant) tem muitos Locais.
     */
    public function locais(): HasMany
    {
        return $this->hasMany(LocalProjeto::class, 'tabfant_id', 'id');
    }
}
