<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPatr extends Model
{
    protected $table = 'TIPOPATR';
    protected $primaryKey = 'NUSEQTIPOPATR';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = ['NUSEQTIPOPATR', 'DETIPOPATR'];

    public function objetos()
    {
        return $this->hasMany(ObjetoPatr::class, 'NUSEQTIPOPATR', 'NUSEQTIPOPATR');
    }
}
