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

    /** @var bool|null Cache do resultado de hasTituloColumn para evitar SHOW queries repetidas */
    private static ?bool $cachedHasTituloColumn = null;

    public static function hasTituloColumn(): bool
    {
        if (self::$cachedHasTituloColumn !== null) {
            return self::$cachedHasTituloColumn;
        }

        try {
            $tabelaExiste = DB::select("SHOW TABLES LIKE 'termo_codigos'");
            if (empty($tabelaExiste)) {
                return self::$cachedHasTituloColumn = false;
            }

            $colunaExiste = DB::select("SHOW COLUMNS FROM termo_codigos LIKE 'titulo'");

            return self::$cachedHasTituloColumn = !empty($colunaExiste);
        } catch (\Throwable $e) {
            Log::warning('Nao foi possivel verificar a coluna titulo em termo_codigos.', [
                'erro' => $e->getMessage(),
            ]);

            return self::$cachedHasTituloColumn = false;
        }
    }
}

