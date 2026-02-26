<?php



namespace App\Http\Controllers;



use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema;

use App\Models\Patrimonio;

use App\Models\User;

use App\Models\ObjetoPatr;

use App\Models\Objpatr;

use App\Models\Tabfant;

use App\Models\LocalProjeto;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Response;

use Illuminate\Http\Request;

use Illuminate\Http\JsonResponse;

use Illuminate\Support\Facades\Auth;

use Illuminate\View\View;

use App\Models\HistoricoMovimentacao;

use App\Models\TermoCodigo;

use App\Services\CodigoService;

use App\Services\PatrimonioService;

use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Cache;

use Carbon\Carbon;



class PatrimonioController extends Controller

{

    protected PatrimonioService $patrimonioService;



    public function __construct(PatrimonioService $patrimonioService)

    {

        $this->patrimonioService = $patrimonioService;

    }



    public function buscarCodigoObjeto($codigo)

    {

        try {

            $codigo = trim($codigo);

            // Detectar nome da coluna PK

            $pkColumn = $this->detectarPKObjetoPatr();

            // Usa a tabela principal de códigos (objetopatr)

            $registro = ObjetoPatr::where($pkColumn, $codigo)->first();

            if (!$registro) {

                return response()->json(['found' => false, 'message' => 'Código não encontrado.'], 200);

            }

            return response()->json([

                'found'     => true,

                'descricao' => $registro->DEOBJETO,

                'tipo'      => $registro->NUSEQTIPOPATR,

            ]);

        } catch (\Throwable $e) {

            Log::error('Erro buscarCodigoObjeto: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Evita erro 500 no front: retorna 404 genérico quando houver exceção não crítica

            return response()->json(['found' => false, 'message' => 'Código não encontrado.'], 200);

        }



        // Aplicar filtros do formulário (Nº Patrimonio, Projeto, Descrição, Situação, Modelo, Cód. Termo, Responsável)

        if ($request->filled('nupatrimonio')) {

            $val = trim((string)$request->input('nupatrimonio'));

            if ($val !== '') {

                // aceitar busca exata por número (garantir inteiro quando for numérico)

                if (is_numeric($val)) {

                    $intVal = (int) $val;

                    Log::info('[Filtro] nupatrimonio aplicado (int)', ['val' => $intVal]);

                    $query->where('NUPATRIMONIO', $intVal);

                } else {

                    // se o usuário digitou algo que não é número, usar LIKE por segurança

                    Log::info('[Filtro] nupatrimonio aplicado (like)', ['val' => $val]);

                    $query->whereRaw('LOWER(NUPATRIMONIO) LIKE ?', ['%' . mb_strtolower($val) . '%']);

                }

            }

        }



        if ($request->filled('cdprojeto')) {

            $val = trim((string)$request->input('cdprojeto'));

            if ($val !== '') {

                // alguns registros guardam CDPROJETO no próprio patr, outros via relação local

                $query->where(function($q) use ($val) {

                    $q->where('CDPROJETO', $val)

                      ->orWhereHas('local.projeto', function($q2) use ($val) {

                          $q2->where('CDPROJETO', $val);

                      });

                });

            }

        }



        if ($request->filled('descricao')) {

            $val = trim((string)$request->input('descricao'));

            if ($val !== '') {

                $like = '%' . mb_strtolower($val) . '%';

                $query->whereRaw('LOWER(DEPATRIMONIO) LIKE ?', [$like]);

            }

        }



        if ($request->filled('situacao')) {

            $val = trim((string)$request->input('situacao'));

            if ($val !== '') {

                $query->where('SITUACAO', $val);

            }

        }



        if ($request->filled('modelo')) {

            $val = trim((string)$request->input('modelo'));

            if ($val !== '') {

                $query->whereRaw('LOWER(MODELO) LIKE ?', ['%' . mb_strtolower($val) . '%']);

            }

        }



        if ($request->filled('nmplanta')) {

            $val = trim((string)$request->input('nmplanta'));

            if ($val !== '') {

                $query->where('NMPLANTA', $val);

            }

        }



        if ($request->filled('matr_responsavel')) {

            $val = trim((string)$request->input('matr_responsavel'));

            if ($val !== '') {

                if (is_numeric($val)) {

                    $query->where('CDMATRFUNCIONARIO', $val);

                } else {

                    // procurar usuário por login ou nome e usar matrícula

                    $usuarioFiltro = User::where('NMLOGIN', $val)->orWhereRaw('LOWER(NOMEUSER) LIKE ?', ['%' . mb_strtolower($val) . '%'])->first();

                    if ($usuarioFiltro) {

                        $query->where('CDMATRFUNCIONARIO', $usuarioFiltro->CDMATRFUNCIONARIO);

                    } else {

                        // fallback: pesquisar por trecho no NOME do funcionário via relação 'funcionario' se existir

                        $query->whereHas('funcionario', function($q) use ($val) {

                            $q->whereRaw('LOWER(NOMEFUNCIONARIO) LIKE ?', ['%' . mb_strtolower($val) . '%']);

                        });

                    }

                }

            }

        }

    }



    // Autocomplete de códigos de objeto (CODOBJETO)

    public function pesquisarCodigos(Request $request): JsonResponse

    {

        try {

            $termo = trim((string) $request->input('q', ''));



            // Detectar nome da coluna PK (NUSEQOBJ local vs NUSEQOBJETO servidor)

            $pkColumn = $this->detectarPKObjetoPatr();



            // Buscar todos os códigos

            $codigos = ObjetoPatr::select([$pkColumn . ' as CODOBJETO', 'DEOBJETO as DESCRICAO'])

                ->get()

                ->toArray();



            // Aplicar filtro inteligente

            $filtrados = \App\Services\FilterService::filtrar(

                $codigos,

                $termo,

                ['CODOBJETO', 'DESCRICAO'],  // campos de busca

                ['CODOBJETO' => 'número', 'DESCRICAO' => 'texto'],  // tipos de campo

                10  // limite

            );



            return response()->json($filtrados);

        } catch (\Throwable $e) {

            Log::error('Erro pesquisarCodigos: ' . $e->getMessage());

            return response()->json([], 200);

        }

    }



    /**

     * Detectar nome da coluna PK de ObjetoPatr (compatível com case-sensitive)

     */

    private function detectarPKObjetoPatr(): string

    {

        try {

            // Primeiro tenta maiúsculo, depois minúsculo (compatibilidade Linux/Windows)

            $tableName = Schema::hasTable('OBJETOPATR') ? 'OBJETOPATR' : 'objetopatr';

            

            $result = DB::selectOne("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 

                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_KEY = 'PRI'",

                [DB::getDatabaseName(), $tableName]);

            return $result ? $result->COLUMN_NAME : 'NUSEQOBJETO';

        } catch (\Exception $e) {

            return 'NUSEQOBJETO';

        }

    }



    /**

     * Gera o próximo número sequencial de Patrimonio

     */

    public function proximoNumeroPatrimonio(): JsonResponse

    {

        try {

            $ultimoNumero = Patrimonio::max('NUPATRIMONIO') ?? 0;

            $proximoNumero = $ultimoNumero + 1;



            Log::info('Próximo número de Patrimonio gerado', [

                'ultimo' => $ultimoNumero,

                'proximo' => $proximoNumero

            ]);



            return response()->json([

                'success' => true,

                'numero' => $proximoNumero

            ]);

        } catch (\Throwable $e) {

            Log::error('Erro ao gerar próximo número de Patrimonio: ' . $e->getMessage());

            return response()->json([

                'success' => false,

                'message' => 'Erro ao gerar número de Patrimonio'

            ], 500);

        }

    }



    

    public function index(Request $request): View

    {

        Log::info('[INDEX] Iniciado', ['user' => Auth::user()->NMLOGIN ?? null]);



        /** @var User $currentUser */

        $currentUser = Auth::user();

        $brunoSkipDefaultActive = false;



        // Filtro padrão para o usuário BRUNO: limitar aos cadastradores Bea e Tiago

        if ($currentUser && strcasecmp((string) ($currentUser->NMLOGIN ?? ''), 'bruno') === 0) {

            $cacheKey = 'bruno_skip_default_until_' . ($currentUser->NMLOGIN ?? 'bruno');

            $skipUntil = \Illuminate\Support\Facades\Cache::get($cacheKey);

            $now = \Carbon\Carbon::now();

            $skipActive = $skipUntil ? $now->lt(\Carbon\Carbon::parse($skipUntil)) : false;



            if ($request->boolean('bruno_skip_default')) {

                $next8am = $now->copy()->addDay()->setTime(8, 0, 0);

                \Illuminate\Support\Facades\Cache::put($cacheKey, $next8am, $next8am);

                $skipActive = true;

            }



            $brunoSkipDefaultActive = $skipActive;



            $hasMulti = $request->filled('cadastrados_por');

            $hasSingle = $request->filled('cadastrado_por');

            // ✅ Removida restrição automática de filtro para BEATRIZ.SC e TIAGOP

            // Ambos podem ver todos os registros normalmente

        }



        $perPage = (int) $request->input('per_page', 30);

        $lista = $this->patrimonioService->listarParaIndex($request, $currentUser, $perPage);



        $patrimonios = $lista['patrimonios'];

        $visibleColumns = $lista['visibleColumns'];

        $hiddenColumns = $lista['hiddenColumns'];

        $showEmpty = $lista['showEmptyColumns'];



        $cadastradores = $this->patrimonioService->listarCadastradoresParaFiltro($currentUser);



        // Locais: filtrar pelo projeto selecionado (se houver) para não trazer lista inteira

        $locais = collect();

        if ($request->filled('cdprojeto')) {

            $proj = Tabfant::where('CDPROJETO', trim((string) $request->input('cdprojeto')))->first(['id']);

            if ($proj) {

                $locais = \App\Models\LocalProjeto::where('tabfant_id', $proj->id)

                    ->select('cdlocal as codigo', 'delocal as descricao')

                    ->orderBy('codigo')

                    ->orderBy('descricao')

                    ->get();

            }

        }



        $projetos = Tabfant::select('CDPROJETO as codigo', 'NOMEPROJETO as descricao')

            ->distinct()

            ->orderBy('codigo')

            ->orderBy('descricao')

            ->get();



        $modelos = Patrimonio::select('MODELO')->whereNotNull('MODELO')->distinct()->orderBy('MODELO')->get();

        $marcas = Patrimonio::select('MARCA')->whereNotNull('MARCA')->distinct()->orderBy('MARCA')->get();

        $descricoes = collect();



        $patrimoniosDisponiveis = $this->patrimonioService->listarDisponiveisParaTermo($request, $perPage);



        return view('patrimonios.index', [

            'patrimonios' => $patrimonios,

            'cadastradores' => $cadastradores,

            'locais' => $locais,

            'projetos' => $projetos,

            'modelos' => $modelos,

            'marcas' => $marcas,

            'descricoes' => $descricoes,

            'patrimoniosDisponiveis' => $patrimoniosDisponiveis,

            'filters' => $request->only(['descricao', 'situacao', 'modelo', 'cadastrado_por']),

            'sort' => ['column' => $request->input('sort', 'DTAQUISICAO'), 'direction' => $request->input('direction', 'asc')],

            'visibleColumns' => $visibleColumns ?? [],

            'hiddenColumns' => $hiddenColumns ?? [],

            'showEmptyColumns' => $showEmpty,

            'currentUser' => $currentUser,

            'brunoSkipDefault' => $brunoSkipDefaultActive,

        ]);

    }

    public function ajaxFilter(Request $request): View
    {
        return $this->index($request);
    }



    /**

     * Navigator beta com layout lateral novo e listagem de patrimonios.

     */

    public function navigatorBeta(Request $request): View

    {

        /** @var \App\Models\User|null $currentUser */

        $currentUser = Auth::user();

        if (!($currentUser && $currentUser->isAdmin())) {

            abort(403, 'Acesso restrito ao beta.');

        }



        $perPage = (int) $request->input('per_page', 10);

        $lista = $this->patrimonioService->listarParaIndex($request, $currentUser, $perPage);



        return view('menu.navigator-beta', [

            'patrimonios' => $lista['patrimonios'],

            'visibleColumns' => $lista['visibleColumns'] ?? [],

            'hiddenColumns' => $lista['hiddenColumns'] ?? [],

            'showEmptyColumns' => $lista['showEmptyColumns'] ?? false,

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

    public function create(Request $request): View

    {

        $this->authorize('create', Patrimonio::class);



        // TODO: Substitua estes arrays pelas suas consultas reais ao banco de dados.

        $projetos = Tabfant::select('CDPROJETO', 'NOMEPROJETO')->distinct()->orderBy('NOMEPROJETO')->get();



        $isModal = $request->boolean('modal');
        $view = $isModal ? 'patrimonios.partials.form-create' : 'patrimonios.create';

        return view($view, array_merge(compact('projetos'), ['isModal' => $isModal]));

    }



    /**

     * Salva o novo Patrimonio no banco de dados.

     * Regras:

     * - Se NUSEQOBJ (código) não existir em objetopatr, cria um novo registro com DEOBJETO.

     * - Em seguida, cria o Patrimonio referenciando esse código.

     */

    public function store(Request $request)

    {

        $this->authorize('create', Patrimonio::class);



        // DEBUG: Ver o que foi recebido

        Log::info("🚀 [STORE] Dados recebidos no formulário", [

            "SITUACAO" => $request->input("SITUACAO"),

            "PESO" => $request->input("PESO"),

            "TAMANHO" => $request->input("TAMANHO"),

            "all_inputs" => $request->all(),

        ]);

        $isModal = $request->boolean('modal');
        $validated = [];
        $localSelecionado = null;

        try {
            // 1) Validar os campos conforme o formulário (nomes em MAIÚSCULO)

            $validated = $request->validate([

                // O Nº Patrimonio pode se repetir entre tipos; removido UNIQUE

                'NUPATRIMONIO' => 'required|integer',

                'NUSEQOBJ' => 'nullable|integer',

                'FLCONFERIDO' => 'nullable|string|in:S,N,1,0',

                'DEOBJETO' => 'nullable|string|max:350', // obrigatória apenas quando código for novo

                'SITUACAO' => 'required|string|in:EM USO,CONSERTO,BAIXA,A DISPOSICAO,À DISPOSIÇÃO,A DISPOSIÇÃO,DISPONIVEL',

                'CDMATRFUNCIONARIO' => 'nullable|integer|exists:funcionarios,CDMATRFUNCIONARIO',

                'NUMOF' => 'nullable|integer',

                'DEHISTORICO' => 'nullable|string|max:300',

                'CDPROJETO' => 'nullable|integer',

                // O Local deve ser o código numérico (cdlocal) do LocalProjeto dentro do projeto

                'CDLOCAL' => 'nullable|integer',

                'NMPLANTA' => 'nullable|integer',

                'MARCA' => 'nullable|string|max:30',

                'MODELO' => 'nullable|string|max:30',

                'DTAQUISICAO' => 'nullable|date',

                'DTBAIXA' => 'nullable|date',

                'PESO' => 'nullable|numeric|min:0',

                'TAMANHO' => 'nullable|string|max:100',

            ]);



            // Regra especial para almoxarifado central (999915) e em transito (2002)

            $this->enforceAlmoxRulesOnCreate($validated['CDLOCAL'] ?? null);

            //  VALIDACAO CRITICA: Local deve pertencer ao projeto selecionado

            $localSelecionado = $this->validateLocalBelongsToProjeto(
                $validated['CDPROJETO'] ?? null,
                $validated['CDLOCAL'] ?? null,
                'criacao de patrimonio'
            );

            // Garantir que vamos persistir sempre o código do local (cdlocal) e o projeto correto do local escolhido

            if ($localSelecionado) {

                $validated['CDLOCAL'] = (int) $localSelecionado->cdlocal;

                if ($localSelecionado->projeto) {

                    $validated['CDPROJETO'] = (int) $localSelecionado->projeto->CDPROJETO;

                }

            }



            //     VERIFICAR DUPLICATAS: Impedir criar Patrimonio com N° que já existe

            $nupatrimonio = (int) $validated['NUPATRIMONIO'];

            $jaExiste = Patrimonio::where('NUPATRIMONIO', $nupatrimonio)->exists();

            if ($jaExiste) {

                throw ValidationException::withMessages([

                    'NUPATRIMONIO' => "Já existe um Patrimonio com o número $nupatrimonio! não é permitido criar duplicatas."

                ]);

            }



            // 2) Garantir existência do ObjetoPatr (tabela objetopatr)

            //    O Model ObjetoPatr usa PK 'NUSEQOBJ'.
            //    ✅ SUPORTE NULL: Permite patrimonios sem objeto definido

            $codigoInput = $validated['NUSEQOBJ'] ?? null;
            $codigo = $codigoInput !== null ? (int) $codigoInput : null;
            $objeto = null;

            if ($codigo !== null) {
                $objeto = ObjetoPatr::find($codigo);

                if (!$objeto) {
                    // Se for novo código, exigir DEOBJETO

                    $request->validate([
                        'DEOBJETO' => 'required|string|max:350',
                    ], [
                        'DEOBJETO.required' => 'Informe a descrição do novo código.',
                    ]);

                    $objeto = ObjetoPatr::create([
                        'NUSEQOBJ' => $codigo,
                        // NUSEQTIPOPATR pode ser opcional aqui; ajustar se sua regra exigir

                        'DEOBJETO' => $request->input('DEOBJETO'),
                    ]);
                }
            }



        } catch (ValidationException $e) {
            if ($isModal) {
                Log::warning('⚠️ [UPDATE] Falha de validação no patrimonio', [
                    'NUSEQPATR' => $patrimonio->NUSEQPATR ?? null,
                    'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO ?? null,
                    'errors' => $e->errors(),
                ]);
                $request->flash();
                $errors = new \Illuminate\Support\MessageBag($e->errors());
                $projetos = Tabfant::select('CDPROJETO', 'NOMEPROJETO')->distinct()->orderBy('NOMEPROJETO')->get();

                return response()->view('patrimonios.partials.form-create', [
                    'projetos' => $projetos,
                    'isModal' => true,
                    'errors' => $errors,
                ], 422);
            }

            throw $e;
        }

        // 3) Criar o Patrimonio associando o código recém-verificado/criado

        $usuarioCriador = Auth::user()->NMLOGIN ?? Auth::user()->NOMEUSER ?? 'SISTEMA';

        $dadosPatrimonio = [

            'NUPATRIMONIO' => $nupatrimonio,

            'CODOBJETO' => $codigo, // campo da tabela patr (pode ser NULL)

            // Usaremos a descrição do objeto como DEPATRIMONIO para manter compatibilidade atual do front
            // ✅ SUPORTE NULL: DEPATRIMONIO pode ser NULL quando não há objeto definido

            'DEPATRIMONIO' => $objeto ? $objeto->DEOBJETO : $request->input('DEOBJETO'),

            'SITUACAO' => $validated['SITUACAO'],

            'FLCONFERIDO' => $this->normalizeConferidoFlag($validated['FLCONFERIDO'] ?? null) ?? 'S',

            'CDMATRFUNCIONARIO' => isset($validated['CDMATRFUNCIONARIO']) ? (int) $validated['CDMATRFUNCIONARIO'] : null,

            'NUMOF' => $validated['NUMOF'] ?? null,

            'DEHISTORICO' => $validated['DEHISTORICO'] ?? null,

            'CDPROJETO' => $validated['CDPROJETO'] ?? null,

            'CDLOCAL' => $validated['CDLOCAL'] ?? null,

            'NMPLANTA' => $validated['NMPLANTA'] ?? null,

            'MARCA' => $validated['MARCA'] ?? null,

            'MODELO' => $validated['MODELO'] ?? null,

            'DTAQUISICAO' => $validated['DTAQUISICAO'] ?? null,

            'DTBAIXA' => $validated['DTBAIXA'] ?? null,

            'PESO' => $validated['PESO'] ?? null,

            'TAMANHO' => $validated['TAMANHO'] ?? null,

            'USUARIO' => $usuarioCriador,

            'DTOPERACAO' => now(),

        ];



        Patrimonio::create($dadosPatrimonio);



        return redirect()->route('patrimonios.index')

            ->with('success', 'Patrimonio cadastrado com sucesso!');

    }



    /**

     * Mostra o formulário de edição para um Patrimonio específico.

     */

    public function edit(Request $request, Patrimonio $patrimonio): View

    {

        $this->authorize('update', $patrimonio);



        // Carregar relações para exibir dados corretos no formulário
        // FONTE DE VERDADE: Carregar projeto diretamente via CDPROJETO

        $patrimonio->load(['local.projeto', 'projeto', 'funcionario']);
        $this->attachLocalCorreto($patrimonio);



        $ultimaVerificacao = null;

        try {

            $ultimaVerificacao = HistoricoMovimentacao::query()

                ->where('NUPATR', $patrimonio->NUPATRIMONIO)

                ->where('CAMPO', 'FLCONFERIDO')

                ->where('VALOR_NOVO', 'S')

                ->orderByDesc('DTOPERACAO')

                ->first();

        } catch (\Throwable $e) {

            Log::warning('Falha ao buscar ultima verificacao do patrimonio', [

                'NUSEQPATR' => $patrimonio->NUSEQPATR ?? null,

                'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO ?? null,

                'erro' => $e->getMessage(),

            ]);

        }



        // TODO: Substitua estes arrays pelas suas consultas reais ao banco de dados.

        $projetos = Tabfant::select('CDPROJETO', 'NOMEPROJETO')->distinct()->orderBy('NOMEPROJETO')->get();



        $isModal = $request->boolean('modal');
        $view = $isModal ? 'patrimonios.partials.form-edit' : 'patrimonios.edit';

        return view($view, array_merge(compact('patrimonio', 'projetos', 'ultimaVerificacao'), ['isModal' => $isModal]));

    }



    /**

     * Atualiza um Patrimonio existente no banco de dados.

     */

    public function update(Request $request, Patrimonio $patrimonio): Response|RedirectResponse

    {

        $this->authorize('update', $patrimonio);



        // Debug: Log de todos os dados recebidos

        Log::info('[UPDATE] Dados recebidos do formulário', [

            'request_all' => $request->all(),

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

        ]);



        $isModal = $request->boolean('modal');
        $validatedData = [];
        $localSelecionado = null;

        try {
            $validatedData = $this->validatePatrimonio($request, $patrimonio);

            $this->enforceAlmoxRulesOnUpdate($patrimonio->CDLOCAL, $validatedData['CDLOCAL'] ?? $patrimonio->CDLOCAL);



            $incomingCdProjeto = $validatedData['CDPROJETO'] ?? $patrimonio->CDPROJETO;
            $incomingCdLocalRaw = $request->input('CDLOCAL');
            if ($incomingCdLocalRaw === null || $incomingCdLocalRaw === '') {
                $incomingCdLocalRaw = $validatedData['CDLOCAL'] ?? $patrimonio->CDLOCAL;
            }

            $incomingCdLocalResolved = $incomingCdLocalRaw;
            if ($incomingCdLocalRaw !== null && $incomingCdLocalRaw !== '') {
                // Validar e resolver CDLOCAL mesmo quando vem como ID (PK) para evitar falso "sem mudança"
                $localSelecionado = $this->validateLocalBelongsToProjeto(
                    $incomingCdProjeto,
                    (int) $incomingCdLocalRaw,
                    'atualizacao de patrimonio'
                );
                if ($localSelecionado) {
                    $incomingCdLocalResolved = (string) $localSelecionado->cdlocal;
                }
            }

            $localChanged = (string) $incomingCdLocalResolved !== (string) $patrimonio->CDLOCAL;
            $projetoChanged = (string) $incomingCdProjeto !== (string) $patrimonio->CDPROJETO;
        } catch (ValidationException $e) {
            // 🔴 LOG DETALHADO DO ERRO DE VALIDAÇÃO
            Log::error('❌ [UPDATE 422] Erro de validação', [
                'NUSEQPATR' => $patrimonio->NUSEQPATR,
                'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,
                'errors' => $e->errors(),
                'request_all' => $request->all(),
                'validation_message' => $e->getMessage(),
            ]);

            if ($isModal) {
                $request->flash();
                $errors = new \Illuminate\Support\MessageBag($e->errors());

                // FONTE DE VERDADE: Carregar projeto diretamente via CDPROJETO
                $patrimonio->load(['local.projeto', 'projeto', 'funcionario']);
                $this->attachLocalCorreto($patrimonio);
                $ultimaVerificacao = null;
                try {
                    $ultimaVerificacao = HistoricoMovimentacao::query()
                        ->where('NUPATR', $patrimonio->NUPATRIMONIO)
                        ->where('CAMPO', 'FLCONFERIDO')
                        ->where('VALOR_NOVO', 'S')
                        ->orderByDesc('DTOPERACAO')
                        ->first();
                } catch (\Throwable $err) {
                    Log::warning('Falha ao buscar ultima verificacao do patrimonio', [
                        'NUSEQPATR' => $patrimonio->NUSEQPATR ?? null,
                        'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO ?? null,
                        'erro' => $err->getMessage(),
                    ]);
                }

                $projetos = Tabfant::select('CDPROJETO', 'NOMEPROJETO')->distinct()->orderBy('NOMEPROJETO')->get();

                return response()->view('patrimonios.partials.form-edit', [
                    'patrimonio' => $patrimonio,
                    'projetos' => $projetos,
                    'ultimaVerificacao' => $ultimaVerificacao,
                    'isModal' => true,
                    'errors' => $errors,
                ], 422);
            }

            throw $e;
        }




        if ($localSelecionado) {

            $validatedData['CDLOCAL'] = (int) $localSelecionado->cdlocal;

            if ($localSelecionado->projeto) {

                $validatedData['CDPROJETO'] = (int) $localSelecionado->projeto->CDPROJETO;

            }

        }



        // Log dos dados antes da Atualização

        Log::info('Patrimonio UPDATE: Dados antes da Atualização', [

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

            'NUPATRIMONIO_old' => $patrimonio->NUPATRIMONIO,

            'CODOBJETO_old' => $patrimonio->CODOBJETO,

            'DEPATRIMONIO_old' => $patrimonio->DEPATRIMONIO,

            'CDLOCAL_old' => $patrimonio->CDLOCAL,

            'CDPROJETO_old' => $patrimonio->CDPROJETO,

            'CDMATRFUNCIONARIO_old' => $patrimonio->CDMATRFUNCIONARIO,

            'SITUACAO_old' => $patrimonio->SITUACAO,

        ]);

        Log::info('Patrimonio UPDATE: Dados validados para atualizar', [

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

            'validated_data' => $validatedData,

        ]);



        // Detectar alterações relevantes

        $oldProjeto = $patrimonio->CDPROJETO;
        $oldNumero = $patrimonio->NUPATRIMONIO;

        $oldSituacao = $patrimonio->SITUACAO;

        $oldLocal = $patrimonio->CDLOCAL;

        $oldConferido = $this->normalizeConferidoFlag($patrimonio->FLCONFERIDO) ?? 'N';

        $flashMessage = 'Patrimonio atualizado com sucesso!';



        // Debug: Log antes do update

        Log::info('[UPDATE] Chamando $patrimonio->update()', [

            'validated_data' => $validatedData,

        ]);



        $patrimonio->update($validatedData);



        // Debug: Recarregar do banco para verificar se salvou

        $patrimonio->refresh();



        $newProjeto = $patrimonio->CDPROJETO;

        $newSituacao = $patrimonio->SITUACAO;

        $newLocal = $patrimonio->CDLOCAL;

        $newConferido = $this->normalizeConferidoFlag($patrimonio->FLCONFERIDO) ?? 'N';
        $newNumero = $patrimonio->NUPATRIMONIO;



        // Log dos dados após a Atualização

        Log::info('Patrimonio UPDATE: Dados após a Atualização', [

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

            'NUPATRIMONIO_after' => $patrimonio->NUPATRIMONIO,

            'CODOBJETO_after' => $patrimonio->CODOBJETO,

            'DEPATRIMONIO_after' => $patrimonio->DEPATRIMONIO,

            'CDLOCAL_after' => $newLocal,

            'CDPROJETO_after' => $newProjeto,

            'CDMATRFUNCIONARIO_after' => $patrimonio->CDMATRFUNCIONARIO,

            'SITUACAO_after' => $newSituacao,

        ]);

        // Evitar retorno de dados antigos no formulário (cache da API /api/patrimonios/buscar/{numero})
        foreach (array_filter([
            'patrimonio_id_' . $patrimonio->NUSEQPATR,
            'patrimonio_numero_' . $oldNumero,
            'patrimonio_numero_' . $newNumero,
        ]) as $cacheKey) {
            Cache::forget($cacheKey);
        }



        // Registrar histórico quando o Local mudar

        if ($newLocal != $oldLocal) {

            try {

                $coAutor = null;

                $actorMat = Auth::user()->CDMATRFUNCIONARIO ?? null;

                $ownerMat = $patrimonio->CDMATRFUNCIONARIO;

                if (!empty($actorMat) && !empty($ownerMat) && $actorMat != $ownerMat) {

                    $coAutor = User::where('CDMATRFUNCIONARIO', $ownerMat)->value('NMLOGIN');

                }

                HistoricoMovimentacao::create([

                    'TIPO' => 'local',

                    'CAMPO' => 'CDLOCAL',

                    'VALOR_ANTIGO' => $oldLocal,

                    'VALOR_NOVO' => $newLocal,

                    'NUPATR' => $patrimonio->NUPATRIMONIO,

                    'CODPROJ' => $newProjeto,

                    'USUARIO' => (Auth::user()->NMLOGIN ?? 'SISTEMA'),

                    'CO_AUTOR' => $coAutor,

                    'DTOPERACAO' => now(),

                ]);

                Log::info('Histórico LOCAL registrado', [

                    'CDLOCAL_old' => $oldLocal,

                    'CDLOCAL_new' => $newLocal

                ]);

            } catch (\Throwable $e) {

                Log::warning('Falha ao gravar histórico de local', [

                    'patrimonio' => $patrimonio->NUSEQPATR,

                    'erro' => $e->getMessage()

                ]);

            }

        }



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

                Log::info('Histórico PROJETO registrado', [

                    'CDPROJETO_old' => $oldProjeto,

                    'CDPROJETO_new' => $newProjeto

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

                Log::info('Histórico SITUAÇÃO registrado', [

                    'SITUACAO_old' => $oldSituacao,

                    'SITUACAO_new' => $newSituacao

                ]);

            } catch (\Throwable $e) {

                Log::warning('Falha ao gravar histórico (situação)', [

                    'patrimonio' => $patrimonio->NUSEQPATR,

                    'erro' => $e->getMessage()

                ]);

            }

        }

        if ($newConferido !== $oldConferido) {

            try {

                $coAutor = null;

                $actorMat = Auth::user()->CDMATRFUNCIONARIO ?? null;

                $ownerMat = $patrimonio->CDMATRFUNCIONARIO;

                if (!empty($actorMat) && !empty($ownerMat) && $actorMat != $ownerMat) {

                    $coAutor = User::where('CDMATRFUNCIONARIO', $ownerMat)->value('NMLOGIN');

                }



                HistoricoMovimentacao::create([

                    'TIPO' => 'conferido',

                    'CAMPO' => 'FLCONFERIDO',

                    'VALOR_ANTIGO' => $oldConferido,

                    'VALOR_NOVO' => $newConferido,

                    'NUPATR' => $patrimonio->NUPATRIMONIO,

                    'CODPROJ' => $newProjeto,

                    'USUARIO' => (Auth::user()->NMLOGIN ?? 'SISTEMA'),

                    'CO_AUTOR' => $coAutor,

                    'DTOPERACAO' => now(),

                ]);

            } catch (\Throwable $e) {

                Log::warning('Falha ao gravar historico (conferido)', [

                    'patrimonio' => $patrimonio->NUSEQPATR,

                    'erro' => $e->getMessage(),

                ]);

            }



            if ($newConferido === 'S') {

                $flashMessage = 'Patrimonio atualizado e verificado com sucesso!';

            } else {

                $flashMessage = 'Patrimonio atualizado e marcado como não verificado!';

            }

        }

        // ✅ Se for requisição AJAX (modal), NÃO fazer redirect
        // Retornar apenas resposta 200 para que JavaScript faça AJAX fetch do grid
        if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response('', 200);
        }

        return redirect()->route('patrimonios.index')->with('success', $flashMessage);

    }



    /**

     * Remove o Patrimonio do banco de dados.

     */

    public function destroy(Patrimonio $patrimonio)

    {

        \Illuminate\Support\Facades\Log::info('[DESTROY] Iniciando deleção', [

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

            'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,

            'user' => Auth::user()->NMLOGIN ?? 'desconhecido',

            'user_id' => Auth::id(),

        ]);



        try {

            $this->authorize('delete', $patrimonio);

            

            \Illuminate\Support\Facades\Log::info('[DESTROY] Autorização concedida', [

                'NUSEQPATR' => $patrimonio->NUSEQPATR,

            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {

            \Illuminate\Support\Facades\Log::error('[DESTROY] Autorização negada', [

                'NUSEQPATR' => $patrimonio->NUSEQPATR,

                'erro' => $e->getMessage(),

            ]);

            

            if (request()->expectsJson()) {

                return response()->json([

                    'message' => 'Você não tem permissão para excluir este Patrimonio.',

                    'code' => 'authorization_failed',

                ], 403);

            }

            

            return redirect()->route('patrimonios.index')

                ->with('error', 'Você não tem permissão para excluir este Patrimonio.');

        }

        

        // Log da deleção

        \Illuminate\Support\Facades\Log::info('¾ [DESTROY] Deletando Patrimonio', [

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

            'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,

            'DEPATRIMONIO' => $patrimonio->DEPATRIMONIO,

            'deletado_por' => Auth::user()->NMLOGIN,

            'user_id' => Auth::id()

        ]);

        

        $patrimonio->delete();

        

        \Illuminate\Support\Facades\Log::info('[DESTROY] Patrimonio deletado com sucesso', [

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

        ]);

        

        if (request()->expectsJson()) {

            return response()->json(['message' => 'Patrimonio deletado com sucesso!'], 204)

                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');

        }

        

        return redirect()->route('patrimonios.index')->with('success', 'Patrimonio deletado com sucesso!');

    }



    /**

     * NOVO MÉTODO DE DELEÇÃO SIMPLIFICADO

     * Método alternativo para deletar Patrimonio por ID direto

     */

    public function deletePatrimonio($id)

    {

        \Illuminate\Support\Facades\Log::info('[DELETE] Requisição recebida', [

            'id' => $id,

            'method' => request()->method(),

            'user' => Auth::user()->NMLOGIN ?? 'guest',

            'user_id' => Auth::id(),

            'ip' => request()->ip()

        ]);



        try {

            // Buscar Patrimonio

            $patrimonio = Patrimonio::where('NUSEQPATR', $id)->first();

            

            if (!$patrimonio) {

                \Illuminate\Support\Facades\Log::warning('[DELETE] Patrimonio não encontrado', ['id' => $id]);

                return response()->json([

                    'success' => false,

                    'message' => 'Patrimonio não encontrado'

                ], 200);

            }



            \Illuminate\Support\Facades\Log::info('[DELETE] Patrimonio encontrado', [

                'NUSEQPATR' => $patrimonio->NUSEQPATR,

                'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,

                'DEPATRIMONIO' => $patrimonio->DEPATRIMONIO

            ]);



            $this->authorize('delete', $patrimonio);

            \Illuminate\Support\Facades\Log::info('[DELETE] Autorização OK');



            // Salvar dados antes de deletar

            $dadosPatrimonio = [

                'NUSEQPATR' => $patrimonio->NUSEQPATR,

                'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,

                'DEPATRIMONIO' => $patrimonio->DEPATRIMONIO

            ];



            // DELETAR

            $deleted = $patrimonio->delete();

            

            \Illuminate\Support\Facades\Log::info('[DELETE] Patrimonio deletado!', [

                'resultado' => $deleted,

                'dados' => $dadosPatrimonio

            ]);



            return response()->json([

                'success' => true,

                'message' => 'Patrimonio deletado com sucesso!',

                'patrimonio' => $dadosPatrimonio

            ], 200);



        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {

            \Illuminate\Support\Facades\Log::warning('[DELETE] Autorização negada', [

                'id' => $id,

                'erro' => $e->getMessage(),

            ]);



            return response()->json([

                'success' => false,

                'message' => 'Você não tem permissão para deletar este Patrimonio.',

            ], 403);

        } catch (\Exception $e) {

            \Illuminate\Support\Facades\Log::error('[DELETE] Erro ao deletar', [

                'id' => $id,

                'erro' => $e->getMessage(),

                'trace' => $e->getTraceAsString()

            ]);



            return response()->json([

                'success' => false,

                'message' => 'Erro ao deletar: ' . $e->getMessage()

            ], 500);

        }

    }



    /**

     * Exibe tela de duplicatas - Patrimonios com mesmo número

     */

    public function duplicatas(): View

    {

        // Encontrar todos os patrimonios que aparecem mais de uma vez

        $duplicatas = Patrimonio::select('NUPATRIMONIO')

            ->groupBy('NUPATRIMONIO')

            ->havingRaw('COUNT(*) > 1')

            ->orderBy('NUPATRIMONIO')

            ->get()

            ->pluck('NUPATRIMONIO')

            ->toArray();



        // Se não há duplicatas, retornar mensagem

        if (empty($duplicatas)) {

            return view('patrimonios.duplicatas', ['grupos' => [], 'temDuplicatas' => false]);

        }



        // Buscar os dados completos dos patrimonios duplicados agrupados

        $grupos = [];

        foreach ($duplicatas as $numero) {

            $grupo = Patrimonio::where('NUPATRIMONIO', $numero)

                ->with('funcionario', 'localProjeto', 'localProjeto.projeto')

                ->orderBy('NUSEQPATR', 'desc') // Mais recente primeiro (maior ID)

                ->get();



            $grupos[$numero] = $grupo;

        }



        return view('patrimonios.duplicatas', [

            'grupos' => $grupos,

            'temDuplicatas' => count($grupos) > 0,

            'totalDuplicatas' => count($grupos),

        ]);

    }



    /**

     * Deleta um Patrimonio (versão para duplicatas)

     * Usado na tela de remoção de duplicatas

     */

    public function deletarDuplicata(Request $request, Patrimonio $patrimonio): RedirectResponse

    {

        $this->authorize('delete', $patrimonio);



        $numero = $patrimonio->NUPATRIMONIO;

        Log::info('Deletando duplicata de Patrimonio', [

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

            'NUPATRIMONIO' => $numero,

            'deletado_por' => Auth::user()->NMLOGIN

        ]);



        $patrimonio->delete();



        return redirect()->route('patrimonios.duplicatas')

            ->with('success', "Duplicata N° $numero deletada com sucesso!");

    }



    // --- MÉTODOS DE API PARA O FORMULÁRIO DINÂMICO ---



    public function buscarPorNumero($numero): JsonResponse
    {
        try {
            // FONTE DE VERDADE: Carregar projeto diretamente via CDPROJETO
            $cacheKey = 'patrimonio_numero_' . intval($numero);
            $ttl = 300; // 5 minutos
            $patrimonio = Cache::get($cacheKey);
            if (!$patrimonio) {
                $patrimonio = Patrimonio::with(['local.projeto', 'projeto', 'funcionario'])->where('NUPATRIMONIO', $numero)->first();
                if ($patrimonio) {
                    $this->attachLocalCorreto($patrimonio);
                    Cache::put($cacheKey, $patrimonio, $ttl);
                    Log::info('📡 [PATRIMONIO] Cache: Buscado #' . $numero);
                } else {
                    return response()->json(null, 404);
                }
            } else {
                Log::info('⚡ [PATRIMONIO] Cache: Hit #' . $numero);
            }

            if ($patrimonio) {
                $this->attachLocalCorreto($patrimonio);
            }

            // VERIFICAR AUTORIZAÇÃO: O usuário pode ver este Patrimonio?
            $user = Auth::user();
            if (!$user) {
                // não autenticado
                return response()->json(['error' => 'não autorizado'], 403);
            }

            // TODOS os usuários autenticados podem ver patrimonio (sem restrição de supervisão)
            return response()->json($patrimonio);
        } catch (\Throwable $e) {
            Log::error('Erro ao buscar Patrimonio por número: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Erro ao buscar Patrimonio'], 500);
        }
    }

    public function getVerificacao($numero): JsonResponse

    {

        try {

            $patrimonio = Patrimonio::where('NUPATRIMONIO', $numero)->first();

            if (!$patrimonio) {

                return response()->json(['conferido' => 'N', 'usuario' => null], 404);

            }



            $ultimaVerificacao = null;

            try {

                $ultimaVerificacao = HistoricoMovimentacao::query()

                    ->where('NUPATR', $patrimonio->NUPATRIMONIO)

                    ->where('CAMPO', 'FLCONFERIDO')

                    ->where('VALOR_NOVO', 'S')

                    ->orderByDesc('DTOPERACAO')

                    ->first();

            } catch (\Throwable $e) {

                Log::warning('Falha ao buscar ultima verificacao', ['NUPATRIMONIO' => $numero]);

            }



            return response()->json([

                'conferido' => $patrimonio->FLCONFERIDO ?? 'N',

                'usuario' => $ultimaVerificacao?->USUARIO ?? null,

            ]);

        } catch (\Throwable $e) {

            Log::error('Erro ao buscar verificacao do patrimonio', ['error' => $e->getMessage()]);

            return response()->json(['conferido' => 'N', 'usuario' => null], 500);

        }

    }



    /**

     * Buscar patrimonio por ID (NUSEQPATR) para modal de consultor

     * Usado no modal de leitura (PERFIL='C')

     */

    public function buscarPorId($id): JsonResponse
    {
        try {
            // FONTE DE VERDADE: Carregar projeto diretamente via CDPROJETO, não via local

            $cacheKey = 'patrimonio_id_' . intval($id);
            $ttl = 300;
            $patrimonio = Cache::get($cacheKey);
            if (!$patrimonio) {
                $patrimonio = Patrimonio::with(['local.projeto', 'projeto', 'funcionario'])->where('NUSEQPATR', $id)->first();
                if ($patrimonio) {
                    $this->attachLocalCorreto($patrimonio);
                    Cache::put($cacheKey, $patrimonio, $ttl);
                } else {
                    return response()->json(['success' => false, 'error' => 'Patrimonio não encontrado'], 404);
                }
            }

            if ($patrimonio) {
                $this->attachLocalCorreto($patrimonio);
            }

            if (!$patrimonio) {
                return response()->json(['success' => false, 'error' => 'Patrimonio não encontrado'], 404);
            }

            // TODOS os usuários autenticados podem ver patrimonio (sem restrição de supervisão)
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'error' => 'Não autenticado'], 403);
            }

            return response()->json(['success' => true, 'patrimonio' => $patrimonio]);
        } catch (\Throwable $e) {
            Log::error('🔴 [PATRIMONIOS] Erro buscarPorId: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'error' => 'Erro ao buscar patrimonio'], 500);
        }
    }

    public function bulkSituacao(Request $request): JsonResponse

    {

        $request->validate([

            'ids' => 'required|array|min:1',

            'ids.*' => 'integer',

            'situacao' => 'required|string|in:EM USO,CONSERTO,BAIXA,A DISPOSICAO'

        ]);



        $ids = collect($request->input('ids', []))

            ->map(fn($v) => (int) $v)

            ->filter()

            ->unique()

            ->values();



        if ($ids->isEmpty()) {

            return response()->json(['error' => 'Nenhum patrimonio selecionado.'], 422);

        }



        $situacao = strtoupper($request->input('situacao'));

        /** @var User|null $user */

        $user = Auth::user();

        if ($user && ($user->PERFIL ?? null) === User::PERFIL_CONSULTOR) {

            return response()->json(['error' => 'Você não tem permissão para alterar patrimonios.'], 403);

        }



        $isAdmin = $user && $user->isAdmin();

        

        // Usuários com permissão total para alteração em massa

        $superUsers = ['BEATRIZ.SC', 'TIAGOP', 'BRUNO'];

        $isSuperUser = $user && in_array(strtoupper($user->NMLOGIN ?? ''), $superUsers, true);



        $patrimonios = Patrimonio::whereIn('NUSEQPATR', $ids)->get();

        if ($patrimonios->isEmpty()) {

            return response()->json(['error' => 'Patrimonios não encontrados.'], 404);

        }



        $unauthorized = [];

        if (!$isAdmin && !$isSuperUser) {

            foreach ($patrimonios as $p) {

                $isResp = (string)($user->CDMATRFUNCIONARIO ?? '') === (string)($p->CDMATRFUNCIONARIO ?? '');

                $usuario = trim((string)($p->USUARIO ?? ''));

                $nmLogin = trim((string)($user->NMLOGIN ?? ''));

                $nmUser  = trim((string)($user->NOMEUSER ?? ''));

                $isCreator = $usuario !== '' && (

                    strcasecmp($usuario, $nmLogin) === 0 ||

                    strcasecmp($usuario, $nmUser) === 0

                );

                if (!($isResp || $isCreator)) {

                    $unauthorized[] = $p->NUSEQPATR;

                }

            }

        }



        if (!empty($unauthorized)) {

            return response()->json([

                'error' => 'Você não tem permissão para alterar todos os itens selecionados.',

                'ids_negados' => $unauthorized,

            ], 403);

        }



        $updated = Patrimonio::whereIn('NUSEQPATR', $ids)->update([

            'SITUACAO' => $situacao,

            'DTOPERACAO' => now(),

        ]);



        Log::info('✏️ Bulk atualização de situação', [

            'user' => $user->NMLOGIN ?? null,

            'situacao' => $situacao,

            'ids_count' => $ids->count(),

            'atualizados' => $updated,

        ]);



        return response()->json([

            'success' => true,

            'atualizados' => $updated,

        ]);

    }



    /**

     * ✅ Deletar patrimonios em massa

     * 

     * Apenas usuários com permissão podem deletar patrimonios que criaram ou são responsáveis

     */

    public function bulkVerificar(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'conferido' => 'nullable|string|in:S,N,1,0',
        ]);

        $ids = collect($request->input('ids', []))
            ->map(fn($v) => (int) $v)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['error' => 'Nenhum patrimonio selecionado.'], 422);
        }

        /** @var User|null $user */
        $user = Auth::user();
        if ($user && ($user->PERFIL ?? null) === User::PERFIL_CONSULTOR) {
            return response()->json(['error' => 'Voce nao tem permissao para alterar patrimonios.'], 403);
        }

        $isAdmin = $user && $user->isAdmin();
        $superUsers = ['BEATRIZ.SC', 'TIAGOP', 'BRUNO'];
        $isSuperUser = $user && in_array(strtoupper($user->NMLOGIN ?? ''), $superUsers, true);

        $patrimonios = Patrimonio::whereIn('NUSEQPATR', $ids)->get();
        if ($patrimonios->isEmpty()) {
            return response()->json(['error' => 'Patrimonios nao encontrados.'], 404);
        }

        $unauthorized = [];
        if (!$isAdmin && !$isSuperUser) {
            foreach ($patrimonios as $p) {
                $isResp = (string)($user->CDMATRFUNCIONARIO ?? '') === (string)($p->CDMATRFUNCIONARIO ?? '');
                $usuario = trim((string)($p->USUARIO ?? ''));
                $nmLogin = trim((string)($user->NMLOGIN ?? ''));
                $nmUser  = trim((string)($user->NOMEUSER ?? ''));
                $isCreator = $usuario !== '' && (
                    strcasecmp($usuario, $nmLogin) === 0 ||
                    strcasecmp($usuario, $nmUser) === 0
                );
                if (!($isResp || $isCreator)) {
                    $unauthorized[] = $p->NUSEQPATR;
                }
            }
        }

        if (!empty($unauthorized)) {
            return response()->json([
                'error' => 'Voce nao tem permissao para alterar todos os itens selecionados.',
                'ids_negados' => $unauthorized,
            ], 403);
        }

        $newConferido = $this->normalizeConferidoFlag($request->input('conferido')) ?? 'S';
        $updated = 0;
        foreach ($patrimonios as $patrimonio) {
            $oldConferido = $this->normalizeConferidoFlag($patrimonio->FLCONFERIDO) ?? 'N';
            if ($oldConferido === $newConferido) {
                continue;
            }

            $patrimonio->FLCONFERIDO = $newConferido;
            $patrimonio->DTOPERACAO = now();
            $patrimonio->save();
            $updated++;

            try {
                $coAutor = null;
                $actorMat = Auth::user()->CDMATRFUNCIONARIO ?? null;
                $ownerMat = $patrimonio->CDMATRFUNCIONARIO;
                if (!empty($actorMat) && !empty($ownerMat) && $actorMat != $ownerMat) {
                    $coAutor = User::where('CDMATRFUNCIONARIO', $ownerMat)->value('NMLOGIN');
                }

                HistoricoMovimentacao::create([
                    'TIPO' => 'conferido',
                    'CAMPO' => 'FLCONFERIDO',
                    'VALOR_ANTIGO' => $oldConferido,
                    'VALOR_NOVO' => $newConferido,
                    'NUPATR' => $patrimonio->NUPATRIMONIO,
                    'CODPROJ' => $patrimonio->CDPROJETO,
                    'USUARIO' => (Auth::user()->NMLOGIN ?? 'SISTEMA'),
                    'CO_AUTOR' => $coAutor,
                    'DTOPERACAO' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Falha ao gravar historico (conferido em massa)', [
                    'patrimonio' => $patrimonio->NUSEQPATR,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Bulk marcacao como verificado', [
            'user' => $user->NMLOGIN ?? null,
            'ids_count' => $ids->count(),
            'atualizados' => $updated,
        ]);

        return response()->json([
            'success' => true,
            'atualizados' => $updated,
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse

    {

        $request->validate([

            'ids' => 'required|array|min:1',

            'ids.*' => 'integer',

        ]);



        $ids = collect($request->input('ids', []))

            ->map(fn($v) => (int) $v)

            ->filter()

            ->unique()

            ->values();



        if ($ids->isEmpty()) {

            return response()->json(['error' => 'Nenhum patrimonio selecionado.'], 422);

        }



        /** @var User|null $user */

        $user = Auth::user();

        // Todos os usuários autenticados podem deletar patrimonios
        // Os patrimonios vão para a tela de removidos para análise pelo Bruno



        $patrimonios = Patrimonio::whereIn('NUSEQPATR', $ids)->get();

        if ($patrimonios->isEmpty()) {

            return response()->json(['error' => 'Patrimonios não encontrados.'], 404);

        }



        // Criar backup antes de deletar

        $backup = [];

        foreach ($patrimonios as $p) {

            $backup[] = [

                'NUSEQPATR' => $p->NUSEQPATR,

                'NUPATRIMONIO' => $p->NUPATRIMONIO,

                'DEPATRIMONIO' => $p->DEPATRIMONIO,

                'CDPROJETO' => $p->CDPROJETO,

                'CDLOCAL' => $p->CDLOCAL,

                'SITUACAO' => $p->SITUACAO,

            ];

        }



        // Deletar (por modelo para disparar observers/eventos e registrar em "Removidos")

        $deleted = 0;

        DB::transaction(function () use ($patrimonios, &$deleted) {

            foreach ($patrimonios as $p) {

                if ($p->delete()) {

                    $deleted++;

                }

            }

        });



        Log::info('🗑️ Bulk deleção de patrimonios', [

            'user' => $user->NMLOGIN ?? null,

            'ids_count' => $ids->count(),

            'deletados' => $deleted,

            'backup' => $backup,

        ]);



        return response()->json([

            'success' => true,

            'deletados' => $deleted,

        ]);

    }



    public function pesquisar(Request $request): JsonResponse

    {

        try {

            $termo = trim((string) $request->input('q', ''));

            /** @var \App\Models\User|null $user */

            $user = Auth::user();



            if (!$user) {

                // não autenticado

                return response()->json([], 403);

            }



            $patrimonios = Patrimonio::select(['NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'SITUACAO'])

                ->get()

                ->toArray();



            // Aplicar filtro inteligente

            $filtrados = \App\Services\FilterService::filtrar(

                $patrimonios,

                $termo,

                ['NUPATRIMONIO', 'DEPATRIMONIO'],  // campos de busca

                ['NUPATRIMONIO' => 'número', 'DEPATRIMONIO' => 'texto'],  // tipos de campo

                10  // limite

            );



            return response()->json($filtrados);

        } catch (\Exception $e) {

            \Illuminate\Support\Facades\Log::error('Erro pesquisar: ' . $e->getMessage());

            return response()->json([], 200);

        }

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



        // Buscar todos os projetos (excluindo código 0 - "não se aplica")

        $projetos = Tabfant::select(['CDPROJETO', 'NOMEPROJETO'])

            ->where('CDPROJETO', '!=', 0)  // Excluir código 0

            ->distinct()

            ->orderByRaw('CAST(CDPROJETO AS UNSIGNED) ASC')  // Ordenação numérica

            ->get()

            ->toArray();



        // Debug log

        Log::debug('pesquisarProjetos', [

            'termo' => $termo,

            'total_projetos' => count($projetos),

            'primeiros_projetos' => array_slice($projetos, 0, 5),

        ]);



        // Se há termo numérico, aplicar busca inteligente por magnitude

        if ($termo !== '' && is_numeric($termo)) {

            $filtrados = $this->buscarProjetosPorMagnitude($projetos, $termo);

        } else if ($termo !== '') {

            // Busca por nome

            $termo_lower = strtolower($termo);

            $filtrados = array_filter($projetos, function ($p) use ($termo_lower) {

                return strpos(strtolower($p['NOMEPROJETO']), $termo_lower) !== false

                    || strpos($p['CDPROJETO'], $termo_lower) !== false;

            });

            $filtrados = array_values($filtrados); // Re-indexar array

        } else {

            // Sem filtro, retorna todos

            $filtrados = $projetos;

        }



        // Limitar a 30 resultados

        $filtrados = array_slice($filtrados, 0, 30);



        // Debug log final

        Log::debug('pesquisarProjetos filtrado', [

            'termo' => $termo,

            'resultados' => count($filtrados),

            'dados' => $filtrados,

        ]);



        return response()->json($filtrados);

    }



    /**

     * Busca projetos por magnitude numérica

     * Se digitar 8: retorna 8, 80-89, 800-899, 8000-8999

     * Se digitar 80: retorna 80-89, 800-899, 8000-8999

     */

    private function buscarProjetosPorMagnitude($projetos, $termo): array

    {

        $termo_len = strlen($termo);

        $termo_num = (int)$termo;



        $resultados = [];



        foreach ($projetos as $projeto) {

            $codigo = (int)$projeto['CDPROJETO'];

            $codigo_str = (string)$codigo;



            // Verificar se começa com o termo

            if (strpos($codigo_str, $termo) === 0) {

                $resultados[] = $projeto;

                continue;

            }



            // Verificar magnitudes (décimos, centenas, milhares)

            // Décimos: 8 -> 80-89

            if ($termo_len === 1) {

                $min = $termo_num * 10;

                $max = $min + 9;

                if ($codigo >= $min && $codigo <= $max) {

                    $resultados[] = $projeto;

                    continue;

                }



                // Centenas: 8 -> 800-899

                $min = $termo_num * 100;

                $max = $min + 99;

                if ($codigo >= $min && $codigo <= $max) {

                    $resultados[] = $projeto;

                    continue;

                }



                // Milhares: 8 -> 8000-8999

                $min = $termo_num * 1000;

                $max = $min + 999;

                if ($codigo >= $min && $codigo <= $max) {

                    $resultados[] = $projeto;

                }

            }

            // Dezenas: 80 -> 800-899, 8000-8999

            else if ($termo_len === 2) {

                // Centenas: 80 -> 800-899

                $min = $termo_num * 10;

                $max = $min + 9;

                if ($codigo >= $min && $codigo <= $max) {

                    $resultados[] = $projeto;

                    continue;

                }



                // Milhares: 80 -> 8000-8999

                $min = $termo_num * 100;

                $max = $min + 99;

                if ($codigo >= $min && $codigo <= $max) {

                    $resultados[] = $projeto;

                }

            }

            // Centenas: 800 -> 8000-8999

            else if ($termo_len === 3) {

                $min = $termo_num * 10;

                $max = $min + 9;

                if ($codigo >= $min && $codigo <= $max) {

                    $resultados[] = $projeto;

                }

            }

        }



        return $resultados;

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

            if ($q !== '' && strlen($q) < 3) {

                return response()->json([]);

            }

            if ($q !== '') {

                $projetosUnicos = array_filter($projetosUnicos, function ($projeto) use ($q) {

                    return stripos((string) $projeto['CDPROJETO'], $q) !== false ||

                        stripos((string) $projeto['NOMEPROJETO'], $q) !== false;

                });

                $projetosUnicos = array_values($projetosUnicos);

            }

            // Limitar a 5 resultados

            $projetosUnicos = array_slice($projetosUnicos, 0, 5);



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

        $termo = trim($request->input('termo', ''));

        $cdprojetoRaw = trim($request->input('cdprojeto', ''));

        // Aceita formatos como "8 - SEDE" e normaliza para "8".

        $cdprojeto = preg_replace('/\D+/', '', $cdprojetoRaw) ?: $cdprojetoRaw;



        // BUSCAR NA TABELA LOCAIS_PROJETO (tem o cdlocal)

        $query = LocalProjeto::query();



        // Regra: projeto define locais. Sem projeto, so permite fallback quando o termo e um cdlocal especifico.

        if ($cdprojeto === '') {

            if ($termo === '' || !is_numeric($termo)) {

                return response()->json([]);

            }

            $query->where('cdlocal', $termo);

        } else {

            // Alguns ambientes possuem multiplos registros tabfant com o mesmo CDPROJETO.

            // Considerar todos evita "sumico" de locais validos.

            $projIds = Tabfant::where('CDPROJETO', $cdprojeto)->pluck('id');

            if ($projIds->isEmpty()) {

                return response()->json([]);

            }

            $query->whereIn('tabfant_id', $projIds->all());

        }



        $locaisProjeto = $query->get();



        $tabfById = Tabfant::whereIn('id', $locaisProjeto->pluck('tabfant_id')->filter()->unique()->values())

            ->get(['id', 'CDPROJETO', 'NOMEPROJETO'])

            ->keyBy('id');



        // Buscar informacoes do projeto na tabfant para cada local

        $locais = $locaisProjeto->map(function ($lp) use ($tabfById) {

            $tabfant = $lp->tabfant_id ? ($tabfById[$lp->tabfant_id] ?? null) : null;



            return [

                'id' => $lp->id,

                'cdlocal' => $lp->cdlocal,

                'LOCAL' => $lp->delocal,

                'delocal' => $lp->delocal,

                'CDPROJETO' => $tabfant ? $tabfant->CDPROJETO : null,

                'NOMEPROJETO' => $tabfant ? $tabfant->NOMEPROJETO : null,

                'tabfant_id' => $lp->tabfant_id,

                'flativo' => $lp->flativo ?? false,

            ];

        })->toArray();



        // Quando o usu�rio informar c�digo num�rico (ex: 1339), priorizar match exato
        // para n�o "sumir" em listas grandes limitadas pelo filtro inteligente.
        if ($termo !== '' && ctype_digit($termo)) {

            $exatos = array_values(array_filter($locais, function ($row) use ($termo) {
                return (string) ($row['cdlocal'] ?? '') === $termo;
            }));

            if (!empty($exatos)) {
                return response()->json($exatos);
            }
        }

        // Aplicar filtro inteligente
        $filtrados = \App\Services\FilterService::filtrar(

            $locais,

            $termo,

            ['cdlocal', 'delocal'],  // campos de busca

            ['cdlocal' => 'n�mero', 'delocal' => 'texto'],  // tipos de campo

            300  // limite ampliado para facilitar sele��o em projetos grandes

        );

        return response()->json($filtrados);

    }



    /**

     * Busca um local específico por ID e retorna informações completas

     * Inclui qual projeto ele realmente pertence (para sincronização de dados desincronizados)

     */



    public function buscarLocalPorId($id): JsonResponse

    {

        try {

            $cdprojeto = request()->query('cdprojeto');



            // Primeiro tenta pelo ID (chave primária)

            $local = LocalProjeto::with('projeto')->find($id);



            // Se o caller informou cdprojeto, nunca retornar local de outro projeto

            if ($local && $cdprojeto) {

                $cdProjetoDoLocal = $local->projeto?->CDPROJETO;

                if (!$cdProjetoDoLocal || (string) $cdProjetoDoLocal !== (string) $cdprojeto) {

                    $local = null;

                }

            }



            // Fallback: algumas telas ainda enviam o código (cdlocal) em vez do ID

            if (!$local) {

                $query = LocalProjeto::with('projeto')->where('cdlocal', $id);



                if ($cdprojeto) {

                    $tabfant = Tabfant::where('CDPROJETO', $cdprojeto)->first();

                    if ($tabfant) {

                        $query->where('tabfant_id', $tabfant->id);

                    } else {

                        return response()->json(['error' => 'Local não encontrado'], 404);

                    }

                }



                $local = $query->first();

            }



            if (!$local) {

                return response()->json(['error' => 'Local não encontrado'], 404);

            }



            return response()->json([

                'id' => $local->id,

                'cdlocal' => $local->cdlocal,

                'delocal' => $local->delocal,

                'LOCAL' => $local->delocal,

                'CDPROJETO' => $local->projeto?->CDPROJETO,

                'NOMEPROJETO' => $local->projeto?->NOMEPROJETO,

                'tabfant_id' => $local->tabfant_id,

                'flativo' => $local->flativo ?? true,

            ]);

        } catch (\Throwable $e) {

            Log::error('Erro ao buscar local por ID:', ['id' => $id, 'erro' => $e->getMessage()]);

            return response()->json(['error' => 'Erro ao buscar local'], 500);

        }

    }



    /**

     *      * DEBUG: Listar todos os locais com código específico­fico

     */

    public function debugLocaisPorCodigo(Request $request): JsonResponse

    {

        $codigo = $request->input('codigo', '');



        Log::info(' [DEBUG] Buscando locais com código:', ['codigo' => $codigo]);



        // CORRIGIDO: Buscar na tabela locais_projeto (tem cdlocal)

        $locaisProjeto = LocalProjeto::where('cdlocal', $codigo)

            ->where('flativo', true)

            ->orderBy('delocal')

            ->get();



        Log::info(' [DEBUG] LocalProjeto encontrados:', ['total' => $locaisProjeto->count()]);



        // Buscar dados do tabfant para cada local

        $locais = $locaisProjeto->map(function ($lp) {

            $tabfant = $lp->tabfant_id ? Tabfant::find($lp->tabfant_id) : null;



            return [

                'id' => $lp->id,

                'cdlocal' => $lp->cdlocal,

                'delocal' => $lp->delocal,

                'tabfant_id' => $lp->tabfant_id,

                'CDPROJETO' => $tabfant?->CDPROJETO,

                'NOMEPROJETO' => $tabfant?->NOMEPROJETO,

            ];

        });



        $resultado = [

            'codigo_buscado' => $codigo,

            'total_encontrado' => $locais->count(),

            'locais' => $locais

        ];



        Log::info(' [DEBUG] Resultado:', $resultado);



        return response()->json($resultado);

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

        $query = Patrimonio::query();



        // Nota: Removido filtro por usuário para que todos os Patrimonios

        // apareçam na tela de atribuição de códigos (requisito de negócio).



        // Filtro por status - default volta a 'disponivel'

        $status = $request->get('status', 'disponivel');

        Log::info('Filtro Status: ' . $status);



        if ($status === 'disponivel') {

            // Patrimonios sem código de termo (campo integer => apenas null significa "sem")

            $query->whereNull('NMPLANTA');

        } elseif ($status === 'indisponivel') {

            // Patrimonios com código de termo

            $query->whereNotNull('NMPLANTA');

        }

        // Se status for vazio ou 'todos', não aplica filtro de status



                // Observação: originalmente excluíamos Patrimonios sem DEPATRIMONIO,

                // mas a regra atual exige que TODOS os Patrimonios cadastrados

                // apareçam na tela de atribuição. Portanto, removemos esse filtro.



        // Aplicar filtros se fornecidos

        if ($request->filled('filtro_numero')) {

            Log::info('Filtro número: ' . $request->filtro_numero);

            $query->where('NUPATRIMONIO', 'like', '%' . $request->filtro_numero . '%');

        }



        if ($request->filled('filtro_descricao')) {

            Log::info('Filtro Descrição: ' . $request->filtro_descricao);

            $query->where('DEPATRIMONIO', 'like', '%' . $request->filtro_descricao . '%');

        }



        if ($request->filled('filtro_modelo')) {

            Log::info('Filtro Modelo: ' . $request->filtro_modelo);

            $query->where('MODELO', 'like', '%' . $request->filtro_modelo . '%');

        }



        // Filtro por projeto para atribuição/termo

        if ($request->filled('filtro_projeto')) {

            Log::info('Filtro Projeto: ' . $request->filtro_projeto);

            $query->where('CDPROJETO', $request->filtro_projeto);

        }



        // Filtro por termo (apenas na aba atribuidos)

        if ($request->filled('filtro_termo')) {

            Log::info('Filtro Termo: ' . $request->filtro_termo);

            $query->where('NMPLANTA', $request->filtro_termo);

        }



        // Filtro por matrícula do responsável (CDMATRFUNCIONARIO)

        if ($request->filled('filtro_matr_responsavel')) {

            Log::info('Filtro Matrícula Responsável: ' . $request->filtro_matr_responsavel);

            $query->where('CDMATRFUNCIONARIO', $request->filtro_matr_responsavel);

        }



        // Filtro por matrícula do cadastrador (USUARIO)

        if ($request->filled('filtro_matr_cadastrador')) {

            Log::info('Filtro Matrícula Cadastrador: ' . $request->filtro_matr_cadastrador);

            // Buscar pelo NMLOGIN do usuário que cadastrou

            $query->whereHas('creator', function ($q) use ($request) {

                $q->where('CDMATRFUNCIONARIO', $request->filtro_matr_cadastrador);

            });

        }



        // Ordenação

        $query->orderBy('NMPLANTA', 'asc');

        $query->orderBy('NUPATRIMONIO', 'asc');



        // Paginação configurável

        $perPage = (int) $request->input('per_page', 30);

        if ($perPage < 30) $perPage = 30;

        if ($perPage > 200) $perPage = 200;



        $patrimonios = $query->paginate($perPage);



        Log::info('Total de Patrimonios após filtro: ' . $patrimonios->total() . ' (Página ' . $patrimonios->currentPage() . ')');

        Log::info('Patrimonios nesta página: ' . count($patrimonios));



        // Preencher descrições ausentes usando a tabela de objetos (consulta em lote)

        $codes = $patrimonios->pluck('CODOBJETO')->filter()->unique()->values()->all();

        if (!empty($codes)) {

            $pkColumn = $this->detectarPKObjetoPatr();

            $descMap = \App\Models\ObjetoPatr::whereIn($pkColumn, $codes)

                ->pluck('DEOBJETO', $pkColumn)

                ->toArray();

        } else {

            $descMap = [];

        }

        foreach ($patrimonios as $p) {

            // Prioridade: DEPATRIMONIO (campo), depois DEOBJETO via CODOBJETO, senão compor por Marca/Modelo/Série

            $display = $p->DEPATRIMONIO ?: ($descMap[$p->CODOBJETO] ?? null);

            if (empty($display)) {

                $parts = array_filter([$p->MODELO ?? null, $p->MARCA ?? null, $p->NUSERIE ?? null, $p->COR ?? null]);

                $display = $parts ? implode(' - ', $parts) : null;

            }

            $p->DEPATRIMONIO = $display ?: '-';

        }



        // Agrupar por NMPLANTA para exibição

        $patrimonios_grouped = $patrimonios->groupBy(function ($item) {

            return $item->NMPLANTA ?? '__sem_termo__';

        });



        return view('patrimonios.atribuir', compact('patrimonios', 'patrimonios_grouped'));

    }



    /**

     * Página isolada (clonada) para atribuição de códigos de termo.

     * Reaproveita a mesma lógica de filtragem da página principal para manter consistência.

     */

    public function atribuirCodigos(Request $request): View

    {

        $query = Patrimonio::query();



        // Nota: Removido filtro por usuário para que todos os Patrimonios

        // apareçam na página de atribuição de códigos (requisito do produto).



        $status = $request->get('status', 'disponivel');

        Log::info('[atribuirCodigos] Filtro Status: ' . $status);



        if ($status === 'disponivel') {

            $query->whereNull('NMPLANTA');

        } elseif ($status === 'indisponivel') {

            $query->whereNotNull('NMPLANTA');

        }



        if ($request->filled('filtro_numero')) {

            Log::info('[atribuirCodigos] Filtro número: ' . $request->filtro_numero);

            $query->where('NUPATRIMONIO', 'like', '%' . $request->filtro_numero . '%');

        }

        if ($request->filled('filtro_descricao')) {

            Log::info('[atribuirCodigos] Filtro Descrição: ' . $request->filtro_descricao);

            $query->where('DEPATRIMONIO', 'like', '%' . $request->filtro_descricao . '%');

        }

        if ($request->filled('filtro_modelo')) {

            Log::info('[atribuirCodigos] Filtro Modelo: ' . $request->filtro_modelo);

            $query->where('MODELO', 'like', '%' . $request->filtro_modelo . '%');

        }

        if ($request->filled('filtro_projeto')) {

            Log::info('[atribuirCodigos] Filtro Projeto: ' . $request->filtro_projeto);

            $query->where('CDPROJETO', $request->filtro_projeto);

        }

        if ($request->filled('filtro_termo')) {

            Log::info('[atribuirCodigos] Filtro Termo: ' . $request->filtro_termo);

            $query->where('NMPLANTA', $request->filtro_termo);

        }



        $query->orderBy('NMPLANTA', 'asc');

        $query->orderBy('NUPATRIMONIO', 'asc');

        $perPage = (int) $request->input('per_page', 30);

        if ($perPage < 30) $perPage = 30;

        if ($perPage > 200) $perPage = 200;

        $patrimonios = $query->paginate($perPage);



        Log::info('[atribuirCodigos] Total de Patrimonios após filtro: ' . $patrimonios->total() . ' (Página ' . $patrimonios->currentPage() . ')');

        Log::info('[atribuirCodigos] Patrimonios nesta página: ' . count($patrimonios));



        // Preencher descrições ausentes usando a tabela de objetos (consulta em lote)

        $codes = $patrimonios->pluck('CODOBJETO')->filter()->unique()->values()->all();

        if (!empty($codes)) {

            $descMap = \App\Models\ObjetoPatr::whereIn('NUSEQOBJ', $codes)

                ->pluck('DEOBJETO', 'NUSEQOBJ')

                ->toArray();

        } else {

            $descMap = [];

        }

        foreach ($patrimonios as $p) {

            $display = $p->DEPATRIMONIO ?: ($descMap[$p->CODOBJETO] ?? null);

            if (empty($display)) {

                $parts = array_filter([$p->MODELO ?? null, $p->MARCA ?? null, $p->NUSERIE ?? null, $p->COR ?? null]);

                $display = $parts ? implode(' - ', $parts) : null;

            }

            $p->DEPATRIMONIO = $display ?: '-';

        }



        // Agrupar por NMPLANTA para exibição

        $patrimonios_grouped = $patrimonios->groupBy(function ($item) {

            return $item->NMPLANTA ?? '__sem_termo__';

        });



        // Reutiliza a mesma view principal de atribuição; evita duplicação e problemas de alias

        return view('patrimonios.atribuir', compact('patrimonios', 'patrimonios_grouped'));

    }



    /**

     * Processar a atribuição/desatribuição de códigos de termo

     */

    public function processarAtribuicao(Request $request): RedirectResponse

    {

        // Verificar autorização de atribuição

        $this->authorize('atribuir', Patrimonio::class);



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



        // Log para verificar se o campo ids (ou patrimonios) está faltando ou vazio

        $fieldName = $request->has('ids') ? 'ids' : 'patrimonios';

        if (!$request->has($fieldName) || empty($request->input($fieldName))) {

            Log::warning('Erro de validação: campo de Patrimonios obrigatório não foi preenchido', [

                'usuario' => Auth::user()?->NMLOGIN ?? 'Desconhecido',

                'user_id' => Auth::id(),

                'ip_address' => $request->ip(),

                'user_agent' => $request->userAgent(),

                'timestamp' => now()->toDateTimeString(),

                'field_sent' => $fieldName,

                'request_data' => $request->except(['password', 'password_confirmation']), // evita registrar senhas

                'operacao' => 'atribuicao',

            ]);

        }



        // Se recebeu 'ids' ao invés de 'patrimonios', renomear para validação consistente

        if ($request->has('ids') && !$request->has('patrimonios')) {

            $request->merge(['patrimonios' => $request->input('ids')]);

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



            // Verificar quais Patrimonios já estão atribuídos

            $jaAtribuidos = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)

                ->whereNotNull('NMPLANTA')

                ->count();



            // Atualizar apenas os Patrimonios disponíveis

            $updated = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)

                ->whereNull('NMPLANTA')

                ->update(['NMPLANTA' => $codigoTermo]);



            $message = "Código de termo {$codigoTermo} atribuído a {$updated} Patrimônio(s) com sucesso!";



            // Log detalhado quando a mensagem de sucesso/erro é exibida

            Log::info('Atribuição de Termo Processada', [

                'usuario' => Auth::user()?->NMLOGIN ?? 'Desconhecido',

                'usuario_id' => Auth::id(),

                'codigo_termo' => $codigoTermo,

                'patrimonios_solicitados' => count($patrimoniosIds),

                'patrimonios_atualizados' => $updated,

                'patrimonios_ja_atribuidos' => $jaAtribuidos,

                'ids_patrimonio' => $patrimoniosIds,

                'timestamp' => now()->toDateTimeString(),

                'mensagem' => $message

            ]);



            // Histórico de atribuição de termo

            if ($updated > 0) {

                try {

                    $patrimoniosAlterados = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)->get(['NUPATRIMONIO']);

                    foreach ($patrimoniosAlterados as $p) {

                        $coAutor = null;

                        $actorMat = Auth::user()->CDMATRFUNCIONARIO ?? null;

                        // Aqui não temos o dono do Patrimonio carregado; buscar rapidamente

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

                $message .= " ({$jaAtribuidos} Patrimônio(s) já estavam atribuídos e foram ignorados)";

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

        // Verificar autorização de desatribuição

        $this->authorize('desatribuir', Patrimonio::class);



        // Log para verificar se o campo ids (ou patrimonios) está faltando ou vazio

        $fieldName = $request->has('ids') ? 'ids' : 'patrimonios';

        if (!$request->has($fieldName) || empty($request->input($fieldName))) {

            Log::warning('Erro de validação: campo de Patrimonios obrigatório não foi preenchido (desatribuição)', [

                'usuario' => Auth::user()?->NMLOGIN ?? 'Desconhecido',

                'user_id' => Auth::id(),

                'ip_address' => $request->ip(),

                'user_agent' => $request->userAgent(),

                'timestamp' => now()->toDateTimeString(),

                'field_sent' => $fieldName,

                'request_data' => $request->except(['password', 'password_confirmation']),

                'operacao' => 'desatribuicao',

            ]);

        }



        // Se recebeu 'ids' ao invés de 'patrimonios', renomear para validação consistente

        if ($request->has('ids') && !$request->has('patrimonios')) {

            $request->merge(['patrimonios' => $request->input('ids')]);

        }



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

                    ->with('success', "Código de termo {$codigoAnterior} removido de {$updated} Patrimônio(s) com sucesso!");

            } else {

                return redirect()->route('patrimonios.atribuir')

                    ->with('warning', 'Nenhum Patrimonio foi desatribuído. Verifique se os Patrimonios selecionados possuem código de termo.');

            }

        } catch (\Exception $e) {

            Log::error('Erro ao processar desatribuição de termo: ' . $e->getMessage());

            return redirect()->route('patrimonios.atribuir')

                ->with('error', 'Erro ao processar desatribuição. Tente novamente.');

        }

    }



    /**

     * API: Retorna lista de cadastradores disponíveis para filtro multi-select

     * Retorna usuários ativos + SISTEMA

     */

    public function listarCadradores(Request $request): JsonResponse

    {

        try {

            /** @var \App\Models\User $user */

            $user = Auth::user();



            $cadastradores = [];



            // SISTEMA (sempre disponível)

            $cadastradores[] = [

                'label' => 'Sistema',

                'value' => 'SISTEMA',

                'type' => 'sistema'

            ];



            $usuarios = User::where('LGATIVO', 'S')

                ->orderBy('NOMEUSER')

                ->get(['NMLOGIN', 'NOMEUSER', 'CDMATRFUNCIONARIO']);



            foreach ($usuarios as $u) {

                $cadastradores[] = [

                    'label' => $u->NOMEUSER . ' (' . $u->NMLOGIN . ')',

                    'value' => $u->NMLOGIN,

                    'type' => 'usuario'

                ];

            }



            Log::info('[API] Listar cadastradores executado', [

                'user_login' => $user->NMLOGIN,

                'user_perfil' => $user->PERFIL,

                'total_cadastradores' => count($cadastradores)

            ]);



            return response()->json($cadastradores);

        } catch (\Exception $e) {

            Log::error('Erro ao listar cadastradores: ' . $e->getMessage());

            return response()->json([], 200);

        }

    }



    // --- MÉTODOS AUXILIARES ---



    private function getPatrimoniosQuery(Request $request)

    {

        /** @var \App\Models\User $user */

        $user = Auth::user();

        

        Log::info(' [getPatrimoniosQuery] INICIADO', [

            'user_id' => $user->NUSEQUSUARIO ?? null,

            'user_login' => $user->NMLOGIN ?? null,

            'user_perfil' => $user->PERFIL ?? null,

            'all_request_params' => $request->all(),

        ]);

        
        // FONTE DE VERDADE: Carregar projeto diretamente via CDPROJETO, não via local

        $query = Patrimonio::with(['funcionario', 'local', 'projeto', 'creator']);



        // Filtro MULTI-SELECT para cadastrador

        $cadastradoresMulti = $request->input('cadastrados_por', []);

        if (is_string($cadastradoresMulti)) {

            // Se vier como string separada por vírgula, converter para array

            $cadastradoresMulti = array_filter(array_map('trim', explode(',', $cadastradoresMulti)));

        }



        if (!empty($cadastradoresMulti)) {

            Log::info('[FILTRO MULTI] Cadastradores múltiplos solicitados', [

                'valores' => $cadastradoresMulti,

                'count' => count($cadastradoresMulti)

            ]);



            // Construir lista de cadastradores selecionados

            $permitidos = [];

            foreach ($cadastradoresMulti as $valor) {

                $valor = trim((string)$valor);

                if ($valor !== '') {

                    $permitidos[] = $valor;

                }

            }

            $permitidos = array_values(array_unique($permitidos));



            if (!empty($permitidos)) {

                Log::info('[FILTRO MULTI] Aplicando filtro com usuários permitidos', [

                    'permitidos' => $permitidos

                ]);



                $query->where(function ($q) use ($permitidos) {

                    foreach ($permitidos as $index => $valor) {

                        $condition = $index === 0 ? 'where' : 'orWhere';

                        if (strcasecmp($valor, 'SISTEMA') === 0) {

                            $q->$condition(function($qInner) {

                                $qInner->whereNull('USUARIO')

                                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', ['SISTEMA']);

                            });

                        } else {

                            $q->$condition(DB::raw('LOWER(USUARIO)'), 'LIKE', '%' . mb_strtolower($valor) . '%');

                        }

                    }

                });

            }

        } else {

            // Filtro SINGLE para compatibilidade com formulário antigo (se não houver multi-select)

            if ($request->filled('cadastrado_por')) {

                $valorFiltro = $request->input('cadastrado_por');



                // Valor especial para restaurar comportamento antigo: não aplicar filtro

                if (trim((string)$valorFiltro) === '__TODOS__') {

                    // não filtrar

                } else {

                    if ($valorFiltro) {

                        if (strcasecmp($valorFiltro, 'SISTEMA') === 0) {

                            $query->where(function($q) {

                                $q->whereNull('USUARIO')

                                  ->orWhere('USUARIO', 'SISTEMA');

                            });

                        } else {

                            $loginFiltro = null;

                            $cdFiltro = null;



                            if (is_numeric($valorFiltro)) {

                                $cdFiltro = $valorFiltro;

                                $usuarioFiltro = User::where('CDMATRFUNCIONARIO', $valorFiltro)->first();

                                $loginFiltro = $usuarioFiltro->NMLOGIN ?? null;

                            } else {

                                $loginFiltro = $valorFiltro;

                                $usuarioFiltro = User::where('NMLOGIN', $valorFiltro)->first();

                                $cdFiltro = $usuarioFiltro->CDMATRFUNCIONARIO ?? null;

                            }



                            $query->where(function ($q) use ($loginFiltro, $cdFiltro, $valorFiltro) {

                                if ($loginFiltro) {

                                    $q->where('USUARIO', $loginFiltro);

                                }

                                if ($cdFiltro) {

                                    $q->orWhere('CDMATRFUNCIONARIO', $cdFiltro);

                                }

                                if (is_numeric($valorFiltro)) {

                                    $q->orWhere('CDMATRFUNCIONARIO', $valorFiltro);

                                }

                            });

                        }

                    }

                }

            }

        }



        // ========== APLICAR FILTROS ADICIONAIS ==========

        Log::info('[FILTROS] Antes de aplicar filtros', [

            'nupatrimonio' => $request->input('nupatrimonio'),

            'cdprojeto' => $request->input('cdprojeto'),

            'descricao' => $request->input('descricao'),

            'situacao' => $request->input('situacao'),

            'modelo' => $request->input('modelo'),

            'nmplanta' => $request->input('nmplanta'),

            'matr_responsavel' => $request->input('matr_responsavel'),

        ]);



        if ($request->filled('nupatrimonio')) {

            $val = trim((string)$request->input('nupatrimonio'));

            if ($val !== '') {

                if (is_numeric($val)) {

                    $intVal = (int) $val;

                    Log::info('[FILTRO] nupatrimonio aplicado (INT)', ['val' => $intVal]);

                    $query->where('NUPATRIMONIO', $intVal);

                } else {

                    Log::info('[FILTRO] nupatrimonio aplicado (LIKE)', ['val' => $val]);

                    $query->whereRaw('LOWER(CAST(NUPATRIMONIO AS CHAR)) LIKE ?', ['%' . mb_strtolower($val) . '%']);

                }

            } else {

                Log::info('[FILTRO] nupatrimonio vazio (não aplicado)');

            }

        }



        if ($request->filled('cdprojeto')) {

            $val = trim((string)$request->input('cdprojeto'));

            if ($val !== '') {

                Log::info('[FILTRO] cdprojeto aplicado', ['val' => $val]);

                $query->where(function($q) use ($val) {

                    $q->where('CDPROJETO', $val)

                      ->orWhereHas('local.projeto', function($q2) use ($val) {

                          $q2->where('CDPROJETO', $val);

                      });

                });

            } else {

                Log::info('[FILTRO] cdprojeto vazio (não aplicado)');

            }

        }



        if ($request->filled('descricao')) {

            $val = trim((string)$request->input('descricao'));

            if ($val !== '') {

                $like = '%' . mb_strtolower($val) . '%';

                Log::info('[FILTRO] descricao aplicado', ['val' => $val]);

                $query->whereRaw('LOWER(DEPATRIMONIO) LIKE ?', [$like]);

            } else {

                Log::info('[FILTRO] descricao vazio (não aplicado)');

            }

        }



        if ($request->filled('situacao')) {

            $val = trim((string)$request->input('situacao'));

            if ($val !== '') {

                Log::info('[FILTRO] situacao aplicado', ['val' => $val]);

                $query->where('SITUACAO', $val);

            } else {

                Log::info('[FILTRO] situacao vazio (não aplicado)');

            }

        }



        if ($request->filled('modelo')) {

            $val = trim((string)$request->input('modelo'));

            if ($val !== '') {

                Log::info('[FILTRO] modelo aplicado', ['val' => $val]);

                $query->whereRaw('LOWER(MODELO) LIKE ?', ['%' . mb_strtolower($val) . '%']);

            } else {

                Log::info('[FILTRO] modelo vazio (não aplicado)');

            }

        }



        if ($request->filled('nmplanta')) {

            $val = trim((string)$request->input('nmplanta'));

            if ($val !== '') {

                Log::info('[FILTRO] nmplanta aplicado', ['val' => $val]);

                $query->where('NMPLANTA', $val);

            } else {

                Log::info('[FILTRO] nmplanta vazio (não aplicado)');

            }

        }



        if ($request->filled('matr_responsavel')) {

            $val = trim((string)$request->input('matr_responsavel'));

            if ($val !== '') {

                Log::info('[FILTRO] matr_responsavel aplicado', ['val' => $val]);

                if (is_numeric($val)) {

                    $query->where('CDMATRFUNCIONARIO', $val);

                } else {

                    $usuarioFiltro = User::where('NMLOGIN', $val)->orWhereRaw('LOWER(NOMEUSER) LIKE ?', ['%' . mb_strtolower($val) . '%'])->first();

                    if ($usuarioFiltro) {

                        Log::info('[FILTRO] matr_responsavel encontrado usuário', ['cdmatr' => $usuarioFiltro->CDMATRFUNCIONARIO, 'nmlogin' => $usuarioFiltro->NMLOGIN]);

                        $query->where('CDMATRFUNCIONARIO', $usuarioFiltro->CDMATRFUNCIONARIO);

                    } else {

                        Log::info('[FILTRO] matr_responsavel usuário NÃO encontrado', ['val' => $val]);

                        $query->whereHas('funcionario', function($q) use ($val) {

                            $q->whereRaw('LOWER(NOMEFUNCIONARIO) LIKE ?', ['%' . mb_strtolower($val) . '%']);

                        });

                    }

                }

            } else {

                Log::info('[FILTRO] matr_responsavel vazio (não aplicado)');

            }

        }





        // Filtro de UF (multi-select) por UF efetiva:
        // prioridade: projeto direto -> projeto do local (alinhado ao projeto do patrimônio)
        // -> UF do local (alinhada) -> UF armazenada no patrimônio.
        if ($request->filled('uf')) {
            $ufs = $request->input('uf', []);
            if (is_string($ufs)) {
                $ufs = array_filter(array_map('trim', explode(',', $ufs)));
            }

            $ufs = array_values(array_unique(array_filter(array_map(
                fn ($uf) => strtoupper(trim((string) $uf)),
                (array) $ufs
            ))));

            if (!empty($ufs)) {
                Log::info('[FILTRO] UF aplicado (uf_efetiva)', ['ufs' => $ufs]);

                $placeholders = implode(',', array_fill(0, count($ufs), '?'));

                $effectiveUfSql = "COALESCE(
                    (SELECT UPPER(TRIM(t.UF))
                     FROM tabfant t
                     WHERE t.CDPROJETO = patr.CDPROJETO
                       AND t.UF IS NOT NULL AND TRIM(t.UF) != ''
                     LIMIT 1),

                    (SELECT UPPER(TRIM(t2.UF))
                     FROM locais_projeto l2
                     JOIN tabfant t2 ON t2.id = l2.tabfant_id
                     WHERE l2.cdlocal = patr.CDLOCAL
                       AND (patr.CDPROJETO IS NULL OR TRIM(patr.CDPROJETO) = '' OR t2.CDPROJETO = patr.CDPROJETO)
                       AND t2.UF IS NOT NULL AND TRIM(t2.UF) != ''
                     LIMIT 1),

                    (SELECT UPPER(TRIM(l3.UF))
                     FROM locais_projeto l3
                     LEFT JOIN tabfant t3 ON t3.id = l3.tabfant_id
                     WHERE l3.cdlocal = patr.CDLOCAL
                       AND (patr.CDPROJETO IS NULL OR TRIM(patr.CDPROJETO) = '' OR t3.CDPROJETO = patr.CDPROJETO OR t3.CDPROJETO IS NULL)
                       AND l3.UF IS NOT NULL AND TRIM(l3.UF) != ''
                     LIMIT 1),

                    NULLIF(UPPER(TRIM(patr.UF)), '')
                )";

                $query->whereRaw("{$effectiveUfSql} IN ({$placeholders})", $ufs);
            } else {
                Log::info('[FILTRO] UF vazio (não aplicado)');
            }
        }
        Log::info('[QUERY] SQL gerada', [

            'sql' => $query->toSql(),

            'bindings' => $query->getBindings(),

        ]);



        // Priorizar lançamentos do usuário autenticado no topo, depois ordenar por DTOPERACAO desc

        try {

            $nmLogin = (string) ($user->NMLOGIN ?? '');

            $cdMatr = $user->CDMATRFUNCIONARIO ?? null;

            // CASE: 0 para registros do usuário (por login ou matrícula), 1 para outros

            $query->orderByRaw("CASE WHEN LOWER(USUARIO) = LOWER(?) OR CDMATRFUNCIONARIO = ? THEN 0 ELSE 1 END", [$nmLogin, $cdMatr]);

            $query->orderBy('DTOPERACAO', 'desc');

        } catch (\Throwable $e) {

            // se algo falhar, não interromper; continuar com Ordenação padrão

            Log::warning('Falha ao aplicar Ordenação por usuário/DTOPERACAO: ' . $e->getMessage());

        }



        // Permitir ordenar também por DTAQUISICAO (ordena após a prioridade do usuário)

        $sortableColumns = ['NUPATRIMONIO', 'MODELO', 'DEPATRIMONIO', 'SITUACAO', 'DTAQUISICAO'];

        $sortColumn = $request->input('sort', 'DTAQUISICAO');

        $sortDirection = $request->input('direction', 'asc');

        if (in_array($sortColumn, $sortableColumns)) {

            $query->orderBy($sortColumn, $sortDirection);

        } else {

            // Ordenação padrão por data de aquisição crescente

            $query->orderBy('DTAQUISICAO', 'asc');

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

            $user = Auth::user();



            if (!$user) {

                return response()->json(['error' => 'não autorizado'], 403);

            }



            // Query para Patrimonios disponíveis (sem termo atribuído ou conforme regra de negócio)

            $query = Patrimonio::with(['funcionario'])

                ->whereNull('NMPLANTA') // Sem código de termo

                ->orWhere('NMPLANTA', '') // Ou código vazio

                ->orderBy('NUPATRIMONIO', 'asc');



            // Nota: Removido filtro de segurança que restringia Patrimônios

            // para não-admins. Todos os Patrimônios serão retornados para a

            // listagem de disponibilidade/atribuição conforme regra de negócio.



            // Paginar manualmente

            $total = $query->count();

            $patrimonios = $query->skip(($page - 1) * $perPage)

                ->take($perPage)

                ->get();



            return response()->json([

                'data' => $patrimonios->map(function ($p) use ($patrimonios) {

                        // Definir texto de exibição com prioridade: DEPATRIMONIO -> MODELO -> MARCA -> OBJETO(DEOBJETO) -> fallback

                        $displayText = null;

                        $displaySource = null;



                        if (!empty($p->DEPATRIMONIO)) {

                            $displayText = $p->DEPATRIMONIO;

                            $displaySource = 'DEPATRIMONIO';

                        } elseif (!empty($p->MODELO)) {

                            $displayText = $p->MODELO;

                            $displaySource = 'MODELO';

                        } elseif (!empty($p->MARCA)) {

                            $displayText = $p->MARCA;

                            $displaySource = 'MARCA';

                        } elseif (!empty($p->CODOBJETO)) {

                            $pkColumn = $this->detectarPKObjetoPatr();

                            $obj = \App\Models\ObjetoPatr::where($pkColumn, $p->CODOBJETO)->first();

                            if ($obj && !empty($obj->DEOBJETO)) {

                                $displayText = $obj->DEOBJETO;

                                $displaySource = 'OBJETO';

                            }

                        }



                        if (empty($displayText)) {

                            // Último fallback: tentar juntar campos menores (número série, cor) ou usar texto padrão

                            $parts = array_filter([$p->NUSERIE ?? null, $p->COR ?? null]);

                            $displayText = $parts ? implode(' - ', $parts) : '-';

                            $displaySource = $parts ? 'COMPOSITE' : 'FALLBACK';

                        }



                        return [

                            'NUSEQPATR' => $p->NUSEQPATR,

                            'NUPATRIMONIO' => $p->NUPATRIMONIO,

                            // DEPATRIMONIO entregue como texto amigável de exibição (nunca vazio)

                            'DEPATRIMONIO' => $displayText,

                            'DEPATRIMONIO_SOURCE' => $displaySource,

                            'NMPLANTA' => $p->NMPLANTA,

                            'MODELO' => $p->MODELO,

                            // Adiciona projeto associado se existir

                            'projeto' => $p->local ? [

                                'CDPROJETO' => $p->local->CDPROJETO ?? null,

                                'NOMEPROJETO' => $p->local->NOMEPROJETO ?? null

                            ] : null,

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



    private function validatePatrimonio(Request $request, ?Patrimonio $patrimonio = null): array

    {

        // Debug inicial

        Log::info('[VALIDATE] Início da validação', [

            'request_all' => $request->all(),

        ]);



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

            'FLCONFERIDO' => 'nullable|string|in:S,N,1,0',

            'SITUACAO' => 'required|string|in:EM USO,CONSERTO,BAIXA,A DISPOSICAO,À DISPOSIÇÃO,A DISPOSIÇÃO,DISPONIVEL',

            'DTAQUISICAO' => 'nullable|date',

            'DTBAIXA' => 'nullable|date',

            'PESO' => 'nullable|numeric|min:0',

            'TAMANHO' => 'nullable|string|max:100',

            // Matricula precisa existir na tabela funcionarios

            'CDMATRFUNCIONARIO' => 'nullable|integer',

        ]);



        if (array_key_exists('CDMATRFUNCIONARIO', $data) && $data['CDMATRFUNCIONARIO'] !== null && $data['CDMATRFUNCIONARIO'] !== '') {
            $cdMat = (int) $data['CDMATRFUNCIONARIO'];
            $exists = DB::table('funcionarios')->where('CDMATRFUNCIONARIO', $cdMat)->exists();
            $isSameLegacy = $patrimonio && (string) $patrimonio->CDMATRFUNCIONARIO === (string) $cdMat;
            if (!$exists && !$isSameLegacy) {
                throw ValidationException::withMessages([
                    'CDMATRFUNCIONARIO' => 'Matricula do responsavel nao encontrada no sistema.',
                ]);
            }
            if (!$exists && $isSameLegacy) {
                Log::warning('Matricula responsavel legado nao encontrada em funcionarios; mantendo valor existente.', [
                    'CDMATRFUNCIONARIO' => $cdMat,
                    'NUSEQPATR' => $patrimonio?->NUSEQPATR,
                    'NUPATRIMONIO' => $patrimonio?->NUPATRIMONIO,
                ]);
            }
        }

        Log::info('[VALIDATE] Dados após validação inicial', [

            'data' => $data,

        ]);



        // 2) Resolver o código do objeto a partir de NUSEQOBJ (preferencial) ou CODOBJETO (fallback)

        $codigoInput = $request->input('NUSEQOBJ', $request->input('CODOBJETO'));

        // Se não informar código, permitir NULL (patrimonios com objeto indefinido)
        if ($codigoInput === null || $codigoInput === '') {
            $codigoInput = null;
        }

        if ($codigoInput !== null && !is_numeric($codigoInput)) {

            throw ValidationException::withMessages([

                'NUSEQOBJ' => 'O código do objeto deve ser numérico.'

            ]);

        }

        $codigo = $codigoInput !== null ? (int) $codigoInput : null;



        // 3) Garantir existência do registro em OBJETOPATR (se código informado)

        $objeto = null;
        $isSameCodigoAtual = $patrimonio && (string) $patrimonio->CODOBJETO === (string) $codigo;
        
        if ($codigo !== null) {
            $objeto = ObjetoPatr::find($codigo);

            if (!$objeto) {
                $descricao = trim((string) $request->input('DEOBJETO', ''));

                // Em modo UPDATE, não bloquear alterações simples (ex.: SITUACAO) por causa de legado
                // onde o código existe em PATR mas ainda não está em OBJETOPATR.
                if (!$isSameCodigoAtual && $descricao === '') {
                    // ❌ Tentando CRIAR um novo objeto SEM descrição → erro
                    throw ValidationException::withMessages([
                        'DEOBJETO' => 'Informe a descrição do novo código.',
                    ]);
                }

                if ($descricao !== '') {
                    // ✅ Criar novo objeto com descrição fornecida
                    // Usar o nome da PK resolvido dinamicamente pelo Model (NUSEQOBJETO no KingHost, NUSEQOBJ local)
                    $pkName = (new ObjetoPatr())->getKeyName();
                    
                    // Buscar NUSEQTIPOPATR do patrimonio atual (se existe) ou definir valor padrão
                    $nuseqTipoPatr = null;
                    if ($patrimonio && $patrimonio->CODOBJETO) {
                        $objetoExistente = ObjetoPatr::find($patrimonio->CODOBJETO);
                        $nuseqTipoPatr = $objetoExistente?->NUSEQTIPOPATR;
                    }
                    // Fallback: tipo genérico (1 = "OUTROS" ou valor default)
                    $nuseqTipoPatr = $nuseqTipoPatr ?? 1;
                    
                    $objeto = ObjetoPatr::create([
                        $pkName => $codigo,
                        'NUSEQTIPOPATR' => $nuseqTipoPatr,
                        'DEOBJETO' => $descricao,
                    ]);
                } else {
                    // ⚠️ Código legado: existe em PATR mas não em OBJETOPATR
                    // Manter o patrimonio como está, sem criar objeto
                    Log::warning('⚠️ [VALIDATE] Código legado não em OBJETOPATR; mantendo sem criar', [
                        'NUSEQPATR' => $patrimonio?->NUSEQPATR,
                        'NUPATRIMONIO' => $patrimonio?->NUPATRIMONIO,
                        'CODOBJETO' => $codigo,
                    ]);
                }
            }
        }



        // 4) Mapear para os campos reais da tabela PATR

        $data['CODOBJETO'] = $codigo;
        // Só atualizar DEPATRIMONIO quando conseguimos resolver/criar o objeto.
        // Isso evita apagar a descrição ao editar campos não relacionados (ex.: SITUACAO).
        if ($objeto) {
            $data['DEPATRIMONIO'] = $objeto->DEOBJETO; // mantém compatibilidade de exibição no index/relatórios
        }

        unset($data['NUSEQOBJ'], $data['DEOBJETO']);

        if (array_key_exists('FLCONFERIDO', $data)) {

            $data['FLCONFERIDO'] = $this->normalizeConferidoFlag($data['FLCONFERIDO']);

        }



        Log::info('[VALIDATE] Após mapear código do objeto', [

            'CODOBJETO' => $data['CODOBJETO'],

            'DEPATRIMONIO' => $data['DEPATRIMONIO'] ?? null,

        ]);



        // 5) Sincronização projeto-local: alinhar projeto e gravar o cdlocal (número do local)
        $resolvedLocal = null;
        $resolvedCdLocal = $data['CDLOCAL'] ?? null;
        if (!empty($data['CDLOCAL'])) {
            $resolvedLocal = $this->validateLocalBelongsToProjeto(
                $data['CDPROJETO'] ?? null,
                (int) $data['CDLOCAL'],
                'atualizacao de patrimonio'
            );

            if ($resolvedLocal) {
                $resolvedCdLocal = (int) $resolvedLocal->cdlocal;
            }
        }

        $shouldValidateLocal = true;
        if ($patrimonio) {
            $incomingCdProjeto = $data['CDPROJETO'] ?? $patrimonio->CDPROJETO;
            $incomingCdLocal = $resolvedCdLocal ?? $patrimonio->CDLOCAL;
            $shouldValidateLocal =
                (string) $incomingCdLocal !== (string) $patrimonio->CDLOCAL
                || (string) $incomingCdProjeto !== (string) $patrimonio->CDPROJETO;
        }

        if ($resolvedLocal) {
            $data['CDLOCAL'] = (int) $resolvedLocal->cdlocal;
            if ($resolvedLocal->projeto) {
                $data['CDPROJETO'] = (int) $resolvedLocal->projeto->CDPROJETO;
            }
        }



        Log::info('[VALIDATE] Dados finais que serão retornados', [

            'final_data' => $data,

        ]);



        return $data;

    }



    /* === Rotas solicitadas para geração e atribuição direta de códigos (fluxo simplificado) === */

    private function normalizeConferidoFlag(mixed $value): ?string

    {

        if ($value === null) {

            return null;

        }



        $raw = mb_strtoupper(trim((string) $value));

        if ($raw === '') {

            return null;

        }



        $truthy = ['S', '1', 'SIM', 'TRUE', 'T', 'Y', 'YES', 'ON'];

        $falsy = ['N', '0', 'NAO', 'NÃO', 'NO', 'FALSE', 'F', 'OFF'];



        if (in_array($raw, $truthy, true)) {

            return 'S';

        }



        if (in_array($raw, $falsy, true)) {

            return 'N';

        }



        return null;

    }



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

     * Desatribui (remove) o código de termo de uma lista de Patrimonios (API JSON usada na página de atribuição)

     */

    public function desatribuirCodigo(Request $request): JsonResponse

    {

        $request->validate([

            'ids' => 'required|array|min:1',

            'ids.*' => 'integer'

        ]);

        try {

            $ids = $request->input('ids', []);

            // Seleciona Patrimonios que realmente têm código para evitar updates desnecessários

            $patrimonios = Patrimonio::whereIn('NUSEQPATR', $ids)->whereNotNull('NMPLANTA')->get(['NUSEQPATR', 'NUPATRIMONIO', 'NMPLANTA', 'CDMATRFUNCIONARIO']);

            if ($patrimonios->isEmpty()) {

                return response()->json(['message' => 'Nenhum Patrimonio elegível para desatribuir', 'updated_ids' => []], 200);

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

     * Cria um novo local vinculado a um projeto existente.

     * Usado no modal de criar local do formulário de Patrimonio.

     */

    public function criarLocalVinculadoProjeto(Request $request): JsonResponse

    {

        // Log para debug

        Log::info('criarLocalVinculadoProjeto chamado', [

            'dados_recebidos' => $request->all(),

            'headers' => $request->headers->all()

        ]);



        try {

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [

                'local' => 'required|string|max:255',

                'cdprojeto' => 'required', // Aceita string ou número

                'cdlocal' => 'required',    // Aceita string ou número

            ], [

                'local.required' => 'Nome do local é obrigatório.',

                'cdprojeto.required' => 'Código do projeto é obrigatório.',

                'cdlocal.required' => 'Código do local base é obrigatório.',

            ]);



            if ($validator->fails()) {

                Log::warning('Validação falhou', ['erros' => $validator->errors()->toArray()]);

                return response()->json([

                    'success' => false,

                    'message' => 'Erro de validação.',

                    'errors' => $validator->errors()

                ], 422);

            }



            $nomeLocal = $request->input('local');

            $cdprojeto = (int) $request->input('cdprojeto');  // Converter para INT, não STRING!

            $cdlocalBase = (string) $request->input('cdlocal');



            // Buscar o projeto no tabfant

            $projeto = Tabfant::where('CDPROJETO', $cdprojeto)->first();



            if (!$projeto) {

                return response()->json([

                    'success' => false,

                    'message' => 'Projeto não encontrado.'

                ], 404);

            }



            // Usar o MESMO codigo do local base (nao incrementar)

            // Multiplos locais podem ter o mesmo CDLOCAL mas nomes diferentes

            $novoCdlocal = $cdlocalBase;



            DB::beginTransaction();

            try {

                // Criar na tabela locais_projeto vinculando ao projeto existente

                $localProjeto = LocalProjeto::create([

                    'cdlocal' => $novoCdlocal,

                    'delocal' => $nomeLocal,

                    'tabfant_id' => $projeto->id,

                    'flativo' => true,

                ]);



                DB::commit();



                Log::info('Local criado com sucesso', [

                    'tabfant_id' => $projeto->id,

                    'local_projeto_id' => $localProjeto->id,

                    'cdlocal' => $novoCdlocal

                ]);



                return response()->json([

                    'success' => true,

                    'cdlocal' => $novoCdlocal,

                    'id' => $localProjeto->id,

                    'data' => [

                        'id' => $localProjeto->id,

                        'cdlocal' => $novoCdlocal,

                        'LOCAL' => $nomeLocal,

                        'delocal' => $nomeLocal,

                        'CDPROJETO' => $projeto->CDPROJETO,

                        'NOMEPROJETO' => $projeto->NOMEPROJETO,

                        'tabfant_id' => $projeto->id,

                    ],

                    'local' => [

                        'id' => $localProjeto->id,

                        'cdlocal' => $novoCdlocal,

                        'LOCAL' => $nomeLocal,

                        'delocal' => $nomeLocal,

                        'CDPROJETO' => $projeto->CDPROJETO,

                        'NOMEPROJETO' => $projeto->NOMEPROJETO,

                        'tabfant_id' => $projeto->id,

                    ]

                ]);

            } catch (\Exception $e) {

                DB::rollBack();

                throw $e;

            }

        } catch (\Exception $e) {

            \Illuminate\Support\Facades\Log::error('Erro ao criar local vinculado:', [

                'erro' => $e->getMessage(),

                'trace' => $e->getTraceAsString()

            ]);



            return response()->json([

                'success' => false,

                'message' => 'Erro ao criar local: ' . $e->getMessage()

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

     * Cria local e/ou projeto baseado nos dados do formulário de Patrimonio.

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

            // Se foi fornecido nome do local, criar apenas se NÃO houver projeto

            if ($nomeLocal && !$projeto) {

                $local = LocalProjeto::create([

                    'cdlocal' => $cdlocal,

                    'delocal' => $nomeLocal,

                    'tabfant_id' => null,

                    'flativo' => true,

                ]);

                \Illuminate\Support\Facades\Log::info('Local criado:', [

                    'cdlocal' => $local->cdlocal,

                    'delocal' => $local->delocal

                ]);

            }



            // Se foi criado um projeto, SEMPRE criar uma nova entrada na tabela locais_projeto para a associação

            if ($projeto) {

                // Pegar o nome do local - prioridade: nomeLocal > nomeLocalAtual > "Local {cdlocal}"

                $nomeLocalParaAssociacao = $nomeLocal ?: ($nomeLocalAtual ?: "Local {$cdlocal}");



                // Criar apenas a associação local-projeto

                $local = LocalProjeto::create([

                    'cdlocal' => $cdlocal,

                    'delocal' => $nomeLocalParaAssociacao,

                    'tabfant_id' => $projeto->id,

                    'flativo' => true,

                ]);



                \Illuminate\Support\Facades\Log::info('Nova associação local-projeto criada:', [

                    'id' => $local->id,

                    'cdlocal' => $local->cdlocal,

                    'delocal' => $local->delocal,

                    'tabfant_id' => $local->tabfant_id,

                    'projeto_codigo' => $projeto->CDPROJETO,

                    'projeto_nome' => $projeto->NOMEPROJETO

                ]);

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



    /**

     * Regras de negócio para almoxarifado central (999915) e em trânsito (2002) na criação.

     */

    



    /**

     * Regras de negócio para almoxarifado central (999915) e em trânsito (2002) na criação.

     */

    private function enforceAlmoxRulesOnCreate($cdlocal): void

    {

        // ✅ Validações de almoxarifado removidas - BEATRIZ.SC e TIAGOP podem criar normalmente

        // Ambos podem criar em qualquer local sem restrições

        return;

    }



    /**

     * Regras de negócio para almoxarifado central (999915) e em trânsito (2002) na edição.

     */

    private function enforceAlmoxRulesOnUpdate($oldLocal, $newLocal): void

    {

        // ✅ Validações de almoxarifado removidas - BEATRIZ.SC e TIAGOP podem mover normalmente

        // Ambos podem mover itens entre locais sem restrições

        return;

    }

    /**
     * Resolve o local correto considerando CDLOCAL e CDPROJETO (evita ambiguidade por cdlocal repetido).
     */
    private function attachLocalCorreto(Patrimonio $patrimonio): void
    {
        $cdlocal = $patrimonio->CDLOCAL;
        if ($cdlocal === null || $cdlocal === '') {
            return;
        }

        $query = LocalProjeto::with('projeto')->where('cdlocal', $cdlocal);
        if (!empty($patrimonio->CDPROJETO)) {
            $query->whereHas('projeto', function ($q) use ($patrimonio) {
                $q->where('CDPROJETO', $patrimonio->CDPROJETO);
            });
        }

        $local = $query->first();
        if (!$local) {
            $local = LocalProjeto::with('projeto')->where('cdlocal', $cdlocal)->first();
        }

        if ($local) {
            $patrimonio->setRelation('local', $local);
        }
    }



    /**

     * Resolve o código (cdlocal) a partir do ID do LocalProjeto.

     */

    private function resolveLocalCode($localId): ?string

    {

        if (is_null($localId) || $localId === '') {

            return null;

        }

        $local = LocalProjeto::find($localId);

        if ($local) {

            return (string) $local->cdlocal;

        }

        return is_scalar($localId) ? (string) $localId : null;

    }



    private function isTiago(string $login): bool

    {

        $logins = ['tiagop', 'tiago', 'tiago.sc', 'tiago.p', 'tiago_p'];

        return in_array($login, $logins, true);

    }



    private function isBeatriz(string $login): bool

    {

        $logins = ['beatriz.sc', 'bea.sc', 'beatriz', 'beatriz_sc'];

        return in_array($login, $logins, true);

    }



    /**

     * ⚠️ VALIDAÇÃO CRÍTICA: Garante que o local pertence ao projeto selecionado

     * REGRA DE NEGÓCIO: O projeto define os locais disponíveis!

     */

    private function validateLocalBelongsToProjeto(?int $cdprojeto, ?int $cdlocal, string $operacao = 'operação'): ?LocalProjeto

    {

        // Precisa ao menos do local para validar/regrafia

        if (!$cdlocal) {

            return null;

        }



        // Buscar o projeto (Tabfant) pelo CDPROJETO quando informado

        $projeto = null;

        if ($cdprojeto) {

            $projeto = Tabfant::where('CDPROJETO', $cdprojeto)->first();



            if (!$projeto) {

                throw ValidationException::withMessages([

                    'CDPROJETO' => "Projeto com código {$cdprojeto} não encontrado no sistema.",

                ]);

            }

        }



        // Preferir busca por código (cdlocal) dentro do projeto quando CDPROJETO informado.
        // Isso evita confundir cdlocal com o ID (PK) de outro projeto.
        $local = null;
        if ($projeto) {
            $local = LocalProjeto::with('projeto')
                ->where('cdlocal', $cdlocal)
                ->where('tabfant_id', $projeto->id)
                ->first();
        }

        // Fallback: tentar como ID (PK)
        if (!$local) {
            $local = LocalProjeto::with('projeto')->find($cdlocal);
        }

        // Fallback final: tentar como cdlocal (com ou sem projeto)
        if (!$local) {
            $query = LocalProjeto::with('projeto')->where('cdlocal', $cdlocal);
            if ($projeto) {
                $query->where('tabfant_id', $projeto->id);
            }
            $local = $query->first();
        }



        if (!$local) {

            // Existe esse código em outro projeto? Mostrar mensagem clara

            $localOutroProjeto = LocalProjeto::with('projeto')->where('cdlocal', $cdlocal)->first();

            if ($localOutroProjeto) {

                $nomeProjetoOutro = $localOutroProjeto->projeto?->NOMEPROJETO ?? 'desconhecido';

                throw ValidationException::withMessages([

                    'CDLOCAL' => "ERRO: O código de local '{$cdlocal}' existe, mas pertence ao projeto '{$nomeProjetoOutro}'. Selecione um local associado ao projeto escolhido.",

                ]);

            }



            throw ValidationException::withMessages([

                'CDLOCAL' => "Local com código/ID {$cdlocal} não encontrado no sistema.",

            ]);

        }



        // Se o local tem projeto vinculado, mas nenhum projeto foi informado, usar o projeto do local

        if (!$projeto && $local->projeto) {

            $projeto = $local->projeto;

        }



        // Verificação crítica: o local precisa estar ligado ao projeto informado

        if ($projeto) {

            if (!$local->tabfant_id) {

                throw ValidationException::withMessages([

                    'CDLOCAL' => "ERRO: O local '{$local->cdlocal} - {$local->delocal}' não está vinculado a nenhum projeto.",

                ]);

            }



            if ($local->tabfant_id !== $projeto->id) {

                $nomeProjetoSelecionado = $projeto->NOMEPROJETO ?? "Projeto {$cdprojeto}";

                $nomeProjetoDoLocal = $local->projeto ? $local->projeto->NOMEPROJETO : 'desconhecido';

                $codigoLocal = $local->cdlocal ?? $cdlocal;

                $nomeLocal = $local->delocal ?? 'Local sem nome';



                throw ValidationException::withMessages([

                    'CDLOCAL' => "ERRO CRÍTICO: O local '{$codigoLocal} - {$nomeLocal}' NÃO pertence ao projeto '{$nomeProjetoSelecionado}'. " .

                                 "Este local pertence ao projeto '{$nomeProjetoDoLocal}'. " .

                                 "Regra: o projeto define os locais disponíveis. Selecione um local que pertença ao projeto escolhido.",

                ]);

            }

        }



        $codigoProjeto = $projeto ? $projeto->CDPROJETO : 'N/A';



        Log::info("Validação OK [{$operacao}]: Local {$local->cdlocal} ({$local->delocal}) pertence ao projeto {$codigoProjeto}");



        return $local;

    }



}


