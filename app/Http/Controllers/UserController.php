<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Funcionario;
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
    public function index(Request $request)
    {
        $query = User::query();

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

        $usuarios = $query->orderBy('NOMEUSER')->paginate(10);

        // Se requisição é AJAX com api=1, retorna JSON com HTML das linhas
        if ($request->has('api') && $request->input('api') === '1') {
            $html = view('usuarios._table_rows_usuarios', compact('usuarios'))->render();
            return response()->json(['html' => $html]);
        }

        if ($request->ajax()) {
            return view('usuarios._table_partial', compact('usuarios'))->render();
        }
        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * Impersonate another user (developer/admin helper).
     * Allowed only in local environment or for admins.
     */
    public function impersonate(User $usuario)
    {
        /** @var \App\Models\User $me */
        $me = Auth::user();
        if (!(app()->environment('local') || $me->isAdmin())) {
            abort(403);
        }

        Log::info('Impersonation requested', [
            'by_login' => $me->NMLOGIN ?? null,
            'by_id' => $me->NUSEQUSUARIO ?? null,
            'target_login' => $usuario->NMLOGIN ?? null,
            'target_id' => $usuario->NUSEQUSUARIO ?? null,
            'env' => app()->environment(),
            'session_before' => session()->all(),
        ]);

        // Store original user id so we can restore and mark impersonation active
        session([
            'impersonator_id' => $me->NUSEQUSUARIO,
            'is_impersonating' => true,
        ]);
        Auth::loginUsingId($usuario->NUSEQUSUARIO);

        Log::info('Impersonation started', [
            'by_login' => $me->NMLOGIN ?? null,
            'by_id' => $me->NUSEQUSUARIO ?? null,
            'target_login' => $usuario->NMLOGIN ?? null,
            'target_id' => $usuario->NUSEQUSUARIO ?? null,
            'session_after' => session()->all(),
        ]);

        return redirect()->route('patrimonios.index')->with('success', "Agora você está como {$usuario->NOMEUSER} ({$usuario->NMLOGIN})");
    }

    /**
     * Stop impersonation and restore original user.
     */
    public function stopImpersonate(Request $request)
    {
        // Log the incoming stop request for debugging
        Log::info('Impersonation stop requested', [
            'current_user' => Auth::user()->NMLOGIN ?? null,
            'current_user_id' => Auth::id(),
            'session_before' => session()->all(),
            'request_ip' => $request->ip(),
            'request_url' => $request->fullUrl(),
        ]);

        // Preferência: read value first, ensure it exists, then clear session keys
        $impId = session()->get('impersonator_id');
        if (!$impId) {
            Log::warning('Impersonation stop failed: no impersonator_id in session', ['session' => session()->all()]);
            abort(403);
        }

        // Clear impersonation flags from session
        session()->forget(['impersonator_id', 'is_impersonating']);

        // Restore original user and regenerate session to avoid fixation
        $original = User::find($impId);
        if (!$original) {
            Log::error('Impersonation stop failed: original user not found', ['impersonator_id' => $impId]);
            abort(404, 'Usuário original não encontrado');
        }

        Auth::login($original);
        // regenerate session id and data to be safe
        try {
            session()->regenerate();
        } catch (\Throwable $e) {
            Log::debug('Session regenerate failed', ['error' => $e->getMessage()]);
        }

        Log::info('Impersonation stopped', [
            'restored_login' => $original->NMLOGIN ?? null,
            'restored_id' => $original->NUSEQUSUARIO ?? null,
            'session_after' => session()->all(),
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Impersonation finalizada. Você voltou à sua conta.');
    }

    /**
     * Reset a user's password and return a temporary password (JSON).
     * Allowed only in local environment or for admins.
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
        return view('usuarios.create');
    }

    public function store(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'NOMEUSER' => ['required', 'string', 'max:80'],
            'NMLOGIN' => ['required', 'string', 'max:30', 'unique:usuario,NMLOGIN'],
            'CDMATRFUNCIONARIO' => ['required', 'string', 'max:8', 'unique:usuario,CDMATRFUNCIONARIO'],
            'PERFIL' => ['required', \Illuminate\Validation\Rule::in(['ADM', 'USR'])],
        ]);

        // Senha provisória forte: prefixo 'Plansul@' + 6 números aleatórios
        $randomNumbers = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $senhaProvisoria = 'Plansul@' . $randomNumbers;

        $user = User::create([
            'NOMEUSER' => $request->NOMEUSER,
            'NMLOGIN' => $request->NMLOGIN,
            'CDMATRFUNCIONARIO' => $request->CDMATRFUNCIONARIO,
            'PERFIL' => $request->PERFIL,
            'SENHA' => $senhaProvisoria,
            'LGATIVO' => 'S',
            'must_change_password' => true,
        ]);

        // Retornar tela de confirmação com credenciais para copiar/repasse
        return view('usuarios.confirmacao', [
            'nmLogin' => $user->NMLOGIN,
            'senhaProvisoria' => $senhaProvisoria,
        ]);
    }

    public function edit(User $usuario): View
    {
        // Carrega usuários USR disponíveis para supervisão
        $usuariosUsrDisponiveis = User::where('PERFIL', 'USR')
            ->where('NUSEQUSUARIO', '!=', $usuario->NUSEQUSUARIO)
            ->orderBy('NOMEUSER')
            ->get(['NUSEQUSUARIO', 'NOMEUSER', 'NMLOGIN']);

        // Pré-compõe um array simples para uso em JS no template (evita eval complexa no Blade)
        $usuariosUsrDisponiveisArray = $usuariosUsrDisponiveis->map(function ($u) {
            return [
                'NUSEQUSUARIO' => $u->NUSEQUSUARIO,
                'NOMEUSER' => $u->NOMEUSER,
                'NMLOGIN' => $u->NMLOGIN,
            ];
        })->values()->toArray();

        return view('usuarios.edit', compact('usuario', 'usuariosUsrDisponiveis', 'usuariosUsrDisponiveisArray'));
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        // Normaliza supervisor_de para garantir que cada item seja string
        if ($request->has('supervisor_de')) {
            $vals = $request->input('supervisor_de');
            if (is_array($vals)) {
                $request->merge(['supervisor_de' => array_map('strval', $vals)]);
            }
        }

        $request->validate([
            'NOMEUSER' => ['required', 'string', 'max:80'],
            'NMLOGIN' => ['required', 'string', 'max:30', Rule::unique('usuario', 'NMLOGIN')->ignore($usuario->NUSEQUSUARIO, 'NUSEQUSUARIO')],
            'CDMATRFUNCIONARIO' => ['required', 'string', 'max:8', Rule::unique('usuario', 'CDMATRFUNCIONARIO')->ignore($usuario->NUSEQUSUARIO, 'NUSEQUSUARIO')],
            'PERFIL' => ['required', Rule::in(['ADM', 'USR'])],
            'SENHA' => ['nullable', 'string', 'min:8'],
            'supervisor_de' => ['nullable', 'array'],
            'supervisor_de.*' => ['string'],
        ]);

        $usuario->NOMEUSER = $request->NOMEUSER;
        $usuario->NMLOGIN = $request->NMLOGIN;
        $usuario->CDMATRFUNCIONARIO = $request->CDMATRFUNCIONARIO;
        $usuario->PERFIL = $request->PERFIL;

        if ($request->filled('SENHA')) {
            $usuario->SENHA = $request->SENHA;
        }

        // Salvar supervisão se fornecida
        if ($request->has('supervisor_de')) {
            $novosSupervisionados = array_unique(array_filter($request->input('supervisor_de', [])));
            
            // Validar que todos os logins existem e são USR
            $loginsValidos = User::whereIn('NMLOGIN', $novosSupervisionados)
                ->where('PERFIL', 'USR')
                ->where('NUSEQUSUARIO', '!=', $usuario->NUSEQUSUARIO)
                ->pluck('NMLOGIN')
                ->toArray();

            $usuario->supervisor_de = count($loginsValidos) > 0 ? $loginsValidos : null;
        }

        $usuario->save();

        return redirect()->route('usuarios.index')->with('success', 'Usuário atualizado com sucesso!');
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

    // === Supervisão de Usuários ===
    public function gerenciarSupervisao(User $usuario)
    {
        // Lista de usuários USR disponíveis para supervisão
        $usuariosDisponiveis = User::where('PERFIL', 'USR')
            ->where('NUSEQUSUARIO', '!=', $usuario->NUSEQUSUARIO)
            ->orderBy('NOMEUSER')
            ->get(['NUSEQUSUARIO', 'NOMEUSER', 'NMLOGIN']);

        $supervisionados = $usuario->supervisor_de ?? [];

        return view('usuarios.supervisao', compact('usuario', 'usuariosDisponiveis', 'supervisionados'));
    }

    public function atualizarSupervisao(Request $request, User $usuario): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'supervisionados' => ['nullable', 'array'],
            'supervisionados.*' => ['string'],
        ]);

        $novosSupervisionados = array_unique(array_filter($request->input('supervisionados', [])));
        
        // Validar que todos os logins existem e são USR
        $loginsValidos = User::whereIn('NMLOGIN', $novosSupervisionados)
            ->where('PERFIL', 'USR')
            ->where('NUSEQUSUARIO', '!=', $usuario->NUSEQUSUARIO)
            ->pluck('NMLOGIN')
            ->toArray();

        $usuario->supervisor_de = count($loginsValidos) > 0 ? $loginsValidos : null;
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => "Supervisão atualizada! {$usuario->NOMEUSER} agora supervisiona " . count($loginsValidos) . " usuários.",
            'count' => count($loginsValidos),
        ]);
    }
}
