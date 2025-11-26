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
        return view('usuarios.edit', compact('usuario'));
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $request->validate([
            'NOMEUSER' => ['required', 'string', 'max:80'],
            'NMLOGIN' => ['required', 'string', 'max:30', Rule::unique('usuario', 'NMLOGIN')->ignore($usuario->NUSEQUSUARIO, 'NUSEQUSUARIO')],
            'CDMATRFUNCIONARIO' => ['required', 'string', 'max:8', Rule::unique('usuario', 'CDMATRFUNCIONARIO')->ignore($usuario->NUSEQUSUARIO, 'NUSEQUSUARIO')],
            'PERFIL' => ['required', Rule::in(['ADM', 'USR'])],
            'SENHA' => ['nullable', 'string', 'min:8'], // Senha é opcional na edição
        ]);

        $usuario->NOMEUSER = $request->NOMEUSER;
        $usuario->NMLOGIN = $request->NMLOGIN;
        $usuario->CDMATRFUNCIONARIO = $request->CDMATRFUNCIONARIO;
        $usuario->PERFIL = $request->PERFIL;

        if ($request->filled('SENHA')) {
            $usuario->SENHA = $request->SENHA; // O Model criptografa
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
}
