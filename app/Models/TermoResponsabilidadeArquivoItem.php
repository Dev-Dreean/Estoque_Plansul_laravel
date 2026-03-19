<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermoResponsabilidadeArquivoItem extends Model
{
    use HasFactory;

    protected $table = 'termos_responsabilidade_arquivo_itens';
    public $timestamps = false;

    protected $fillable = [
        'termo_responsabilidade_arquivo_id',
        'nuseqpatr',
    ];

    public function arquivo(): BelongsTo
    {
        return $this->belongsTo(TermoResponsabilidadeArquivo::class, 'termo_responsabilidade_arquivo_id');
    }
}
