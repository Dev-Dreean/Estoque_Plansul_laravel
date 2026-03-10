<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TermoCodigo extends Model
{
    use HasFactory;

    protected $table = 'termo_codigos';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'titulo',
        'created_by',
    ];

    public static function hasTituloColumn(): bool
    {
        try {
            if (!Schema::hasTable('termo_codigos')) {
                return false;
            }

            return Schema::hasColumn('termo_codigos', 'titulo');
        } catch (\Throwable $e) {
            Log::warning('Nao foi possivel verificar a coluna titulo em termo_codigos.', [
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

