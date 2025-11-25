<?php

// app/Models/User.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\User
 * 
 * @property string $PERFIL
 * @property string|null $NMLOGIN
 * @property string|null $NOMEUSER
 * @property string|null $CDMATRFUNCIONARIO
 * 
 * @method bool isGod()
 * @method bool isSuperAdmin()
 * @method bool isAdmin()
 * @method bool podeExcluir()
 * @method bool temAcessoTela(int $nuseqtela)
 * @method bool telaVisivel(int $nuseqtela)
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Constantes de perfis
    public const PERFIL_USUARIO = 'USR';
    public const PERFIL_ADMIN = 'ADM';
    public const PERFIL_SUPER = 'SUP';

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
        'PERFIL',
        'SENHA',
        'LGATIVO',
        'CDMATRFUNCIONARIO',
        'UF',
        'must_change_password',
        'password_policy_version',
        'theme',
    ];

    protected $casts = [
        'must_change_password' => 'boolean',
        'password_policy_version' => 'integer',
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

    /**
     * Relacionamento: um usuário tem muitos acessos a telas
     */
    public function acessos()
    {
        return $this->hasMany(AcessoUsuario::class, 'CDMATRFUNCIONARIO', 'CDMATRFUNCIONARIO');
    }

    /**
     * Verifica se o usuário é Super Administrador
     */
    public function isSuperAdmin(): bool
    {
        return $this->PERFIL === self::PERFIL_SUPER;
    }

    /**
     * 🔱 GOD MODE: Super Admin tem poder absoluto
     * Anula TODAS as verificações de permissão
     * 
     * @return bool
     */
    public function isGod(): bool
    {
        return $this->PERFIL === self::PERFIL_SUPER;
    }

    /**
     * Verifica se o usuário é Administrador (ou superior)
     */
    public function isAdmin(): bool
    {
        return in_array($this->PERFIL, [self::PERFIL_ADMIN, self::PERFIL_SUPER]);
    }

    /**
     * Verifica se o usuário é apenas usuário comum
     */
    public function isUsuario(): bool
    {
        return $this->PERFIL === self::PERFIL_USUARIO;
    }

    /**
     * Verifica se o usuário pode excluir registros
     * Apenas Super Admin pode excluir
     */
    public function podeExcluir(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Verifica se o usuário tem acesso a uma tela específica
     * considerando tanto permissões quanto visibilidade
     * 
     * Hierarquia: Super Admin tem acesso a TODAS as telas
     * Admin tem acesso a telas visíveis para ele
     * Usuários comuns precisam ter acesso configurado + tela visível
     *
     * @param int $nuseqtela
     * @return bool
     */
    public function temAcessoTela(int $nuseqtela): bool
    {
        // Primeiro verifica se a tela está visível para este perfil
        if (!$this->telaVisivel($nuseqtela)) {
            return false;
        }

        // Usuário comum nunca acessa a tela de usuários (1003)
        if ($nuseqtela === 1003 && $this->isUsuario()) {
            return false;
        }

        // Usuário comum nunca acessa a tela de cadastro de locais (1002)
        if ($nuseqtela === 1002 && $this->isUsuario()) {
            return false;
        }

        // Super Admin tem acesso TOTAL
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Admin tem acesso a todas as telas visíveis para ele
        if ($this->PERFIL === self::PERFIL_ADMIN) {
            return true;
        }

        // Usuários comuns: verifica se existe um registro ativo para esta tela
        return $this->acessos()
            ->where('NUSEQTELA', $nuseqtela)
            ->where('INACESSO', 'S')
            ->exists();
    }

    /**
     * Verifica se uma tela está visível para o perfil do usuário
     * baseado no campo NIVEL_VISIBILIDADE da tabela acessotela
     * 
     * @param int $nuseqtela
     * @return bool
     */
    public function telaVisivel(int $nuseqtela): bool
    {
        $tela = DB::table('acessotela')
            ->where('NUSEQTELA', $nuseqtela)
            ->first();

        if (!$tela) {
            return false;
        }

        $nivelVisibilidade = $tela->NIVEL_VISIBILIDADE ?? 'TODOS';

        // Super Admin vê TODAS as telas sempre
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Admin vê telas 'TODOS' e 'ADM', mas não 'SUP'
        if ($this->PERFIL === self::PERFIL_ADMIN) {
            return in_array($nivelVisibilidade, ['TODOS', 'ADM']);
        }

        // Usuário comum vê apenas telas 'TODOS'
        return $nivelVisibilidade === 'TODOS';
    }

    /**
     * Retorna lista de códigos de telas que o usuário tem acesso
     * considerando tanto permissões quanto visibilidade
     *
     * @return array
     */
    public function telasComAcesso(): array
    {
        // Super Admin tem acesso a TODAS as telas
        if ($this->isSuperAdmin()) {
            return DB::table('acessotela')
                ->where('FLACESSO', 'S')
                ->pluck('NUSEQTELA')
                ->toArray();
        }

        // Admin tem acesso a todas VISÍVEIS para ele
        if ($this->PERFIL === self::PERFIL_ADMIN) {
            return DB::table('acessotela')
                ->where('FLACESSO', 'S')
                ->whereIn('NIVEL_VISIBILIDADE', ['TODOS', 'ADM'])
                ->pluck('NUSEQTELA')
                ->toArray();
        }

        // Usuário comum: apenas telas com permissão E visíveis
        return $this->acessos()
            ->join('acessotela', 'acessousuario.NUSEQTELA', '=', 'acessotela.NUSEQTELA')
            ->where('acessousuario.INACESSO', 'S')
            ->where('acessotela.NIVEL_VISIBILIDADE', 'TODOS')
            ->pluck('acessousuario.NUSEQTELA')
            ->toArray();
    }
}
