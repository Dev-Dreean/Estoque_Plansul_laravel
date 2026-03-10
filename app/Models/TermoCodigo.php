<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $tabelaExiste = DB::select("SHOW TABLES LIKE 'termo_codigos'");
            if (empty($tabelaExiste)) {
                return false;
            }

            $colunaExiste = DB::select("SHOW COLUMNS FROM termo_codigos LIKE 'titulo'");

            return !empty($colunaExiste);
        } catch (\Throwable $e) {
            Log::warning('Nao foi possivel verificar a coluna titulo em termo_codigos.', [
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

