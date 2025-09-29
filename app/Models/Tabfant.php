<?php

// Caminho: app/Models/Tabfant.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tabfant extends Model
{
    use HasFactory;

    /**
     * O nome da tabela associada com o model.
     * @var string
     */
    protected $table = 'tabfant';

    /**
     * A chave primária da tabela.
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indica se os IDs são auto-incremento.
     * Como estamos definindo o ID manualmente a partir do arquivo TXT,
     * isso deve ser false. ESTA É A CORREÇÃO PRINCIPAL.
     * @var bool
     */
    public $incrementing = false;

    /**
     * O tipo de dados da chave primária.
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Os atributos que podem ser atribuídos em massa.
     * @var array
     */
    protected $fillable = [
        'id',
        'NOMEPROJETO',
        'CDPROJETO',
        'LOCAL',
    ];
}
