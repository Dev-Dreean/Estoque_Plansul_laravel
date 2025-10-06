<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Patrimonio;
use App\Models\User;
use App\Models\ObjetoPatr;
use App\Models\Objpatr;
use App\Models\Tabfant;
use App\Models\LocalProjeto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\HistoricoMovimentacao;
use App\Models\TermoCodigo;
use App\Services\CodigoService;
use Illuminate\Validation\ValidationException;

class PatrimonioController extends Controller
{

    public function buscarCodigoObjeto($codigo)
    {
        try {
            $codigo = trim($codigo);
            // Usa a tabela principal de códigos (objetopatr)
            $registro = ObjetoPatr::where('NUSEQOBJETO', $codigo)->first();
            if (!$registro) {
                return response()->json(['found' => false, 'message' => 'Código não encontrado.'], 404);
            }
            return response()->json([
                'found'     => true,
                'descricao' => $registro->DEOBJETO,
                'tipo'      => $registro->NUSEQTIPOPATR,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro buscarCodigoObjeto: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            // Evita erro 500 no front: retorna 404 genérico quando houver exceção não crítica
            return response()->json(['found' => false, 'message' => 'Código não encontrado.'], 404);
        }
    }

    /**
     * Autocomplete de códigos de objeto (CODOBJETO). Busca por número parcial ou parte da descrição.
     */
    public function pesquisarCodigos(Request $request): JsonResponse
    {
        try {
            $termo = trim((string) $request->input('q', ''));
            if ($termo === '') return response()->json([]);
            $q = ObjetoPatr::query();
            if (is_numeric($termo)) {
                $q->where('NUSEQOBJETO', 'like', "%{$termo}%");
            } else {
                $q->where('DEOBJETO', 'like', "%{$termo}%");
            }
            $registros = $q->orderBy('DEOBJETO')
                ->select(['NUSEQOBJETO as CODOBJETO', 'DEOBJETO as DESCRICAO'])
                ->limit(10)
                ->get();
            return response()->json($registros);
        } catch (\Throwable $e) {
            Log::error('Erro pesquisarCodigos: ' . $e->getMessage());
            // Resposta segura para não quebrar o front
            return response()->json([], 200);
        }
    }

    public function index(Request $request): View
    {
        // Busca os patrimônios para a tabela principal
        $query = $this->getPatrimoniosQuery($request);

        // per_page opcional via querystring, padrão 30, mínimo 10, máximo 500
        $perPage = (int) $request->input('per_page', 30);
        if ($perPage < 10) $perPage = 10;
        if ($perPage > 500) $perPage = 500;

        $patrimonios = $query->paginate($perPage)->withQueryString();

        // Busca os usuários para o filtro de admin
        $cadastradores = [];
        if (Auth::user()->PERFIL === 'ADM') {
            $cadastradores = User::orderBy('NOMEUSER')->get(['CDMATRFUNCIONARIO', 'NOMEUSER']);
        }

        // Busca os locais para o modal de relatório geral
        $locais = Tabfant::select('id as codigo', 'LOCAL as descricao')
            ->orderBy('descricao')
            ->get();

        // Busca os patrimônios disponíveis para o modal de atribuição de termo
        $patrimoniosDisponiveis = \App\Models\Patrimonio::whereNull('NMPLANTA')
            ->orderBy('DEPATRIMONIO')
            ->paginate($perPage, ['*'], 'disponiveisPage')->withQueryString();

        // Return unificado e corrigido, enviando TODAS as variáveis necessárias
        return view('patrimonios.index', [
            'patrimonios' => $patrimonios,
            'cadastradores' => $cadastradores,
            'locais' => $locais,
            'patrimoniosDisponiveis' => $patrimoniosDisponiveis,
            'filters' => $request->only(['descricao', 'situacao', 'modelo', 'cadastrado_por']),
            'sort' => ['column' => $request->input('sort', 'NUSEQPATR'), 'direction' => 'desc'],
        ]);
    }

    public function lookupCodigo(Request $request)
    {
        $request->validate(['codigo' => 'required|integer']);
        $objeto = ObjetoPatr::find($request->codigo);

        if ($objeto) {
            return response()->json([
                'found' => true,
                'descricao' => $objeto->ds_obj,
            ]);
        }

        return response()->json(['found' => false]);
    }

    /**
     * Mostra o formulário de criação.
     */
    public function create(): View
    {
        // TODO: Substitua estes arrays pelas suas consultas reais ao banco de dados.
        $projetos = Tabfant::select('CDPROJETO', 'NOMEPROJETO')->distinct()->orderBy('NOMEPROJETO')->get();

        return view('patrimonios.create', compact('projetos'));
    }

    /**
     * Salva o novo patrimônio no banco de dados.
     * Regras:
     * - Se NUSEQOBJ (código) não existir em objetopatr, cria um novo registro com DEOBJETO.
     * - Em seguida, cria o Patrimônio referenciando esse código.
     */
    public function store(Request $request)
    {
        // 1) Validar os campos conforme o formulário (nomes em MAIÚSCULO)
        $validated = $request->validate([
            // O Nº Patrimônio pode se repetir entre tipos; removido UNIQUE
            'NUPATRIMONIO' => 'required|integer',
            'NUSEQOBJ' => 'required|integer',
            'DEOBJETO' => 'nullable|string|max:350', // obrigatória apenas quando código for novo
            'SITUACAO' => 'required|string|in:EM USO,CONSERTO,BAIXA,À DISPOSIÇÃO',
            'CDMATRFUNCIONARIO' => 'required|integer|exists:funcionarios,CDMATRFUNCIONARIO',
            'NUMOF' => 'nullable|integer',
            'DEHISTORICO' => 'nullable|string|max:300',
            'CDPROJETO' => 'nullable|integer',
            // O Local deve ser o código numérico (cdlocal) do LocalProjeto dentro do projeto
            'CDLOCAL' => 'nullable|integer',
            'NMPLANTA' => 'nullable|integer',
            'MARCA' => 'nullable|string|max:30',
            'MODELO' => 'nullable|string|max:30',
            'DTAQUISICAO' => 'nullable|date',
            'DTBAIXA' => 'required_if:SITUACAO,BAIXA|nullable|date',
        ]);

        // 2) Garantir existência do ObjetoPatr (tabela objetopatr)
        //    O Model ObjetoPatr usa PK 'NUSEQOBJETO'.
        $codigo = (int) $validated['NUSEQOBJ'];
        $objeto = ObjetoPatr::find($codigo);

        if (!$objeto) {
            // Se for novo código, exigir DEOBJETO
            $request->validate([
                'DEOBJETO' => 'required|string|max:350',
            ], [
                'DEOBJETO.required' => 'Informe a descrição do novo código.',
            ]);

            $objeto = ObjetoPatr::create([
                'NUSEQOBJETO' => $codigo,
                // NUSEQTIPOPATR pode ser opcional aqui; ajustar se sua regra exigir
                'DEOBJETO' => $request->input('DEOBJETO'),
            ]);
        }

        // 3) Criar o patrimônio associando o código recém-verificado/criado
        $usuarioCriador = Auth::user()->NMLOGIN ?? Auth::user()->NOMEUSER ?? 'SISTEMA';
        $dadosPatrimonio = [
            'NUPATRIMONIO' => (int) $validated['NUPATRIMONIO'],
            'CODOBJETO' => $codigo, // campo da tabela patr
            // Usaremos a descrição do objeto como DEPATRIMONIO para manter compatibilidade atual do front
            'DEPATRIMONIO' => $objeto->DEOBJETO ?? $request->input('DEOBJETO'),
            'SITUACAO' => $validated['SITUACAO'],
            'CDMATRFUNCIONARIO' => (int) $validated['CDMATRFUNCIONARIO'],
            'NUMOF' => $request->input('NUMOF'),
            'DEHISTORICO' => $request->input('DEHISTORICO'),
            'CDPROJETO' => $request->input('CDPROJETO'),
            'CDLOCAL' => $request->input('CDLOCAL'),
            'NMPLANTA' => $request->input('NMPLANTA'),
            'MARCA' => $request->input('MARCA'),
            'MODELO' => $request->input('MODELO'),
            'DTAQUISICAO' => $request->input('DTAQUISICAO'),
            'DTBAIXA' => $request->input('DTBAIXA'),
            'USUARIO' => $usuarioCriador,
            'DTOPERACAO' => now(),
        ];

        Patrimonio::create($dadosPatrimonio);

        return redirect()->route('patrimonios.index')
            ->with('success', 'Patrimônio cadastrado com sucesso!');
    }

    /**
     * Mostra o formulário de edição para um patrimônio específico.
     */
    public function edit(Patrimonio $patrimonio): View
    {
        $this->authorize('update', $patrimonio);

        // TODO: Substitua estes arrays pelas suas consultas reais ao banco de dados.
        $projetos = Tabfant::select('CDPROJETO', 'NOMEPROJETO')->distinct()->orderBy('NOMEPROJETO')->get();

        return view('patrimonios.edit', compact('patrimonio', 'projetos'));
    }

    /**
     * Atualiza um patrimônio existente no banco de dados.
     */
    public function update(Request $request, Patrimonio $patrimonio): RedirectResponse
    {
        $this->authorize('update', $patrimonio);
        $validatedData = $this->validatePatrimonio($request);

        // Detectar alterações relevantes
        $oldProjeto = $patrimonio->CDPROJETO;
        $oldSituacao = $patrimonio->SITUACAO;
        $patrimonio->update($validatedData);
        $newProjeto = $patrimonio->CDPROJETO;
        $newSituacao = $patrimonio->SITUACAO;

        // Registrar histórico quando o Projeto mudar
        if ($newProjeto != $oldProjeto) {
            try {
                $coAutor = null;
                $actorMat = Auth::user()->CDMATRFUNCIONARIO ?? null;
                $ownerMat = $patrimonio->CDMATRFUNCIONARIO;
                if (!empty($actorMat) && !empty($ownerMat) && $actorMat != $ownerMat) {
                    $coAutor = User::where('CDMATRFUNCIONARIO', $ownerMat)->value('NMLOGIN'); // registra dono como co-autor
                }
                HistoricoMovimentacao::create([
                    'TIPO' => 'projeto',
                    'CAMPO' => 'CDPROJETO',
                    'VALOR_ANTIGO' => $oldProjeto,
                    'VALOR_NOVO' => $newProjeto,
                    'NUPATR' => $patrimonio->NUPATRIMONIO,
                    'CODPROJ' => $newProjeto,
                    'USUARIO' => (Auth::user()->NMLOGIN ?? 'SISTEMA'),
                    'CO_AUTOR' => $coAutor,
                    'DTOPERACAO' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Falha ao gravar histórico de projeto', [
                    'patrimonio' => $patrimonio->NUSEQPATR,
                    'erro' => $e->getMessage()
                ]);
            }
        }

        // Registrar histórico quando a Situação mudar
        if ($newSituacao !== $oldSituacao) {
            try {
                $coAutor = null;
                $actorMat = Auth::user()->CDMATRFUNCIONARIO ?? null;
                $ownerMat = $patrimonio->CDMATRFUNCIONARIO;
                if (!empty($actorMat) && !empty($ownerMat) && $actorMat != $ownerMat) {
                    $coAutor = User::where('CDMATRFUNCIONARIO', $ownerMat)->value('NMLOGIN');
                }
                HistoricoMovimentacao::create([
                    'TIPO' => 'situacao',
                    'CAMPO' => 'SITUACAO',
                    'VALOR_ANTIGO' => $oldSituacao,
                    'VALOR_NOVO' => $newSituacao,
                    'NUPATR' => $patrimonio->NUPATRIMONIO,
                    'CODPROJ' => $newProjeto,
                    'USUARIO' => (Auth::user()->NMLOGIN ?? 'SISTEMA'),
                    'CO_AUTOR' => $coAutor,
                    'DTOPERACAO' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Falha ao gravar histórico (situação)', [
                    'patrimonio' => $patrimonio->NUSEQPATR,
                    'erro' => $e->getMessage()
                ]);
            }
        }
        return redirect()->route('patrimonios.index')->with('success', 'Patrimônio atualizado com sucesso!');
    }

    /**
     * Remove o patrimônio do banco de dados.
     */
    public function destroy(Patrimonio $patrimonio): RedirectResponse
    {
        $this->authorize('delete', $patrimonio);
        $patrimonio->delete();
        return redirect()->route('patrimonios.index')->with('success', 'Patrimônio deletado com sucesso!');
    }

    // --- MÉTODOS DE API PARA O FORMULÁRIO DINÂMICO ---

    public function buscarPorNumero($numero): JsonResponse
    {
        $patrimonio = Patrimonio::where('NUPATRIMONIO', $numero)->first();
        if ($patrimonio) {
            return response()->json($patrimonio);
        }
        return response()->json(null, 404);
    }

    public function pesquisar(Request $request): JsonResponse
    {
        $termo = $request->input('q', '');
        $patrimonios = Patrimonio::query()
            ->where('DEPATRIMONIO', 'like', "%{$termo}%")
            ->orWhere('NUPATRIMONIO', 'like', "%{$termo}%")
            ->select(['NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'SITUACAO'])
            ->limit(10)->get();
        return response()->json($patrimonios);
    }

    // Método pesquisarUsuarios removido após migração para FuncionarioController::pesquisar

    public function buscarProjeto($cdprojeto): JsonResponse
    {
        $projeto = Tabfant::where('CDPROJETO', $cdprojeto)->first(['NOMEPROJETO']);
        return response()->json($projeto);
    }

    /**
     * Autocomplete de projetos. Busca por código numérico parcial ou parte do nome.
     * Limite: 10 resultados para performance.
     */
    public function pesquisarProjetos(Request $request): JsonResponse
    {
        $termo = trim((string) $request->input('q', ''));
        if ($termo === '') {
            return response()->json([]);
        }
        $query = Tabfant::query();
        if (is_numeric($termo)) {
            $query->where('CDPROJETO', 'like', "%{$termo}%");
        } else {
            $query->where('NOMEPROJETO', 'like', "%{$termo}%");
        }
        $projetos = $query->orderBy('NOMEPROJETO')
            ->select(['CDPROJETO', 'NOMEPROJETO'])
            ->limit(10)
            ->get();
        return response()->json($projetos);
    }

    /**
     * Busca projetos associados a um local específico.
     * Retorna os projetos vinculados ao local informado pelo cdlocal.
     */
    public function buscarProjetosPorLocal($cdlocal): JsonResponse
    {
        try {
            // Buscar TODOS os projetos vinculados a este local
            $locaisProjetos = LocalProjeto::with('projeto')
                ->where('cdlocal', $cdlocal)
                ->where('flativo', true)
                ->whereHas('projeto')
                ->get();

            $projetos = [];
            foreach ($locaisProjetos as $localProjeto) {
                if ($localProjeto->projeto) {
                    $projetos[] = [
                        'id' => $localProjeto->id,
                        'CDPROJETO' => $localProjeto->projeto->CDPROJETO,
                        'NOMEPROJETO' => $localProjeto->projeto->NOMEPROJETO,
                        'tabfant_id' => $localProjeto->tabfant_id
                    ];
                }
            }

            // Remover duplicatas por CDPROJETO
            $projetosUnicos = collect($projetos)->unique('CDPROJETO')->values()->all();

            // Se veio um termo de busca (q), filtra pelo código ou nome
            $q = trim((string) request()->query('q', ''));
            if ($q !== '') {
                $projetosUnicos = array_filter($projetosUnicos, function ($projeto) use ($q) {
                    return stripos((string) $projeto['CDPROJETO'], $q) !== false ||
                        stripos((string) $projeto['NOMEPROJETO'], $q) !== false;
                });
                $projetosUnicos = array_values($projetosUnicos);
            }

            return response()->json($projetosUnicos);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao buscar projetos por local', [
                'cdlocal' => $cdlocal,
                'error' => $e->getMessage()
            ]);
            return response()->json([]);
        }
    }

    /**
     * Cria um novo projeto com código único e sequencial.
     */
    public function criarProjeto(Request $request): JsonResponse
    {
        $request->validate([
            'nome' => 'required|string|max:255',
        ], [
            'nome.required' => 'Informe o nome do projeto.',
            'nome.max' => 'Nome muito longo (máximo 255 caracteres).',
        ]);

        try {
            // Gera o próximo código sequencial único
            $maxCodigo = Tabfant::max('CDPROJETO') ?? 0;
            $novoCodigo = (int) $maxCodigo + 1;

            // Cria o projeto
            $projeto = Tabfant::create([
                'CDPROJETO' => $novoCodigo,
                'NOMEPROJETO' => $request->nome,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Se recebeu cdlocal e delocal, cria um novo local vinculado a este projeto
            $cdlocal = $request->input('cdlocal');
            $delocal = $request->input('delocal');

            \Illuminate\Support\Facades\Log::info('Debug criar projeto - dados recebidos', [
                'cdlocal' => $cdlocal,
                'delocal' => $delocal,
                'projeto_id' => $projeto->id,
                'projeto_codigo' => $projeto->CDPROJETO,
                'request_all' => $request->all()
            ]);

            if ($cdlocal && $delocal) {
                $novoLocal = LocalProjeto::create([
                    'cdlocal' => $cdlocal,
                    'delocal' => $delocal,
                    'tabfant_id' => $projeto->id,
                    'flativo' => true,
                ]);

                \Illuminate\Support\Facades\Log::info('Local criado para novo projeto', [
                    'local_id' => $novoLocal->id,
                    'cdlocal' => $cdlocal,
                    'delocal' => $delocal,
                    'projeto_id' => $projeto->id,
                    'projeto_codigo' => $projeto->CDPROJETO,
                    'projeto_nome' => $projeto->NOMEPROJETO
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning('Local NÃO criado - dados insuficientes', [
                    'cdlocal' => $cdlocal,
                    'delocal' => $delocal
                ]);
            }

            Log::info('Projeto criado', [
                'CDPROJETO' => $projeto->CDPROJETO,
                'NOMEPROJETO' => $projeto->NOMEPROJETO,
                'usuario' => Auth::id()
            ]);

            return response()->json([
                'CDPROJETO' => $projeto->CDPROJETO,
                'NOMEPROJETO' => $projeto->NOMEPROJETO,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao criar projeto', [
                'nome' => $request->nome,
                'erro' => $e->getMessage(),
                'usuario' => Auth::id()
            ]);

            return response()->json([
                'error' => 'Erro interno. Tente novamente.'
            ], 500);
        }
    }

    public function getLocaisPorProjeto($cdprojeto): JsonResponse
    {
        $projeto = Tabfant::where('CDPROJETO', $cdprojeto)->first(['id']);
        if (!$projeto) {
            return response()->json([]); // projeto não encontrado => sem locais
        }
        $term = trim(request()->query('q', ''));
        $locais = LocalProjeto::where('tabfant_id', $projeto->id)
            ->when($term !== '', function ($q) use ($term) {
                $q->where('delocal', 'like', "%{$term}%");
            })
            ->orderBy('delocal')
            ->limit(30)
            ->get(['id', 'delocal as LOCAL', 'cdlocal']);
        return response()->json($locais);
    }

    /**
     * Cria um novo local para o projeto (filial) informado.
     */
    public function criarLocal(Request $request, $cdprojeto): JsonResponse
    {
        $request->validate([
            'delocal' => 'required|string|max:120',
        ], [
            'delocal.required' => 'Informe o nome do local.',
        ]);

        $projeto = Tabfant::where('CDPROJETO', $cdprojeto)->first(['id']);
        if (!$projeto) {
            return response()->json(['error' => 'Projeto não encontrado.'], 404);
        }

        // Calcula automaticamente o próximo cdlocal baseado apenas nos locais deste projeto
        $maxCdLocal = LocalProjeto::where('tabfant_id', $projeto->id)
            ->max('cdlocal') ?? 0;

        $nextCdLocal = (int) $maxCdLocal + 1;

        // Log para debug
        Log::info('Criando local para projeto', [
            'cdprojeto' => $cdprojeto,
            'tabfant_id' => $projeto->id,
            'max_cdlocal_atual' => $maxCdLocal,
            'next_cdlocal' => $nextCdLocal,
            'delocal' => $request->delocal
        ]);

        $local = LocalProjeto::create([
            'tabfant_id' => $projeto->id,
            'cdlocal' => $nextCdLocal,
            'delocal' => $request->delocal,
            'flativo' => true,
        ]);

        return response()->json([
            'id' => $local->id,
            'LOCAL' => $local->delocal,
            'cdlocal' => $local->cdlocal,
        ], 201);
    }

    /**
     * Busca locais disponíveis por código ou nome
     */
    public function buscarLocais(Request $request): JsonResponse
    {
        $termo = $request->input('termo', '');

        $query = LocalProjeto::with('projeto')->where('flativo', true);

        if (!empty($termo)) {
            $query->where(function ($q) use ($termo) {
                $q->where('cdlocal', 'LIKE', "%{$termo}%")
                    ->orWhere('delocal', 'LIKE', "%{$termo}%");
            });
        }

        $todosLocais = $query->orderBy('cdlocal')
            ->limit(empty($termo) ? 20 : 50)
            ->get();

        // Agrupar locais por cdlocal + delocal para eliminar duplicatas no dropdown
        $locaisAgrupados = $todosLocais->groupBy(function ($local) {
            return $local->cdlocal . '|' . $local->delocal;
        })->map(function ($grupo) {
            $primeiro = $grupo->first();
            $projetosVinculados = $grupo->filter(function ($local) {
                return $local->projeto !== null;
            })->pluck('projeto')->unique('CDPROJETO');

            return [
                'id' => $primeiro->id,
                'cdlocal' => $primeiro->cdlocal,
                'LOCAL' => $primeiro->delocal,
                'delocal' => $primeiro->delocal,
                'tabfant_id' => $primeiro->tabfant_id,
                'CDPROJETO' => $primeiro->projeto ? $primeiro->projeto->CDPROJETO : null,
                'NOMEPROJETO' => $primeiro->projeto ? $primeiro->projeto->NOMEPROJETO : null,
                'projetos_count' => $projetosVinculados->count(),
                'tem_multiplos_projetos' => $projetosVinculados->count() > 1
            ];
        })->values();

        $locais = $locaisAgrupados;

        Log::info('Busca de locais', [
            'termo' => $termo,
            'resultados' => $locais->count(),
            'primeiros_3' => $locais->take(3)->toArray()
        ]);

        return response()->json($locais);
    }

    /**
     * Cria um novo local informando o projeto por nome ou código
     */
    public function criarLocalComProjeto(Request $request): JsonResponse
    {
        $request->validate([
            'delocal' => 'required|string|max:120',
            'projeto' => 'required|string|max:120',
        ], [
            'delocal.required' => 'Informe o nome do local.',
            'projeto.required' => 'Informe o projeto associado.',
        ]);

        // Busca o projeto por código ou nome
        $projeto = Tabfant::where('CDPROJETO', $request->projeto)
            ->orWhere('NOMEPROJETO', 'LIKE', "%{$request->projeto}%")
            ->first(['id', 'CDPROJETO', 'NOMEPROJETO']);

        if (!$projeto) {
            return response()->json(['error' => 'Projeto não encontrado.'], 404);
        }

        // Calcula automaticamente o próximo cdlocal baseado apenas nos locais deste projeto
        $maxCdLocal = LocalProjeto::where('tabfant_id', $projeto->id)
            ->max('cdlocal') ?? 0;

        $nextCdLocal = (int) $maxCdLocal + 1;

        Log::info('Criando local com projeto informado', [
            'projeto_busca' => $request->projeto,
            'projeto_encontrado' => $projeto->CDPROJETO . ' - ' . $projeto->NOMEPROJETO,
            'tabfant_id' => $projeto->id,
            'max_cdlocal_atual' => $maxCdLocal,
            'next_cdlocal' => $nextCdLocal,
            'delocal' => $request->delocal
        ]);

        $local = LocalProjeto::create([
            'tabfant_id' => $projeto->id,
            'cdlocal' => $nextCdLocal,
            'delocal' => $request->delocal,
            'flativo' => true,
        ]);

        return response()->json([
            'id' => $local->id,
            'LOCAL' => $local->delocal,
            'cdlocal' => $local->cdlocal,
            'projeto' => [
                'CDPROJETO' => $projeto->CDPROJETO,
                'NOMEPROJETO' => $projeto->NOMEPROJETO,
            ]
        ], 201);
    }

    /**
     * Página dedicada para atribuição de códigos de termo
     */
    public function atribuir(Request $request): View
    {
        // Query base para patrimônios
        $query = Patrimonio::query();

        // Segurança: usuários não-ADM só visualizam patrimônios que eles cadastraram
        $user = Auth::user();
        if ($user && ($user->PERFIL ?? null) !== 'ADM') {
            $nmLogin = (string) ($user->NMLOGIN ?? '');
            $nmUser  = (string) ($user->NOMEUSER ?? '');
            $query->where(function ($q) use ($user, $nmLogin, $nmUser) {
                $q->where('CDMATRFUNCIONARIO', $user->CDMATRFUNCIONARIO)
                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', [$nmLogin])
                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', [$nmUser]);
            });
        }

        // Filtro por status - default volta a 'disponivel'
        $status = $request->get('status', 'disponivel');

        if ($status === 'disponivel') {
            // Patrimônios sem código de termo (campo integer => apenas null significa "sem")
            $query->whereNull('NMPLANTA');
        } elseif ($status === 'indisponivel') {
            // Patrimônios com código de termo
            $query->whereNotNull('NMPLANTA');
        }
        // Se status for vazio ou 'todos', não aplica filtro de status

        // Aplicar filtros se fornecidos
        if ($request->filled('filtro_numero')) {
            $query->where('NUPATRIMONIO', 'like', '%' . $request->filtro_numero . '%');
        }

        if ($request->filled('filtro_descricao')) {
            $query->where('DEPATRIMONIO', 'like', '%' . $request->filtro_descricao . '%');
        }

        if ($request->filled('filtro_modelo')) {
            $query->where('MODELO', 'like', '%' . $request->filtro_modelo . '%');
        }

        // Filtro por projeto para atribuição/termo
        if ($request->filled('filtro_projeto')) {
            $query->where('CDPROJETO', $request->filtro_projeto);
        }

        // Ordenação
        $query->orderBy('NUPATRIMONIO', 'asc');

        // Paginação configurável
        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 10) $perPage = 10;
        if ($perPage > 100) $perPage = 100;

        $patrimonios = $query->paginate($perPage);

        return view('patrimonios.atribuir', compact('patrimonios'));
    }

    /**
     * Página isolada (clonada) para atribuição de códigos de termo.
     * Reaproveita a mesma lógica de filtragem da página principal para manter consistência.
     */
    public function atribuirCodigos(Request $request): View
    {
        // Reutilizamos a lógica do método atribuir sem duplicar demasiadamente
        // (poderíamos extrair para método privado, mas mantemos simples para clareza)
        $query = Patrimonio::query();

        // Segurança: usuários não-ADM só visualizam patrimônios que eles cadastraram
        $user = Auth::user();
        if ($user && ($user->PERFIL ?? null) !== 'ADM') {
            $nmLogin = (string) ($user->NMLOGIN ?? '');
            $nmUser  = (string) ($user->NOMEUSER ?? '');
            $query->where(function ($q) use ($user, $nmLogin, $nmUser) {
                $q->where('CDMATRFUNCIONARIO', $user->CDMATRFUNCIONARIO)
                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', [$nmLogin])
                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', [$nmUser]);
            });
        }

        $status = $request->get('status', 'disponivel');
        if ($status === 'disponivel') {
            $query->whereNull('NMPLANTA');
        } elseif ($status === 'indisponivel') {
            $query->whereNotNull('NMPLANTA');
        }

        if ($request->filled('filtro_numero')) {
            $query->where('NUPATRIMONIO', 'like', '%' . $request->filtro_numero . '%');
        }
        if ($request->filled('filtro_descricao')) {
            $query->where('DEPATRIMONIO', 'like', '%' . $request->filtro_descricao . '%');
        }
        if ($request->filled('filtro_modelo')) {
            $query->where('MODELO', 'like', '%' . $request->filtro_modelo . '%');
        }
        if ($request->filled('filtro_projeto')) {
            $query->where('CDPROJETO', $request->filtro_projeto);
        }

        $query->orderBy('NUPATRIMONIO', 'asc');
        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 10) $perPage = 10;
        if ($perPage > 100) $perPage = 100;
        $patrimonios = $query->paginate($perPage);

        // Reutiliza a mesma view principal de atribuição; evita duplicação e problemas de alias
        return view('patrimonios.atribuir', compact('patrimonios'));
    }

    /**
     * Processar a atribuição/desatribuição de códigos de termo
     */
    public function processarAtribuicao(Request $request): RedirectResponse
    {
        // Verificar se é uma operação de desatribuição
        if ($request->filled('desatribuir')) {
            return $this->processarDesatribuicao($request);
        }
        // Validação condicional (caso envie código manualmente ainda funciona, mas não é mais o fluxo principal)
        $rules = [
            'patrimonios' => 'required|array|min:1',
            'patrimonios.*' => 'integer|exists:PATR,NUSEQPATR'
        ];
        if ($request->filled('codigo_termo')) {
            $rules['codigo_termo'] = 'required|integer|min:1';
        }
        $request->validate($rules);

        try {
            $patrimoniosIds = $request->patrimonios;

            // Novo fluxo: se não veio um código explícito, o sistema determina automaticamente.
            if ($request->filled('codigo_termo')) {
                $codigoTermo = (int) $request->codigo_termo;
                $codigoExiste = TermoCodigo::where('codigo', $codigoTermo)->exists() || Patrimonio::where('NMPLANTA', $codigoTermo)->exists();
                if (!$codigoExiste) {
                    // Caso o código tenha sido "gerado" no front mas ainda não registrado, registramos agora
                    TermoCodigo::firstOrCreate([
                        'codigo' => $codigoTermo
                    ], [
                        'created_by' => (Auth::user()->NMLOGIN ?? 'SISTEMA')
                    ]);
                }
            } else {
                // Fluxo inteligente: reutilizar menor código registrado sem uso ou gerar próximo sequencial
                $unusedCodigo = TermoCodigo::whereNotIn('codigo', function ($q) {
                    $q->select('NMPLANTA')->from('patr')->whereNotNull('NMPLANTA');
                })
                    ->orderBy('codigo')
                    ->first();

                if ($unusedCodigo) {
                    $codigoTermo = (int) $unusedCodigo->codigo; // reutiliza código "vago"
                } else {
                    $maxRegistrado = (int) TermoCodigo::max('codigo');
                    $maxUsado = (int) Patrimonio::max('NMPLANTA');
                    $codigoTermo = max($maxRegistrado, $maxUsado) + 1; // próximo sequencial
                    // registra para manter histórico de códigos gerados
                    TermoCodigo::firstOrCreate([
                        'codigo' => $codigoTermo
                    ], [
                        'created_by' => (Auth::user()->NMLOGIN ?? 'SISTEMA')
                    ]);
                }
            }

            // Verificar quais patrimônios já estão atribuídos
            $jaAtribuidos = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)
                ->whereNotNull('NMPLANTA')
                ->count();

            // Atualizar apenas os patrimônios disponíveis
            $updated = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)
                ->whereNull('NMPLANTA')
                ->update(['NMPLANTA' => $codigoTermo]);

            $message = "Código de termo {$codigoTermo} atribuído a {$updated} patrimônio(s) com sucesso!";

            // Histórico de atribuição de termo
            if ($updated > 0) {
                try {
                    $patrimoniosAlterados = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)->get(['NUPATRIMONIO']);
                    foreach ($patrimoniosAlterados as $p) {
                        $coAutor = null;
                        $actorMat = Auth::user()->CDMATRFUNCIONARIO ?? null;
                        // Aqui não temos o dono do patrimônio carregado; buscar rapidamente
                        $ownerMat = Patrimonio::where('NUPATRIMONIO', $p->NUPATRIMONIO)->value('CDMATRFUNCIONARIO');
                        if (!empty($actorMat) && !empty($ownerMat) && $actorMat != $ownerMat) {
                            $coAutor = User::where('CDMATRFUNCIONARIO', $ownerMat)->value('NMLOGIN');
                        }
                        HistoricoMovimentacao::create([
                            'TIPO' => 'termo',
                            'CAMPO' => 'NMPLANTA',
                            'VALOR_ANTIGO' => null,
                            'VALOR_NOVO' => $codigoTermo,
                            'NUPATR' => $p->NUPATRIMONIO,
                            'CODPROJ' => null,
                            'USUARIO' => (Auth::user()->NMLOGIN ?? 'SISTEMA'),
                            'CO_AUTOR' => $coAutor,
                            'DTOPERACAO' => now(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Falha ao gravar histórico atribuição de termo', ['erro' => $e->getMessage()]);
                }
            }

            if ($jaAtribuidos > 0) {
                $message .= " ({$jaAtribuidos} patrimônio(s) já estavam atribuídos e foram ignorados)";
            }

            return redirect()->route('patrimonios.atribuir.codigos', ['status' => 'indisponivel'])
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Erro ao processar atribuição de termo: ' . $e->getMessage());
            return redirect()->route('patrimonios.atribuir.codigos')
                ->with('error', 'Erro ao processar atribuição. Tente novamente.');
        }
    }

    /**
     * Processar desatribuição de códigos de termo
     */
    private function processarDesatribuicao(Request $request): RedirectResponse
    {
        $request->validate([
            'patrimonios' => 'required|array|min:1',
            'patrimonios.*' => 'integer|exists:PATR,NUSEQPATR'
        ]);

        try {
            $patrimoniosIds = $request->patrimonios;

            // Buscar informações antes da desatribuição para feedback
            $patrimonio = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)->first();
            $codigoAnterior = $patrimonio ? $patrimonio->NMPLANTA : 'N/A';

            // Desatribuir (limpar campo NMPLANTA)
            $updated = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)
                ->whereNotNull('NMPLANTA')
                ->update(['NMPLANTA' => null]);

            if ($updated > 0) {
                // Histórico de desatribuição de termo
                try {
                    $patrimoniosAlterados = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)->get(['NUPATRIMONIO']);
                    foreach ($patrimoniosAlterados as $p) {
                        $coAutor = null;
                        $actorMat = Auth::user()->CDMATRFUNCIONARIO ?? null;
                        $ownerMat = Patrimonio::where('NUPATRIMONIO', $p->NUPATRIMONIO)->value('CDMATRFUNCIONARIO');
                        if (!empty($actorMat) && !empty($ownerMat) && $actorMat != $ownerMat) {
                            $coAutor = User::where('CDMATRFUNCIONARIO', $ownerMat)->value('NMLOGIN');
                        }
                        HistoricoMovimentacao::create([
                            'TIPO' => 'termo',
                            'CAMPO' => 'NMPLANTA',
                            'VALOR_ANTIGO' => $codigoAnterior,
                            'VALOR_NOVO' => null,
                            'NUPATR' => $p->NUPATRIMONIO,
                            'CODPROJ' => null,
                            'USUARIO' => (Auth::user()->NMLOGIN ?? 'SISTEMA'),
                            'CO_AUTOR' => $coAutor,
                            'DTOPERACAO' => now(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Falha ao gravar histórico desatribuição de termo', ['erro' => $e->getMessage()]);
                }
                return redirect()->route('patrimonios.atribuir')
                    ->with('success', "Código de termo {$codigoAnterior} removido de {$updated} patrimônio(s) com sucesso!");
            } else {
                return redirect()->route('patrimonios.atribuir')
                    ->with('warning', 'Nenhum patrimônio foi desatribuído. Verifique se os patrimônios selecionados possuem código de termo.');
            }
        } catch (\Exception $e) {
            Log::error('Erro ao processar desatribuição de termo: ' . $e->getMessage());
            return redirect()->route('patrimonios.atribuir')
                ->with('error', 'Erro ao processar desatribuição. Tente novamente.');
        }
    }

    // --- MÉTODOS AUXILIARES ---

    private function getPatrimoniosQuery(Request $request)
    {
        $user = Auth::user();
        $query = Patrimonio::with(['funcionario', 'local', 'creator']);

        if ($user->PERFIL !== 'ADM') {
            // Não-ADM: pode ver o que é responsável OU o que ele mesmo cadastrou (login ou nome), case-insensitive
            $nmLogin = (string) ($user->NMLOGIN ?? '');
            $nmUser  = (string) ($user->NOMEUSER ?? '');
            $query->where(function ($q) use ($user, $nmLogin, $nmUser) {
                $q->where('CDMATRFUNCIONARIO', $user->CDMATRFUNCIONARIO)
                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', [$nmLogin])
                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', [$nmUser]);
            });
        }
        if ($request->filled('nupatrimonio')) {
            $query->where('NUPATRIMONIO', $request->nupatrimonio);
        }
        if ($request->filled('cdprojeto')) {
            $query->where('CDPROJETO', $request->cdprojeto);
        }
        if ($request->filled('descricao')) {
            $query->where('DEPATRIMONIO', 'like', '%' . $request->descricao . '%');
        }
        if ($request->filled('situacao')) {
            $query->where('SITUACAO', 'like', '%' . $request->situacao . '%');
        }
        if ($request->filled('modelo')) {
            $query->where('MODELO', 'like', '%' . $request->modelo . '%');
        }
        if ($request->filled('nmplanta')) {
            $query->where('NMPLANTA', $request->nmplanta);
        }
        if ($request->filled('cadastrado_por') && $user->PERFIL === 'ADM') {
            if ($request->cadastrado_por === 'SISTEMA') {
                // Registros importados / sem vínculo direto de usuário
                $query->whereNull('CDMATRFUNCIONARIO');
            } else {
                $query->where('CDMATRFUNCIONARIO', $request->cadastrado_por);
            }
        }

        $sortableColumns = ['NUPATRIMONIO', 'MODELO', 'DEPATRIMONIO', 'SITUACAO'];
        $sortColumn = $request->input('sort', 'NUSEQPATR');
        $sortDirection = $request->input('direction', 'desc');
        if (in_array($sortColumn, $sortableColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->latest('NUSEQPATR');
        }
        return $query;
    }

    /**
     * API endpoint dedicado para modal de atribuir termo
     * Retorna apenas JSON sem afetar URL principal
     */
    public function getPatrimoniosDisponiveis(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int) $request->input('page', 1));
            $perPage = 30; // Fixo para modal

            // Query para patrimônios disponíveis (sem termo atribuído ou conforme regra de negócio)
            $query = Patrimonio::with(['funcionario'])
                ->whereNull('NMPLANTA') // Sem código de termo
                ->orWhere('NMPLANTA', '') // Ou código vazio
                ->orderBy('NUPATRIMONIO', 'asc');

            // Paginar manualmente
            $total = $query->count();
            $patrimonios = $query->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            return response()->json([
                'data' => $patrimonios->map(function ($p) {
                    return [
                        'NUSEQPATR' => $p->NUSEQPATR,
                        'NUPATRIMONIO' => $p->NUPATRIMONIO,
                        'DEPATRIMONIO' => $p->DEPATRIMONIO,
                        'NMPLANTA' => $p->NMPLANTA,
                        'MODELO' => $p->MODELO,
                    ];
                }),
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao carregar dados'], 500);
        }
    }

    private function validatePatrimonio(Request $request): array
    {
        // 1) Validar campos básicos; aceitar tanto o fluxo novo (NUSEQOBJ/DEOBJETO)
        // quanto o legado (CODOBJETO/DEPATRIMONIO)
        $data = $request->validate([
            'NUPATRIMONIO' => 'required|integer',
            'NUMOF' => 'nullable|integer',
            // Fluxo novo de código
            'NUSEQOBJ' => 'nullable|integer',
            'DEOBJETO' => 'nullable|string|max:350',
            // Fluxo legado (fallback)
            'CODOBJETO' => 'nullable|integer',
            'DEPATRIMONIO' => 'nullable|string|max:350',
            'DEHISTORICO' => 'nullable|string|max:300',
            'CDPROJETO' => 'nullable|integer',
            'CDLOCAL' => 'nullable|integer',
            'CDLOCALINTERNO' => 'nullable|integer',
            'NMPLANTA' => 'nullable|integer',
            'MARCA' => 'nullable|string|max:30',
            'MODELO' => 'nullable|string|max:30',
            'SITUACAO' => 'required|string|in:EM USO,CONSERTO,BAIXA,À DISPOSIÇÃO',
            'DTAQUISICAO' => 'nullable|date',
            'DTBAIXA' => 'required_if:SITUACAO,BAIXA|nullable|date',
            // Matricula precisa existir na tabela funcionarios
            'CDMATRFUNCIONARIO' => 'required|integer|exists:funcionarios,CDMATRFUNCIONARIO',
        ]);

        // 2) Resolver o código do objeto a partir de NUSEQOBJ (preferencial) ou CODOBJETO (fallback)
        $codigoInput = $request->input('NUSEQOBJ', $request->input('CODOBJETO'));
        if ($codigoInput === null || $codigoInput === '') {
            throw ValidationException::withMessages([
                'NUSEQOBJ' => 'Informe o código do objeto.'
            ]);
        }
        if (!is_numeric($codigoInput)) {
            throw ValidationException::withMessages([
                'NUSEQOBJ' => 'O código do objeto deve ser numérico.'
            ]);
        }
        $codigo = (int) $codigoInput;

        // 3) Garantir existência do registro em OBJETOPATR
        $objeto = ObjetoPatr::find($codigo);
        if (!$objeto) {
            $descricao = trim((string) $request->input('DEOBJETO', ''));
            if ($descricao === '') {
                throw ValidationException::withMessages([
                    'DEOBJETO' => 'Informe a descrição do novo código.'
                ]);
            }
            $objeto = ObjetoPatr::create([
                'NUSEQOBJETO' => $codigo,
                'DEOBJETO' => $descricao,
            ]);
        }

        // 4) Mapear para os campos reais da tabela PATR
        $data['CODOBJETO'] = $codigo;
        $data['DEPATRIMONIO'] = $objeto->DEOBJETO; // mantém compatibilidade de exibição no index/relatórios
        unset($data['NUSEQOBJ'], $data['DEOBJETO']);

        return $data;
    }

    /* === Rotas solicitadas para geração e atribuição direta de códigos (fluxo simplificado) === */
    public function gerarCodigo(Request $request, CodigoService $service): JsonResponse
    {
        try {
            [$code, $reused] = $service->gerarOuReaproveitar();
            return response()->json(['code' => $code, 'reused' => $reused]);
        } catch (\Throwable $e) {
            Log::error('Falha gerarCodigo', ['erro' => $e->getMessage()]);
            return response()->json(['message' => 'Erro ao gerar código'], 500);
        }
    }

    public function atribuirCodigo(Request $request, CodigoService $service): JsonResponse
    {
        // Aceita código numérico vindo como number ou string
        $request->validate([
            'code' => 'required', // pode vir number no JSON, então não restringimos a string
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer'
        ]);
        try {
            $codigo = (int) $request->input('code');
            if ($codigo <= 0) {
                return response()->json(['message' => 'Código inválido'], 422);
            }
            $resultado = $service->atribuirCodigo($codigo, $request->ids);
            if ($resultado['already_used']) {
                return response()->json(['message' => 'Código já utilizado'], 422);
            }
            return response()->json([
                'code' => $resultado['code'],
                'updated_ids' => $resultado['updated'],
                'message' => 'Atribuído.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha atribuirCodigo', ['erro' => $e->getMessage()]);
            return response()->json(['message' => 'Erro ao atribuir código'], 500);
        }
    }

    /**
     * Desatribui (remove) o código de termo de uma lista de patrimônios (API JSON usada na página de atribuição)
     */
    public function desatribuirCodigo(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer'
        ]);
        try {
            $ids = $request->input('ids', []);
            // Seleciona patrimônios que realmente têm código para evitar updates desnecessários
            $patrimonios = Patrimonio::whereIn('NUSEQPATR', $ids)->whereNotNull('NMPLANTA')->get(['NUSEQPATR', 'NUPATRIMONIO', 'NMPLANTA', 'CDMATRFUNCIONARIO']);
            if ($patrimonios->isEmpty()) {
                return response()->json(['message' => 'Nenhum patrimônio elegível para desatribuir', 'updated_ids' => []], 200);
            }
            $idsParaUpdate = $patrimonios->pluck('NUSEQPATR')->all();
            Patrimonio::whereIn('NUSEQPATR', $idsParaUpdate)->update(['NMPLANTA' => null]);

            // Histórico
            foreach ($patrimonios as $p) {
                try {
                    $coAutor = null;
                    $actorMat = Auth::user()->CDMATRFUNCIONARIO ?? null;
                    $ownerMat = $p->CDMATRFUNCIONARIO;
                    if (!empty($actorMat) && !empty($ownerMat) && $actorMat != $ownerMat) {
                        $coAutor = User::where('CDMATRFUNCIONARIO', $ownerMat)->value('NMLOGIN');
                    }
                    HistoricoMovimentacao::create([
                        'TIPO' => 'termo',
                        'CAMPO' => 'NMPLANTA',
                        'VALOR_ANTIGO' => $p->NMPLANTA,
                        'VALOR_NOVO' => null,
                        'NUPATR' => $p->NUPATRIMONIO,
                        'CODPROJ' => null,
                        'USUARIO' => (Auth::user()->NMLOGIN ?? 'SISTEMA'),
                        'CO_AUTOR' => $coAutor,
                        'DTOPERACAO' => now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Falha histórico desatribuirCodigo', ['id' => $p->NUSEQPATR, 'erro' => $e->getMessage()]);
                }
            }

            return response()->json([
                'message' => 'Desatribuição concluída',
                'updated_ids' => $idsParaUpdate,
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha desatribuirCodigo', ['erro' => $e->getMessage()]);
            return response()->json(['message' => 'Erro ao desatribuir código'], 500);
        }
    }

    private function parseIds(Request $request): array
    {
        if ($request->has('ids') && is_string($request->input('ids'))) {
            return collect(explode(',', (string) $request->input('ids')))->map('trim')->filter()->values()->all();
        }
        $arr = $request->input('ids', []);
        return is_array($arr) ? array_values(array_filter($arr)) : [];
    }

    /**
     * Cria um novo local com projeto opcional.
     */
    public function criarNovoLocal(Request $request): JsonResponse
    {
        $request->validate([
            'cdlocal' => 'required|string|max:10',
            'delocal' => 'required|string|max:255',
            'projeto' => 'nullable|string|max:255',
        ], [
            'cdlocal.required' => 'Código do local é obrigatório.',
            'delocal.required' => 'Nome do local é obrigatório.',
        ]);

        try {
            $cdlocal = $request->input('cdlocal');
            $delocal = $request->input('delocal');
            $nomeProjeto = $request->input('projeto');

            // Verificar se já existe local com esse código
            $localExistente = LocalProjeto::where('cdlocal', $cdlocal)->first();
            if ($localExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe um local com este código.'
                ]);
            }

            $projeto = null;
            $tabfantId = null;

            // Se foi especificado um projeto, criar ou encontrar
            if ($nomeProjeto) {
                // Primeiro tentar encontrar projeto existente pelo nome
                $projeto = Tabfant::where('NOMEPROJETO', 'LIKE', "%{$nomeProjeto}%")->first();

                if (!$projeto) {
                    // Criar novo projeto
                    $maxCodigo = Tabfant::max('CDPROJETO') ?? 0;
                    $novoCodigo = $maxCodigo + 1;

                    $projeto = Tabfant::create([
                        'CDPROJETO' => $novoCodigo,
                        'NOMEPROJETO' => $nomeProjeto,
                        'flativo' => true,
                    ]);
                }

                $tabfantId = $projeto->id;
            }

            // Criar o local
            $local = LocalProjeto::create([
                'cdlocal' => $cdlocal,
                'delocal' => $delocal,
                'tabfant_id' => $tabfantId,
                'flativo' => true,
            ]);

            return response()->json([
                'success' => true,
                'local' => [
                    'cdlocal' => $local->cdlocal,
                    'delocal' => $local->delocal,
                ],
                'projeto' => $projeto ? [
                    'CDPROJETO' => $projeto->CDPROJETO,
                    'NOMEPROJETO' => $projeto->NOMEPROJETO
                ] : null
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao criar local:', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Cria um novo projeto associado com local opcional.
     */
    public function criarProjetoAssociado(Request $request): JsonResponse
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'local' => 'nullable|string|max:255',
        ], [
            'nome.required' => 'Nome do projeto é obrigatório.',
        ]);

        try {
            $nomeProjeto = $request->input('nome');
            $localInfo = $request->input('local');

            // Criar novo projeto
            $maxCodigo = Tabfant::max('CDPROJETO') ?? 0;
            $novoCodigo = $maxCodigo + 1;

            $projeto = Tabfant::create([
                'CDPROJETO' => $novoCodigo,
                'NOMEPROJETO' => $nomeProjeto,
                'flativo' => true,
            ]);

            $local = null;

            // Se foi especificado um local, processar
            if ($localInfo) {
                // Tentar extrair código e nome do formato "123 - Nome do Local"
                if (preg_match('/^(\d+)\s*-\s*(.+)$/', $localInfo, $matches)) {
                    $cdlocal = $matches[1];
                    $delocal = $matches[2];

                    // Verificar se o local já existe
                    $localExistente = LocalProjeto::where('cdlocal', $cdlocal)->first();

                    if ($localExistente) {
                        // Criar nova associação local-projeto (permitir múltiplos projetos por local)
                        $local = LocalProjeto::create([
                            'cdlocal' => $cdlocal,
                            'delocal' => $delocal,
                            'tabfant_id' => $projeto->id,
                            'flativo' => true,
                        ]);
                    } else {
                        // Criar novo local associado ao projeto
                        $local = LocalProjeto::create([
                            'cdlocal' => $cdlocal,
                            'delocal' => $delocal,
                            'tabfant_id' => $projeto->id,
                            'flativo' => true,
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'projeto' => [
                    'CDPROJETO' => $projeto->CDPROJETO,
                    'NOMEPROJETO' => $projeto->NOMEPROJETO
                ],
                'local' => $local ? [
                    'cdlocal' => $local->cdlocal,
                    'delocal' => $local->delocal,
                ] : null
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao criar projeto associado:', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Cria local e/ou projeto baseado nos dados do formulário de patrimônio.
     */
    public function criarLocalProjeto(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nomeLocal' => 'nullable|string|max:255',
                'nomeProjeto' => 'nullable|string|max:255',
                'cdlocal' => 'required|max:20',
                'nomeLocalAtual' => 'nullable|string|max:255',
                'projetoAtual' => 'nullable|max:20'
            ], [
                'cdlocal.required' => 'Código do local é obrigatório',
                'nomeLocal.max' => 'Nome do local muito longo (máximo 255 caracteres)',
                'nomeProjeto.max' => 'Nome do projeto muito longo (máximo 255 caracteres)',
            ]);

            $nomeLocal = $validated['nomeLocal'];
            $nomeProjeto = $validated['nomeProjeto'];
            $cdlocal = $validated['cdlocal'];
            $nomeLocalAtual = $validated['nomeLocalAtual'];

            \Illuminate\Support\Facades\Log::info('criarLocalProjeto - Dados recebidos:', [
                'nomeLocal' => $nomeLocal,
                'nomeProjeto' => $nomeProjeto,
                'cdlocal' => $cdlocal,
                'nomeLocalAtual' => $nomeLocalAtual
            ]);

            if (!$nomeLocal && !$nomeProjeto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Preencha pelo menos um campo (Nome do Local ou Projeto Associado)'
                ], 422);
            }

            DB::beginTransaction();

            $local = null;
            $projeto = null;

            // Se foi fornecido nome do projeto, criar projeto
            if ($nomeProjeto) {
                // Criar novo projeto sempre (não buscar existente)
                $maxCodigo = Tabfant::max('CDPROJETO') ?? 0;
                $novoCodigo = $maxCodigo + 1;

                $projeto = Tabfant::create([
                    'CDPROJETO' => $novoCodigo,
                    'NOMEPROJETO' => $nomeProjeto,
                    'LOCAL' => null,  // Campo LOCAL na tabela tabfant
                ]);

                \Illuminate\Support\Facades\Log::info('Projeto criado:', [
                    'CDPROJETO' => $projeto->CDPROJETO,
                    'NOMEPROJETO' => $projeto->NOMEPROJETO
                ]);
            }

            // Se foi fornecido nome do local, criar/atualizar local
            if ($nomeLocal) {
                // Verificar se já existe um local com este código (sem se preocupar com tabfant_id)
                $localExistente = LocalProjeto::where('cdlocal', $cdlocal)
                    ->orderByDesc('id')
                    ->first();

                if ($localExistente) {
                    // Atualizar nome do local existente
                    $localExistente->update([
                        'delocal' => $nomeLocal,
                    ]);
                    $local = $localExistente;

                    \Illuminate\Support\Facades\Log::info('Local atualizado:', [
                        'cdlocal' => $local->cdlocal,
                        'delocal' => $local->delocal
                    ]);
                } else {
                    // Criar novo local com código fornecido
                    $local = LocalProjeto::create([
                        'cdlocal' => $cdlocal,
                        'delocal' => $nomeLocal,
                        'tabfant_id' => null, // Será associado depois se houver projeto
                        'flativo' => true,
                    ]);

                    \Illuminate\Support\Facades\Log::info('Local criado:', [
                        'cdlocal' => $local->cdlocal,
                        'delocal' => $local->delocal
                    ]);
                }
            }

            // Se foi criado um projeto, SEMPRE criar uma nova entrada na tabela locais_projeto para a associação
            if ($projeto) {
                // Pegar o nome do local - prioridade: nomeLocal > nomeLocalAtual > "Local {cdlocal}"
                $nomeLocalParaAssociacao = $nomeLocal ?: ($nomeLocalAtual ?: "Local {$cdlocal}");

                // Criar nova entrada na tabela locais_projeto vinculando o projeto ao local
                $novaAssociacao = LocalProjeto::create([
                    'cdlocal' => $cdlocal,
                    'delocal' => $nomeLocalParaAssociacao,
                    'tabfant_id' => $projeto->id,
                    'flativo' => true,
                ]);

                \Illuminate\Support\Facades\Log::info('Nova associação local-projeto criada:', [
                    'id' => $novaAssociacao->id,
                    'cdlocal' => $novaAssociacao->cdlocal,
                    'delocal' => $novaAssociacao->delocal,
                    'tabfant_id' => $novaAssociacao->tabfant_id,
                    'projeto_codigo' => $projeto->CDPROJETO,
                    'projeto_nome' => $projeto->NOMEPROJETO
                ]);

                // Se não foi criado/atualizado um local específico, usar a nova associação como referência
                if (!$local) {
                    $local = $novaAssociacao;
                }
            }

            DB::commit();

            \Illuminate\Support\Facades\Log::info('Criação finalizada com sucesso:', [
                'local_criado' => $local ? true : false,
                'projeto_criado' => $projeto ? true : false
            ]);

            return response()->json([
                'success' => true,
                'local' => $local ? [
                    'cdlocal' => $local->cdlocal,
                    'delocal' => $local->delocal,
                ] : null,
                'projeto' => $projeto ? [
                    'CDPROJETO' => $projeto->CDPROJETO,
                    'NOMEPROJETO' => $projeto->NOMEPROJETO
                ] : null
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            \Illuminate\Support\Facades\Log::error('Erro ao criar local/projeto:', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor: ' . $e->getMessage()
            ], 500);
        }
    }
}
