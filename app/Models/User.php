<?php

// app/Models/User.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * @var string A tabela do banco de dados associada a este Model.
     */
    protected $table = 'usuario';

    /**
     * @var string A chave primária da tabela.
     */
    protected $primaryKey = 'NUSEQUSUARIO';

    /**
     * @var bool Indica se o model deve registrar 'created_at' e 'updated_at'.
     * Sua tabela não possui essas colunas, então desativamos.
     */
    public $timestamps = false;

    /**
     * Atributos que podem ser preenchidos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'NOMEUSER',
        'NMLOGIN',
        'CDMATRFUNCIONARIO', // <-- Adicione esta linha
        'PERFIL',            // <-- Adicione esta linha
        'SENHA',
        'LGATIVO',
        // Adicione 'email' se você tiver essa coluna
    ];

    /**
     * Atributos que devem ser escondidos.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'SENHA',
    ];

    /**
     * Pega o nome da coluna de senha para autenticação.
     * O padrão é 'password', o nosso é 'SENHA'.
     *
     * @return string
     */
    public function getAuthPasswordName(): string
    {
        return 'SENHA';
    }

    /**
     * Pega a senha para autenticação.
     * Necessário para que o Auth::attempt() funcione com a coluna 'SENHA'.
     *
     * @return string
     */
    public function getAuthPassword(): string
    {
        return $this->SENHA;
    }

    /**
     * Define um "mutator" para sempre criptografar a senha ao salvá-la.
     * Ex: $user->SENHA = '1234'; // Salvará o hash, não '1234'
     */
    public function setSenhaAttribute(string $value): void
    {
        $this->attributes['SENHA'] = Hash::make($value);
    }
}