<?php

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

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'NUSEQPATR';
    }

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
        'NMPLANTA'
    ];

    protected $casts = [
        'DTAQUISICAO' => 'date:Y-m-d',
        'DTOPERACAO'  => 'date:Y-m-d',
        'DTBAIXA'     => 'date:Y-m-d',
        'DTGARANTIA'  => 'date:Y-m-d',
        'DTLAUDO'     => 'date:Y-m-d',
    ];

    protected $appends = ['projeto_correto'];

    /**
     * Mutators para converter campos em UPPERCASE ao salvar
     */
    public function setSITUACAOAttribute($value)
    {
        $this->attributes['SITUACAO'] = strtoupper($value);
    }

    public function setMARCAAttribute($value)
    {
        $this->attributes['MARCA'] = strtoupper($value);
    }

    public function setMODELOAttribute($value)
    {
        $this->attributes['MODELO'] = strtoupper($value);
    }

    public function setCARACTERISTICASAttribute($value)
    {
        $this->attributes['CARACTERISTICAS'] = strtoupper($value);
    }

    public function setDIMENSAOAttribute($value)
    {
        $this->attributes['DIMENSAO'] = strtoupper($value);
    }

    public function setCORAttribute($value)
    {
        $this->attributes['COR'] = strtoupper($value);
    }

    public function setDEPATRIMONIOAttribute($value)
    {
        $this->attributes['DEPATRIMONIO'] = strtoupper($value);
    }

    public function setDEHISTORICOAttribute($value)
    {
        $this->attributes['DEHISTORICO'] = strtoupper($value);
    }

    public function setNUSERIEAttribute($value)
    {
        $this->attributes['NUSERIE'] = strtoupper($value);
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class, 'CDMATRFUNCIONARIO', 'CDMATRFUNCIONARIO');
    }

    /**
     * Relação com o Local do Projeto.
     * CDLOCAL armazena o cdlocal da tabela locais_projeto.
     */
    public function localProjeto(): BelongsTo
    {
        return $this->belongsTo(LocalProjeto::class, 'CDLOCAL', 'cdlocal');
    }

    /**
     * Relação com o Local (via LocalProjeto -> Tabfant).
     * Esta é a relação corrigida que busca o projeto através do local.
     * CDLOCAL do patr -> cdlocal da locais_projeto
     */
    public function local()
    {
        return $this->belongsTo(LocalProjeto::class, 'CDLOCAL', 'cdlocal');
    }

    /**
     * Relação com o Projeto diretamente.
     * CDPROJETO armazena o código do projeto (não o ID).
     */
    public function projeto()
    {
        return $this->belongsTo(Tabfant::class, 'CDPROJETO', 'CDPROJETO');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'USUARIO', 'NMLOGIN');
    }

    public function getCadastradoPorNomeAttribute(): string
    {
        $user = null;
        $login = $this->USUARIO;

        if ($this->relationLoaded('creator') && $this->creator) {
            $user = $this->creator;
        } elseif (!empty($login)) {
            $cacheKey = 'login_user_' . $login;
            $user = Cache::remember($cacheKey, 300, fn () => User::where('NMLOGIN', $login)->first());
        }

        if (!$user && empty($login)) {
            return 'SISTEMA';
        }

        $fullName = $user->NOMEUSER ?? null;
        $uf = $user->UF ?? null;
        $uf = $uf ? mb_strtoupper(trim((string) $uf)) : null;

        // fallback: extrai UF do login (ex.: beatriz.sc => sc)
        if (!$uf && $login && preg_match('/\\.([a-z]{2})$/i', $login, $m)) {
            $uf = mb_strtoupper($m[1]);
        }

        $firstName = null;
        if ($fullName && is_string($fullName)) {
            $parts = array_values(array_filter(preg_split('/\s+/', trim($fullName)) ?: []));
            $firstName = $parts[0] ?? null;
        }

        if (!$firstName && $login) {
            if (preg_match('/^([^.]+)/', (string) $login, $m)) {
                $firstName = $m[1];
            } else {
                $firstName = $login;
            }
        }

        $baseRaw = $firstName ?: ($login ?? 'SISTEMA');
        $baseRaw = mb_strtoupper($baseRaw);

        // Se tiver UF, sempre mostra "NOME.UF" usando apenas a parte antes do primeiro ponto para evitar ".SC.SC"
        if ($uf) {
            $parts = explode('.', $baseRaw);
            $base = $parts[0] ?: $baseRaw;
            return $base . '.' . $uf;
        }

        // Sem UF: mostrar apenas a parte antes do ponto, se existir
        if (str_contains($baseRaw, '.')) {
            $baseRaw = explode('.', $baseRaw)[0] ?: $baseRaw;
        }

        return $baseRaw;
    }

    public function getDtaquisicaoPtBrAttribute(): ?string
    {
        if (!$this->DTAQUISICAO) {
            return null;
        }
        
        try {
            $date = is_string($this->attributes['DTAQUISICAO'] ?? null) 
                ? \Carbon\Carbon::parse($this->attributes['DTAQUISICAO'])
                : $this->DTAQUISICAO;
            
            // Validar se o ano é razoável (entre 1990 e próximos 5 anos)
            $year = $date->year;
            if ($year < 1990 || $year > 2030) {
                return null; // Retorna null para datas inválidas
            }
            
            return $date->format('d/m/Y');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getDtoperacaoPtBrAttribute(): ?string
    {
        if (!$this->DTOPERACAO) {
            return null;
        }
        
        try {
            $date = is_string($this->attributes['DTOPERACAO'] ?? null) 
                ? \Carbon\Carbon::parse($this->attributes['DTOPERACAO'])
                : $this->DTOPERACAO;
            
            // Validar se o ano é razoável (entre 1990 e próximos 5 anos)
            $year = $date->year;
            if ($year < 1990 || $year > 2030) {
                return null; // Retorna null para datas inválidas
            }
            
            return $date->format('d/m/Y');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Retorna o CDPROJETO correto - preferindo o do local se disponível
     * Isso garante consistência entre o grid e o formulário de edição
     */
    public function getProjetoCorretoAttribute(): ?string
    {
        // Se o local está carregado e tem projeto, usar dele
        if ($this->relationLoaded('local') && $this->local && $this->local->relationLoaded('projeto') && $this->local->projeto) {
            return $this->local->projeto->CDPROJETO;
        }

        // Senão, retornar o CDPROJETO armazenado diretamente
        return $this->CDPROJETO;
    }
}
