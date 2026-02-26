<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Funcionario;
use App\Models\AcessoUsuario;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    private const TELA_PATRIMONIO = 1000;
    private const TELA_GERENCIAR_ACESSOS = 1005;
    private const TELA_RELATORIOS = 1006;

    public function index(Request $request)
    {
        $query = User::query();
        $currentUserId = Auth::id();

        $search = $request->input('search', $request->input('busca', ''));
        $terms = [];
        if (!empty($search)) {
            // Aceita string separada por vírgula, pipe ou array
            if (is_array($search)) {
                $terms = $search;
            } else {
                $terms = preg_split('/[\s,|]+/', $search, -1, PREG_SPLIT_NO_EMPTY);
            }
        }
        if (count($terms)) {
            foreach ($terms as $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('NOMEUSER', 'like', "%$term%")
                        ->orWhere('NMLOGIN', 'like', "%$term%")
                        ->orWhere('CDMATRFUNCIONARIO', 'like', "%$term%")
                        ->orWhere('UF', 'like', "%$term%")
                        ->orWhere('PERFIL', 'like', "%$term%")
                    ;
                });
            }
        }

        $sortableColumns = ['NOMEUSER', 'NMLOGIN', 'CDMATRFUNCIONARIO', 'UF', 'PERFIL'];
        $sort = strtoupper((string) $request->input('sort', 'NOMEUSER'));
        if (!in_array($sort, $sortableColumns, true)) {
            $sort = 'NOMEUSER';
        }
        $direction = strtolower((string) $request->input('direction', 'asc'));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        if ($currentUserId) {
            $query->orderByRaw('CASE WHEN NUSEQUSUARIO = ? THEN 0 ELSE 1 END', [$currentUserId]);
        }
        $query->orderBy($sort, $direction)->orderBy('NOMEUSER');

        $usuarios = $query->paginate(30)->withQueryString();

        // Se requisição é AJAX com api=1, retorna JSON com HTML das linhas
        if ($request->has('api') && $request->input('api') === '1') {
            $html = view('usuarios._table_rows_usuarios', compact('usuarios', 'currentUserId'))->render();
            return response()->json(['html' => $html]);
        }

        if ($request->ajax()) {
            return view('usuarios._table_partial', compact('usuarios', 'currentUserId', 'sort', 'direction'))->render();
        }
        return view('usuarios.index', compact('usuarios', 'currentUserId', 'sort', 'direction'));
    }

    /**
     * Personificar outro usuário (ferramenta dev/admin).
     * Permitido apenas em ambiente local ou para administradores.
     */
    public function impersonate(User $usuario)
    {
        /** @var \App\Models\User $me */
        $me = Auth::user();
        if (!(app()->environment('local') || $me->isAdmin())) {
            abort(403);
        }

        Log::info('[PERSONIFICACAO] Solicitada', [
            'por_login' => $me->NMLOGIN ?? null,
            'por_id' => $me->NUSEQUSUARIO ?? null,
            'alvo_login' => $usuario->NMLOGIN ?? null,
            'alvo_id' => $usuario->NUSEQUSUARIO ?? null,
            'ambiente' => app()->environment(),
        ]);

        // Armazenar ID do usuário original para restauração
        session([
            'impersonator_id' => $me->NUSEQUSUARIO,
            'is_impersonating' => true,
        ]);
        Auth::loginUsingId($usuario->NUSEQUSUARIO);

        Log::info('[PERSONIFICACAO] Iniciada', [
            'por_login' => $me->NMLOGIN ?? null,
            'alvo_login' => $usuario->NMLOGIN ?? null,
        ]);

        return redirect()->route('patrimonios.index')->with('success', "Agora você está como {$usuario->NOMEUSER} ({$usuario->NMLOGIN})");
    }

    /**
     * Parar personificação e restaurar usuário original.
     */
    public function stopImpersonate(Request $request)
    {
        // Log da solicitação de parada para depuração
        Log::info('[PERSONIFICACAO] Parada solicitada', [
            'usuario_atual' => Auth::user()->NMLOGIN ?? null,
            'usuario_atual_id' => Auth::id(),
        ]);

        // Ler valor e verificar existência antes de limpar sessão
        $impId = session()->get('impersonator_id');
        if (!$impId) {
            Log::warning('[PERSONIFICACAO] Falha: sem impersonator_id na sessão');
            abort(403);
        }

        // Limpar flags de personificação da sessão
        session()->forget(['impersonator_id', 'is_impersonating']);

        // Restaurar usuário original e regenerar sessão
        $original = User::find($impId);
        if (!$original) {
            Log::error('[PERSONIFICACAO] Usuário original não encontrado', ['impersonator_id' => $impId]);
            abort(404, 'Usuário original não encontrado');
        }

        Auth::login($original);
        try {
            session()->regenerate();
        } catch (\Throwable $e) {
            Log::debug('Falha ao regenerar sessão', ['erro' => $e->getMessage()]);
        }

        Log::info('[PERSONIFICACAO] Finalizada', [
            'restaurado_login' => $original->NMLOGIN ?? null,
            'restaurado_id' => $original->NUSEQUSUARIO ?? null,
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Personificação finalizada. Você voltou à sua conta.');
    }

    /**
     * Resetar senha de um usuário e retornar senha temporária (JSON).
     * Permitido apenas em ambiente local ou para administradores.
     */
    public function resetSenha(User $usuario)
    {
        /** @var \App\Models\User $me */
        $me = Auth::user();
        if (!(app()->environment('local') || $me->isAdmin())) {
            return response()->json(['success' => false, 'message' => 'Não autorizado'], 403);
        }

        // Gerar senha provisória: Plansul@ + 6 dígitos
        $randomNumbers = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $senhaProvisoria = 'Plansul@' . $randomNumbers;

        $usuario->SENHA = $senhaProvisoria; // mutator vai aplicar Hash::make
        $usuario->must_change_password = true;
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Senha provisória gerada com sucesso.',
            'senha' => $senhaProvisoria,
            'login' => $usuario->NMLOGIN,
        ]);
    }

    public function create(): View
    {
        $telasDisponiveis = $this->carregarTelasDisponiveis();
        $acessosAtuais = [];

        return view('usuarios.create', compact('telasDisponiveis', 'acessosAtuais'));
    }

    public function store(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $matriculaInput = trim((string) ($request->CDMATRFUNCIONARIO ?? ''));

        $rules = [
            'NOMEUSER' => ['nullable', 'string', 'max:80'],
            'NMLOGIN' => ['required', 'string', 'max:30', 'unique:usuario,NMLOGIN'],
            'PERFIL' => ['required', \Illuminate\Validation\Rule::in(['ADM', 'USR', 'C'])],
            'needs_identity_update' => ['nullable', 'boolean'],
            'telas' => ['nullable', 'array'],
            'telas.*' => ['integer', 'exists:acessotela,NUSEQTELA'],
        ];

        $matriculaRules = ['nullable', 'string', 'max:8'];
        if ($matriculaInput !== '') {
            $matriculaRules[] = Rule::unique('usuario', 'CDMATRFUNCIONARIO');
        }
        $rules['CDMATRFUNCIONARIO'] = $matriculaRules;

        $request->validate($rules);

        // Senha provisória forte: prefixo 'Plansul@' + 6 números aleatórios
        $randomNumbers = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $senhaProvisoria = 'Plansul@' . $randomNumbers;

        $matricula = $matriculaInput !== '' ? $matriculaInput : User::generateTemporaryMatricula();
        $forceIdentityUpdate = $request->boolean('needs_identity_update', false)
            || User::isPlaceholderMatriculaValue($matricula)
            || trim((string) ($request->NOMEUSER ?? '')) === '';

        $user = User::create([
            'NOMEUSER' => $request->NOMEUSER,
            'NMLOGIN' => $request->NMLOGIN,
            'CDMATRFUNCIONARIO' => $matricula,
            'PERFIL' => $request->PERFIL,
            'SENHA' => $senhaProvisoria,
            'LGATIVO' => 'S',
            'must_change_password' => true,
            'needs_identity_update' => $forceIdentityUpdate,
        ]);

        /** @var User|null $me */
        $me = Auth::user();
        if ($me?->isAdmin()) {
            $telasSelecionadas = $this->normalizarTelas($request->input('telas', []));
            $this->syncTelas($user->CDMATRFUNCIONARIO, $telasSelecionadas);
        }

        // Retornar tela de confirmação com credenciais para copiar/repasse
        return view('usuarios.confirmacao', [
            'nmLogin' => $user->NMLOGIN,
            'senhaProvisoria' => $senhaProvisoria,
        ]);
    }

    public function edit(User $usuario): View
    {
        $telasDisponiveis = $this->carregarTelasDisponiveis();
        $acessosAtuais = $this->carregarAcessosAtuais($usuario->CDMATRFUNCIONARIO);

        return view('usuarios.edit', compact('usuario', 'telasDisponiveis', 'acessosAtuais'));
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $matriculaInput = trim((string) ($request->CDMATRFUNCIONARIO ?? ''));

        $rules = [
            'NOMEUSER' => ['nullable', 'string', 'max:80'],
            'NMLOGIN' => ['required', 'string', 'max:30', Rule::unique('usuario', 'NMLOGIN')->ignore($usuario->NUSEQUSUARIO, 'NUSEQUSUARIO')],
            'PERFIL' => ['required', Rule::in(['ADM', 'USR', 'C'])],
            'needs_identity_update' => ['nullable', 'boolean'],
            'SENHA' => ['nullable', 'string', 'min:8'],
            'telas' => ['nullable', 'array'],
            'telas.*' => ['integer', 'exists:acessotela,NUSEQTELA'],
        ];

        $matriculaRules = ['nullable', 'string', 'max:8'];
        if ($matriculaInput !== '') {
            $matriculaRules[] = Rule::unique('usuario', 'CDMATRFUNCIONARIO')->ignore($usuario->NUSEQUSUARIO, 'NUSEQUSUARIO');
        }
        $rules['CDMATRFUNCIONARIO'] = $matriculaRules;

        $request->validate($rules);

        $oldMatricula = $usuario->CDMATRFUNCIONARIO;
        $matricula = $matriculaInput !== '' ? $matriculaInput : User::generateTemporaryMatricula();
        $forceIdentityUpdate = $request->boolean('needs_identity_update', false)
            || User::isPlaceholderMatriculaValue($matricula)
            || trim((string) ($request->NOMEUSER ?? '')) === '';

        $usuario->NOMEUSER = $request->NOMEUSER;
        $usuario->NMLOGIN = $request->NMLOGIN;
        $usuario->CDMATRFUNCIONARIO = $matricula;
        $usuario->PERFIL = $request->PERFIL;
        $usuario->needs_identity_update = $forceIdentityUpdate;

        if ($request->filled('SENHA')) {
            $usuario->SENHA = $request->SENHA;
        }

        $usuario->save();

        /** @var User|null $me */
        $me = Auth::user();
        if ($me?->isAdmin()) {
            $newMatricula = $usuario->CDMATRFUNCIONARIO;

            if (!empty($oldMatricula) && !empty($newMatricula) && $oldMatricula !== $newMatricula) {
                $this->migrateAcessosMatricula($oldMatricula, $newMatricula);
            }

            $telasSelecionadas = $this->normalizarTelas($request->input('telas', []));
            $this->syncTelas($usuario->CDMATRFUNCIONARIO, $telasSelecionadas);
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuário atualizado com sucesso!');
    }

    private function grantTela(?string $matricula, int $tela): void
    {
        $matricula = trim((string) $matricula);
        if ($matricula === '') {
            return;
        }

        $exists = AcessoUsuario::query()
            ->where('CDMATRFUNCIONARIO', $matricula)
            ->where('NUSEQTELA', $tela)
            ->exists();

        if ($exists) {
            AcessoUsuario::query()
                ->where('CDMATRFUNCIONARIO', $matricula)
                ->where('NUSEQTELA', $tela)
                ->update(['INACESSO' => 'S']);
            return;
        }

        AcessoUsuario::create([
            'CDMATRFUNCIONARIO' => $matricula,
            'NUSEQTELA' => $tela,
            'INACESSO' => 'S',
        ]);
    }

    private function syncTela(?string $matricula, int $tela, bool $allow): void
    {
        $matricula = trim((string) $matricula);
        if ($matricula === '') {
            return;
        }

        if ($allow) {
            $this->grantTela($matricula, $tela);
            return;
        }

        AcessoUsuario::query()
            ->where('CDMATRFUNCIONARIO', $matricula)
            ->where('NUSEQTELA', $tela)
            ->delete();
    }

    private function carregarTelasDisponiveis()
    {
        return DB::table('acessotela')
            ->whereRaw("TRIM(UPPER(FLACESSO)) = 'S'")
            ->where('NUSEQTELA', '!=', self::TELA_GERENCIAR_ACESSOS)
            ->orderBy('NUSEQTELA')
            ->get();
    }

    private function carregarAcessosAtuais(?string $matricula): array
    {
        $matricula = trim((string) $matricula);
        if ($matricula === '') {
            return [];
        }

        return AcessoUsuario::query()
            ->where('CDMATRFUNCIONARIO', $matricula)
            ->whereRaw("TRIM(UPPER(INACESSO)) = 'S'")
            ->pluck('NUSEQTELA')
            ->toArray();
    }

    private function normalizarTelas($telas): array
    {
        if (!is_array($telas)) {
            return [];
        }

        $normalizadas = [];
        foreach ($telas as $tela) {
            if (is_numeric($tela)) {
                $normalizadas[] = (int) $tela;
            }
        }

        return array_values(array_unique($normalizadas));
    }

    private function syncTelas(?string $matricula, array $telas, array $ignorar = []): void
    {
        $matricula = trim((string) $matricula);
        if ($matricula === '') {
            return;
        }

        $telas = $this->normalizarTelas($telas);
        $ignorar = $this->normalizarTelas($ignorar);
        $telas = array_values(array_diff($telas, $ignorar));
        if (!in_array(self::TELA_PATRIMONIO, $telas, true)) {
            $telas[] = self::TELA_PATRIMONIO;
        }
        if (!in_array(self::TELA_RELATORIOS, $telas, true)) {
            $telas[] = self::TELA_RELATORIOS;
        }

        DB::transaction(function () use ($matricula, $telas, $ignorar) {
            $deleteQuery = AcessoUsuario::query()
                ->where('CDMATRFUNCIONARIO', $matricula);

            if (!empty($ignorar)) {
                $deleteQuery->whereNotIn('NUSEQTELA', $ignorar);
            }

            $deleteQuery->delete();

            foreach ($telas as $tela) {
                AcessoUsuario::create([
                    'CDMATRFUNCIONARIO' => $matricula,
                    'NUSEQTELA' => $tela,
                    'INACESSO' => 'S',
                ]);
            }
        });
    }

    private function migrateAcessosMatricula(string $from, string $to): void
    {
        $from = trim($from);
        $to = trim($to);
        if ($from === '' || $to === '' || $from === $to) {
            return;
        }

        DB::transaction(function () use ($from, $to) {
            $rows = AcessoUsuario::query()
                ->where('CDMATRFUNCIONARIO', $from)
                ->get(['NUSEQTELA', 'INACESSO']);

            if ($rows->isEmpty()) {
                return;
            }

            AcessoUsuario::query()
                ->where('CDMATRFUNCIONARIO', $from)
                ->delete();

            foreach ($rows as $row) {
                $tela = (int) $row->NUSEQTELA;
                $allow = (bool) $row->INACESSO;
                $this->syncTela($to, $tela, $allow);
            }
        });
    }

    public function destroy(User $usuario)
    {
        // Regra de segurança para não se auto-deletar
        if ($usuario->id === Auth::id()) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Você não pode deletar seu próprio usuário.'], 403);
            }
            return redirect()->route('usuarios.index')->with('error', 'Você não pode deletar seu próprio usuário.');
        }

        $usuario->delete();
        
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Usuário deletado com sucesso!'], 200);
        }
        
        return redirect()->route('usuarios.index')->with('success', 'Usuário deletado com sucesso!');
    }

    // === APIs auxiliares para o form ===
    public function porMatricula(Request $request)
    {
        $mat = trim((string)$request->query('matricula', ''));
        if ($mat === '') return response()->json(['exists' => false]);
        // Busca no cadastro de funcionários (fonte de verdade do nome)
        $f = Funcionario::where('CDMATRFUNCIONARIO', $mat)->first(['NMFUNCIONARIO']);
        $nomeLimpo = $f ? $this->sanitizeNome($f->NMFUNCIONARIO) : null;
        return response()->json([
            'exists' => (bool)$f,
            'nome'   => $nomeLimpo,
        ]);
    }

    public function sugerirLogin(Request $request)
    {
        $nome = trim((string)$request->query('nome', ''));
        $matricula = trim((string)$request->query('matricula', ''));
        if ($nome === '') return response()->json(['login' => '']);
        // Base no formato AOliveira (Inicial + Último Sobrenome, CamelCase sem acento)
        $baseCamel = $this->slugLogin($nome);
        if ($baseCamel === '') return response()->json(['login' => '']);

        // Estratégia de fortalecimento e unicidade:
        // 1) Tentar a base CamelCase original (AOliveira)
        // 2) Se indisponível ou curto demais, tentar minúsculo com último nome + inicial (oliveiraa)
        // 3) Se ainda indisponível, anexar parte da matrícula (ex.: AOliveira123) quando fornecida
        // 4) Por fim, fallback incremental com números
        $candidatos = [];
        $candidatos[] = $baseCamel; // AOliveira

        $iniLower = strtolower(substr($baseCamel, 0, 1));
        $lastLower = strtolower(substr($baseCamel, 1));
        if ($lastLower !== '') {
            $candidatos[] = $lastLower . $iniLower; // oliveiraa
        }

        if ($matricula !== '') {
            $matSan = preg_replace('/[^0-9]/', '', $matricula);
            if ($matSan !== '') {
                $suffix = substr($matSan, -3); // usar últimos 3 dígitos
                $candidatos[] = $baseCamel . $suffix; // AOliveira123
                if ($lastLower !== '') $candidatos[] = $lastLower . $suffix; // oliveira123
            }
        }

        // Remover duplicatas mantendo ordem
        $candidatos = array_values(array_unique(array_filter($candidatos)));

        // Escolher o primeiro disponível e suficientemente forte (>= 4 chars)
        foreach ($candidatos as $cand) {
            if (strlen($cand) >= 4 && $this->isLoginAvailable($cand)) {
                return response()->json(['login' => $cand]);
            }
        }

        // Fallback incremental
        $login = $this->gerarLoginUnico($baseCamel);
        return response()->json(['login' => $login]);
    }

    public function loginDisponivel(Request $request)
    {
        $login = trim((string)$request->query('login', ''));
        $ignore = $request->query('ignore');
        if ($login === '') return response()->json(['available' => false]);
        $q = User::where('NMLOGIN', $login);
        if ($ignore) $q->where('NUSEQUSUARIO', '!=', (int)$ignore);
        $exists = $q->exists();
        return response()->json(['available' => !$exists]);
    }

    private function slugLogin(string $nome): string
    {
        // Gera base no formato: InicialPrimeiroNome + UltimoSobrenome (CamelCase), sem acentos e sem caracteres especiais
        $nome = $this->sanitizeNome($nome);
        $parts = preg_split('/\s+/', trim($nome));
        $parts = array_values(array_filter($parts));
        if (empty($parts)) return '';
        $first = $parts[0] ?? '';
        $last  = $parts[count($parts) - 1] ?? '';
        $ini   = $first !== '' ? mb_substr($first, 0, 1) : '';
        // Remove acentos
        $iniAscii  = iconv('UTF-8', 'ASCII//TRANSLIT', $ini) ?: '';
        $lastAscii = iconv('UTF-8', 'ASCII//TRANSLIT', $last) ?: '';
        // CamelCase: inicial maiúscula + último sobrenome com primeira maiúscula e restante minúsculo
        $lastCamel = ucfirst(strtolower($lastAscii));
        // Remover qualquer não-letra
        $iniAscii  = preg_replace('/[^A-Za-z]/', '', $iniAscii);
        $lastCamel = preg_replace('/[^A-Za-z]/', '', $lastCamel);
        $base = $iniAscii . $lastCamel; // Ex.: AOliveira
        if ($base === '' && $first) {
            $fAscii = iconv('UTF-8', 'ASCII//TRANSLIT', $first) ?: '';
            $base = preg_replace('/[^A-Za-z]/', '', ucfirst(strtolower($fAscii)));
        }
        return $base;
    }

    private function sanitizeNome(string $nome): string
    {
        // Mantém apenas letras (incl. acentos) e espaços, normaliza múltiplos espaços e aplica trim
        $nome = preg_replace('/[^\p{L}\s]/u', ' ', $nome ?? '');
        $nome = preg_replace('/\s+/u', ' ', $nome);
        return trim($nome);
    }

    private function gerarLoginUnico(string $base): string
    {
        $login = $base;
        $i = 0;
        while (User::where('NMLOGIN', $login)->exists()) {
            $i++;
            $login = $base . $i;
            if ($i > 1000) break; // fallback de segurança
        }
        return $login;
    }

    private function isLoginAvailable(string $login): bool
    {
        return !User::where('NMLOGIN', $login)->exists();
    }
}
