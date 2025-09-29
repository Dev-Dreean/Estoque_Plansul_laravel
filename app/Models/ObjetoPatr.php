<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObjetoPatr extends Model
{
    protected $table = 'objetopatr';
    protected $primaryKey = 'NUSEQOBJETO';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = ['NUSEQOBJETO', 'NUSEQTIPOPATR', 'DEOBJETO'];

    public function tipo()
    {
        return $this->belongsTo(TipoPatr::class, 'NUSEQTIPOPATR', 'NUSEQTIPOPATR');
    }
}
