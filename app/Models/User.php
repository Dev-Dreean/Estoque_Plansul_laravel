<?php
declare(strict_types=1);

// app/Models/User.php

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
 * @method bool temAcessoTela(int|string $nuseqtela)
 * @method bool telaVisivel(int|string $nuseqtela)
 * @method \Illuminate\Database\Eloquent\Relations\HasMany acessos()
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
     * @var string A chave prim├íria da tabela.
     */
    protected $primaryKey = 'NUSEQUSUARIO';

    /**
     * @var bool Indica se o model deve registrar 'created_at' e 'updated_at'.
     * Sua tabela n├úo possui essas colunas, ent├úo desativamos.
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
     * Pega o nome da coluna de senha para autentica├º├úo.
     * O padr├úo ├® 'password', o nosso ├® 'SENHA'.
     *
     * @return string
     */
    public function getAuthPasswordName(): string
    {
        return 'SENHA';
    }

    /**
     * Pega a senha para autentica├º├úo.
     * Necess├írio para que o Auth::attempt() funcione com a coluna 'SENHA'.
     *
     * @return string
     */
    public function getAuthPassword(): string
    {
        return $this->SENHA;
    }

    /**
     * Define um "mutator" para sempre criptografar a senha ao salv├í-la.
     * Ex: $user->SENHA = '1234'; // Salvar├í o hash, n├úo '1234'
     */
    public function setSenhaAttribute(string $value): void
    {
        $this->attributes['SENHA'] = Hash::make($value);
    }

    /**
     * Relacionamento: um usu├írio tem muitos acessos a telas
     */
    public function acessos()
    {
        return $this->hasMany(AcessoUsuario::class, 'CDMATRFUNCIONARIO', 'CDMATRFUNCIONARIO');
    }

    /**
     * Verifica se o usu├írio ├® Super Administrador
     */
    public function isSuperAdmin(): bool
    {
        return $this->PERFIL === self::PERFIL_SUPER;
    }

    /**
     * ­ƒö▒ GOD MODE: Super Admin tem poder absoluto
     * Anula TODAS as verifica├º├Áes de permiss├úo
     * 
     * @return bool
     */
    public function isGod(): bool
    {
        return $this->PERFIL === self::PERFIL_SUPER;
    }

    /**
     * Verifica se o usu├írio ├® Administrador (ou superior)
     */
    public function isAdmin(): bool
    {
        return in_array($this->PERFIL, [self::PERFIL_ADMIN, self::PERFIL_SUPER]);
    }

    /**
     * Verifica se o usu├írio ├® apenas usu├írio comum
     */
    public function isUsuario(): bool
    {
        return $this->PERFIL === self::PERFIL_USUARIO;
    }

    /**
     * Verifica se o usu├írio pode excluir registros
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
     * Admin precisa ter permissão explícita
     * Usuários comuns precisam ter acesso configurado + tela visível
     *
     * @param int|string $nuseqtela
     * @return bool
     */
    public function temAcessoTela(int|string $nuseqtela): bool
    {
        $nuseqtela = (string) $nuseqtela;

        // Super Admin tem acesso total
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Verifica se há permissão explícita na tabela acessousuario
        $temPermissao = $this->acessos()
            ->where('NUSEQTELA', $nuseqtela)
            ->whereRaw("TRIM(UPPER(INACESSO)) = 'S'")
            ->exists();

        // Se não tem permissão, retorna false
        if (!$temPermissao) {
            return false;
        }

        // Se tem permissão, verifica se a tela está visível para o perfil
        return $this->telaVisivel($nuseqtela);
    }

    public function telaVisivel(int|string $nuseqtela): bool
    {
        $nuseqtela = (string) $nuseqtela;

        if ($this->isSuperAdmin()) {
            return true;
        }

        $tela = DB::table('acessotela')
            ->where('NUSEQTELA', $nuseqtela)
            ->first();

        if (!$tela) {
            return false;
        }

        if (strtoupper(trim($tela->FLACESSO ?? 'N')) !== 'S') {
            return false;
        }

        $nivelVisibilidade = $tela->NIVEL_VISIBILIDADE ?? 'TODOS';

        if ($this->PERFIL === self::PERFIL_ADMIN) {
            return in_array($nivelVisibilidade, ['TODOS', 'ADM']);
        }

        return $nivelVisibilidade === 'TODOS';
    }

    public function telasComAcesso(): array
    {
        if ($this->isSuperAdmin()) {
            return DB::table('acessotela')
                ->whereRaw("TRIM(UPPER(FLACESSO)) = 'S'")
                ->pluck('NUSEQTELA')
                ->toArray();
        }

        return $this->acessos()
            ->join('acessotela', 'acessousuario.NUSEQTELA', '=', 'acessotela.NUSEQTELA')
            ->whereRaw("TRIM(UPPER(acessousuario.INACESSO)) = 'S'")
            ->whereRaw("TRIM(UPPER(acessotela.FLACESSO)) = 'S'")
            ->pluck('acessousuario.NUSEQTELA')
            ->toArray();
    }
}
