<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroRemovido extends Model
{
    protected $table = 'registros_removidos';

    protected $fillable = [
        'entity',
        'model_type',
        'model_id',
        'model_label',
        'deleted_by',
        'deleted_by_matricula',
        'deleted_at',
        'request_path',
        'ip_address',
        'user_agent',
        'payload',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'payload' => 'array',
    ];
}


