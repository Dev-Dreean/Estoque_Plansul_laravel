<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    public const PERFIL_USUARIO = 'USR';
    public const PERFIL_ADMIN = 'ADM';
    public const PERFIL_CONSULTOR = 'C';

    public const TELA_PATRIMONIO = '1000';
    public const TELA_RELATORIOS = '1006';
    public const TELA_SOLICITACOES_BENS = '1010';
    public const TELA_SOLICITACOES_VER_TODAS = '1011';
    public const TELA_SOLICITACOES_ATUALIZAR = '1012';
    public const TELA_SOLICITACOES_CRIAR = '1013';
    public const TELA_SOLICITACOES_APROVAR = '1014';
    public const TELA_SOLICITACOES_CANCELAR = '1015';
    public const TELA_SOLICITACOES_HISTORICO = '1016';
    public const TELA_SOLICITACOES_GERENCIAR_VISIBILIDADE = '1017';
    public const TELA_SOLICITACOES_VISUALIZACAO_RESTRITA = '1018';
    public const TELA_SOLICITACOES_TRIAGEM_INICIAL = '1019';
    public const TELA_SOLICITACOES_LIBERACAO_ENVIO = '1020';
    public const TELA_SOLICITACOES_AUTORIZACAO_LIBERACAO = '1021';

    public const MATRICULA_PLACEHOLDERS = ['0', '1'];
    public const MATRICULA_PLACEHOLDER_PREFIX = 'TMP-';

    protected $table = 'usuario';
    protected $primaryKey = 'NUSEQUSUARIO';

    public $timestamps = false;

    protected $fillable = [
        'NOMEUSER',
        'NMLOGIN',
        'PERFIL',
        'SENHA',
        'LGATIVO',
        'CDMATRFUNCIONARIO',
        'UF',
        'email',
        'must_change_password',
        'password_policy_version',
        'needs_identity_update',
        'theme',
        'patrimonio_columns_order',
    ];

    protected $casts = [
        'must_change_password' => 'boolean',
        'password_policy_version' => 'integer',
        'needs_identity_update' => 'boolean',
        'patrimonio_columns_order' => 'array',
    ];

    protected $hidden = [
        'SENHA',
    ];

    private ?array $resolvedTelaAccessMap = null;
    private ?array $visibleTelaMap = null;
    private ?array $activeTelaAssignmentsMap = null;

    public function getAuthPasswordName(): string
    {
        return 'SENHA';
    }

    public function getAuthPassword(): string
    {
        return $this->SENHA;
    }

    public function setSenhaAttribute(string $value): void
    {
        $this->attributes['SENHA'] = Hash::make($value);
    }

    public function acessos(): HasMany
    {
        return $this->hasMany(AcessoUsuario::class, 'CDMATRFUNCIONARIO', 'CDMATRFUNCIONARIO');
    }

    public function notificacoesSolicitacoes(): HasMany
    {
        return $this->hasMany(SolicitacaoBemNotificacaoUsuario::class, 'usuario_id', 'NUSEQUSUARIO');
    }

    public function isGod(): bool
    {
        return $this->isAdmin();
    }

    public function isAdmin(): bool
    {
        return $this->PERFIL === self::PERFIL_ADMIN;
    }

    public function isUsuario(): bool
    {
        return $this->PERFIL === self::PERFIL_USUARIO;
    }

    public function podeExcluir(): bool
    {
        return $this->isAdmin();
    }

    public function temAcessoTela(int|string $nuseqtela): bool
    {
        $nuseqtela = (string) $nuseqtela;

        if ($this->isAdmin()) {
            return true;
        }

        return (bool) ($this->resolvedTelaAccessMap()[$nuseqtela] ?? false);
    }

    public function temCodigoTela(int|string $nuseqtela): bool
    {
        $nuseqtela = (string) $nuseqtela;

        if ($this->isAdmin()) {
            return true;
        }

        return (bool) ($this->activeTelaAssignmentsMap()[$nuseqtela] ?? false);
    }

    public function telaVisivel(int|string $nuseqtela): bool
    {
        $nuseqtela = (string) $nuseqtela;

        if ($nuseqtela === self::TELA_PATRIMONIO) {
            return true;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return (bool) ($this->visibleTelaMap()[$nuseqtela] ?? false);
    }

    public function needsUf(): bool
    {
        return trim((string) ($this->UF ?? '')) === '';
    }

    public function needsEmail(): bool
    {
        return trim((string) ($this->email ?? '')) === '';
    }

    public function needsName(): bool
    {
        return trim((string) ($this->NOMEUSER ?? '')) === '';
    }

    public function needsMatricula(): bool
    {
        return self::isPlaceholderMatriculaValue((string) ($this->CDMATRFUNCIONARIO ?? ''));
    }

    public function isPlaceholderMatricula(): bool
    {
        return self::isPlaceholderMatriculaValue((string) ($this->CDMATRFUNCIONARIO ?? ''));
    }

    public function shouldRequestMatricula(): bool
    {
        return $this->needsMatricula() || (bool) ($this->needs_identity_update ?? false);
    }

    public function shouldRequestName(): bool
    {
        return $this->needsName() || (bool) ($this->needs_identity_update ?? false);
    }

    public function needsIdentityCompletion(): bool
    {
        return $this->needsName() || $this->needsMatricula();
    }

    public function needsIdentityUpdate(): bool
    {
        return $this->needsIdentityCompletion() || (bool) ($this->needs_identity_update ?? false);
    }

    public static function isPlaceholderMatriculaValue(?string $matricula): bool
    {
        $matricula = trim((string) ($matricula ?? ''));

        if ($matricula === '') {
            return true;
        }

        if (in_array($matricula, self::MATRICULA_PLACEHOLDERS, true)) {
            return true;
        }

        return str_starts_with($matricula, self::MATRICULA_PLACEHOLDER_PREFIX);
    }

    public static function generateTemporaryMatricula(): string
    {
        do {
            $candidate = self::MATRICULA_PLACEHOLDER_PREFIX . Str::upper(Str::random(4));
        } while (self::where('CDMATRFUNCIONARIO', $candidate)->exists());

        return $candidate;
    }

    public function telasComAcesso(): array
    {
        if ($this->isAdmin()) {
            return DB::table('acessotela')
                ->whereRaw("TRIM(UPPER(FLACESSO)) = 'S'")
                ->pluck('NUSEQTELA')
                ->map(fn ($tela) => (int) $tela)
                ->toArray();
        }

        return array_map('intval', array_keys($this->resolvedTelaAccessMap()));
    }

    /**
     * @return array<string, bool>
     */
    private function resolvedTelaAccessMap(): array
    {
        if ($this->resolvedTelaAccessMap !== null) {
            return $this->resolvedTelaAccessMap;
        }

        $map = $this->activeTelaAssignmentsMap();
        $map[self::TELA_PATRIMONIO] = true;
        $map[self::TELA_RELATORIOS] = true;

        if ($this->PERFIL === self::PERFIL_CONSULTOR || $this->hasSolicitacoesSubscreenAccess($map)) {
            $map[self::TELA_SOLICITACOES_BENS] = true;
        }

        $this->resolvedTelaAccessMap = $map;

        return $this->resolvedTelaAccessMap;
    }

    /**
     * @return array<string, bool>
     */
    private function activeTelaAssignmentsMap(): array
    {
        if ($this->activeTelaAssignmentsMap !== null) {
            return $this->activeTelaAssignmentsMap;
        }

        $this->activeTelaAssignmentsMap = $this->acessos()
            ->join('acessotela', 'acessousuario.NUSEQTELA', '=', 'acessotela.NUSEQTELA')
            ->whereRaw("TRIM(UPPER(acessousuario.INACESSO)) = 'S'")
            ->whereRaw("TRIM(UPPER(acessotela.FLACESSO)) = 'S'")
            ->pluck('acessousuario.NUSEQTELA')
            ->mapWithKeys(fn ($tela) => [(string) $tela => true])
            ->all();

        return $this->activeTelaAssignmentsMap;
    }

    /**
     * @return array<string, bool>
     */
    private function visibleTelaMap(): array
    {
        if ($this->visibleTelaMap !== null) {
            return $this->visibleTelaMap;
        }

        $this->visibleTelaMap = DB::table('acessotela')
            ->select('NUSEQTELA', 'FLACESSO')
            ->get()
            ->mapWithKeys(function ($tela) {
                return [
                    (string) $tela->NUSEQTELA => strtoupper(trim((string) ($tela->FLACESSO ?? 'N'))) === 'S',
                ];
            })
            ->all();

        return $this->visibleTelaMap;
    }

    /**
     * @param array<string, bool> $map
     */
    private function hasSolicitacoesSubscreenAccess(array $map): bool
    {
        foreach ([
            self::TELA_SOLICITACOES_VER_TODAS,
            self::TELA_SOLICITACOES_ATUALIZAR,
            self::TELA_SOLICITACOES_CRIAR,
            self::TELA_SOLICITACOES_APROVAR,
            self::TELA_SOLICITACOES_CANCELAR,
            self::TELA_SOLICITACOES_TRIAGEM_INICIAL,
            self::TELA_SOLICITACOES_LIBERACAO_ENVIO,
            self::TELA_SOLICITACOES_AUTORIZACAO_LIBERACAO,
        ] as $tela) {
            if (!empty($map[$tela])) {
                return true;
            }
        }

        return false;
    }
}
