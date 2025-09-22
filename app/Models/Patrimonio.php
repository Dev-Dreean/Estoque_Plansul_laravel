<?php
// app/Models/Patrimonio.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
