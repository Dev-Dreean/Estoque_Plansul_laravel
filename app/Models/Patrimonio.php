<?php
// app/Models/Patrimonio.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Patrimonio extends Model
{
    use HasFactory;

    protected $table = 'patr';
    protected $primaryKey = 'NUSEQPATR';
    public $timestamps = false;

    protected $fillable = [
        'NUPATRIMONIO',
        'SITUACAO',
        'TIPO',
        'MARCA',
        'MODELO',
        'CARACTERISTICAS',
        'DIMENSAO',
        'COR',
        'NUSERIE',
        'CDLOCAL',
        'DTAQUISICAO',
        'DTBAIXA',
        'DTGARANTIA',
        'DEHISTORICO',
        'DTLAUDO',
        'DEPATRIMONIO',
        'CDMATRFUNCIONARIO',
        'CDLOCALINTERNO',
        'CDPROJETO',
        'USUARIO',
        'DTOPERACAO',
        'FLCONFERIDO',
        'NUMOF',
        'CODOBJETO',
        'NMPLANTA',
    ];

    protected $casts = [
        'DTAQUISICAO' => 'date',
        'DTOPERACAO'  => 'date',
        'DTBAIXA'     => 'date',
        'DTGARANTIA'  => 'date',
        'DTLAUDO'     => 'date',
    ];

    // ALTERAÇÃO AQUI: A relação agora é com Funcionario
    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'CDMATRFUNCIONARIO', 'CDMATRFUNCIONARIO');
    }

    public function local()
    {
        return $this->belongsTo(Tabfant::class, 'CDLOCAL', 'id');
    }

    /**
     * Usuário (sistema) que realizou o cadastro.
     * O campo 'USUARIO' na tabela armazena o login (NMLOGIN). Relacionamos via NMLOGIN -> NMLOGIN.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'USUARIO', 'NMLOGIN');
    }

    // Nome legível de quem cadastrou (usa relação creator, ou fallback resolvido via cache simples)
    public function getCadastradoPorNomeAttribute(): string
    {
        if ($this->relationLoaded('creator') && $this->creator && $this->creator->NOMEUSER) {
            return $this->creator->NOMEUSER;
        }
        if (!empty($this->USUARIO)) {
            $cacheKey = 'login_nome_' . $this->USUARIO;
            return Cache::remember($cacheKey, 300, function () {
                return optional(User::where('NMLOGIN', $this->USUARIO)->first())->NOMEUSER ?? $this->USUARIO;
            });
        }
        return 'SISTEMA';
    }

    // Datas formatadas PT-BR (evita Carbon::parse no Blade)
    public function getDtaquisicaoPtBrAttribute(): ?string
    {
        return $this->DTAQUISICAO ? $this->DTAQUISICAO->format('d/m/Y') : null;
    }

    public function getDtoperacaoPtBrAttribute(): ?string
    {
        return $this->DTOPERACAO ? $this->DTOPERACAO->format('d/m/Y') : null;
    }
}
