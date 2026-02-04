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



        // Aplicar filtros do formulário (Nº Patrimônio, Projeto, Descrição, Situação, Modelo, Cód. Termo, Responsável)

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

                    // procurar usuÃ¡rio por login ou nome e usar matrÃ­cula

                    $usuarioFiltro = User::where('NMLOGIN', $val)->orWhereRaw('LOWER(NOMEUSER) LIKE ?', ['%' . mb_strtolower($val) . '%'])->first();

                    if ($usuarioFiltro) {

                        $query->where('CDMATRFUNCIONARIO', $usuarioFiltro->CDMATRFUNCIONARIO);

                    } else {

                        // fallback: pesquisar por trecho no NOME do funcionÃ¡rio via relação 'funcionario' se existir

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

     * Gera o próximo número sequencial de Patrimônio

     */

    public function proximoNumeroPatrimonio(): JsonResponse

    {

        try {

            $ultimoNumero = Patrimonio::max('NUPATRIMONIO') ?? 0;

            $proximoNumero = $ultimoNumero + 1;



            Log::info('Próximo número de Patrimônio gerado', [

                'ultimo' => $ultimoNumero,

                'proximo' => $proximoNumero

            ]);



            return response()->json([

                'success' => true,

                'numero' => $proximoNumero

            ]);

        } catch (\Throwable $e) {

            Log::error('Erro ao gerar próximo número de Patrimônio: ' . $e->getMessage());

            return response()->json([

                'success' => false,

                'message' => 'Erro ao gerar número de Patrimônio'

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

     * Navigator beta com layout lateral novo e listagem de patrimônios.

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

     * Salva o novo Patrimônio no banco de dados.

     * Regras:

     * - Se NUSEQOBJ (código) não existir em objetopatr, cria um novo registro com DEOBJETO.

     * - Em seguida, cria o Patrimônio referenciando esse código.

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
            // 1) Validar os campos conforme o formulário (nomes em MAIÃSCULO)

            $validated = $request->validate([

                // O Nº Patrimônio pode se repetir entre tipos; removido UNIQUE

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



            //      VERIFICAR DUPLICATAS: Impedir criar Patrimônio com N° que jÃ¡ existe

            $nupatrimonio = (int) $validated['NUPATRIMONIO'];

            $jaExiste = Patrimonio::where('NUPATRIMONIO', $nupatrimonio)->exists();

            if ($jaExiste) {

                throw ValidationException::withMessages([

                    'NUPATRIMONIO' => "Já existe um Patrimônio com o número $nupatrimonio! não é permitido criar duplicatas."

                ]);

            }



            // 2) Garantir existÃªncia do ObjetoPatr (tabela objetopatr)

            //    O Model ObjetoPatr usa PK 'NUSEQOBJ'.
            //    ✅ SUPORTE NULL: Permite patrimônios sem objeto definido

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
                Log::warning('⚠️ [UPDATE] Falha de validação no patrimônio', [
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

        // 3) Criar o Patrimônio associando o código recém-verificado/criado

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

            ->with('success', 'Patrimônio cadastrado com sucesso!');

    }



    /**

     * Mostra o formulário de edição para um Patrimônio específico.

     */

    public function edit(Request $request, Patrimonio $patrimonio): View

    {

        $this->authorize('update', $patrimonio);



        // Carregar relaÃ§Ãµes para exibir dados corretos no formulário
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

     * Atualiza um Patrimônio existente no banco de dados.

     */

    public function update(Request $request, Patrimonio $patrimonio): Response|RedirectResponse

    {

        $this->authorize('update', $patrimonio);



        // Debug: Log de todos os dados recebidos

        Log::info(' [UPDATE] Dados recebidos do formulário', [

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
            $incomingCdLocal = $request->input('CDLOCAL');
            if ($incomingCdLocal === null || $incomingCdLocal === '') {
                $incomingCdLocal = $validatedData['CDLOCAL'] ?? $patrimonio->CDLOCAL;
            }
            $localChanged = (string) $incomingCdLocal !== (string) $patrimonio->CDLOCAL;
            $projetoChanged = (string) $incomingCdProjeto !== (string) $patrimonio->CDPROJETO;

            if ($localChanged || $projetoChanged) {
                //  VALIDACAO CRITICA: Local deve pertencer ao projeto selecionado
                $localSelecionado = $this->validateLocalBelongsToProjeto(
                    $incomingCdProjeto,
                    $incomingCdLocal,
                    'atualizacao de patrimonio'
                );
            }
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



        //  Log dos dados antes da Atualização

        Log::info('Patrimônio UPDATE: Dados antes da Atualização', [

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

            'NUPATRIMONIO_old' => $patrimonio->NUPATRIMONIO,

            'CODOBJETO_old' => $patrimonio->CODOBJETO,

            'DEPATRIMONIO_old' => $patrimonio->DEPATRIMONIO,

            'CDLOCAL_old' => $patrimonio->CDLOCAL,

            'CDPROJETO_old' => $patrimonio->CDPROJETO,

            'CDMATRFUNCIONARIO_old' => $patrimonio->CDMATRFUNCIONARIO,

            'SITUACAO_old' => $patrimonio->SITUACAO,

        ]);

        Log::info('Patrimônio UPDATE: Dados validados para atualizar', [

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

            'validated_data' => $validatedData,

        ]);



        // Detectar alteraÃ§Ãµes relevantes

        $oldProjeto = $patrimonio->CDPROJETO;
        $oldNumero = $patrimonio->NUPATRIMONIO;

        $oldSituacao = $patrimonio->SITUACAO;

        $oldLocal = $patrimonio->CDLOCAL;

        $oldConferido = $this->normalizeConferidoFlag($patrimonio->FLCONFERIDO) ?? 'N';

        $flashMessage = 'Patrimônio atualizado com sucesso!';



        // Debug: Log antes do update

        Log::info(' [UPDATE] Chamando $patrimonio->update()', [

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



        //  Log dos dados apÃ³s a Atualização

        Log::info('Patrimônio UPDATE: Dados apÃ³s a Atualização', [

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

            'NUPATRIMONIO_after' => $patrimonio->NUPATRIMONIO,

            'CODOBJETO_after' => $patrimonio->CODOBJETO,

            'DEPATRIMONIO_after' => $patrimonio->DEPATRIMONIO,

            'CDLOCAL_after' => $newLocal,

            'CDPROJETO_after' => $newProjeto,

            'CDMATRFUNCIONARIO_after' => $patrimonio->CDMATRFUNCIONARIO,

            'SITUACAO_after' => $newSituacao,

        ]);

        // Evitar retorno de dados antigos no formulÃ¡rio (cache da API /api/patrimonios/buscar/{numero})
        foreach (array_filter([
            'patrimonio_id_' . $patrimonio->NUSEQPATR,
            'patrimonio_numero_' . $oldNumero,
            'patrimonio_numero_' . $newNumero,
        ]) as $cacheKey) {
            Cache::forget($cacheKey);
        }



        // Registrar histÃ³rico quando o Local mudar

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

                Log::info('HistÃ³rico LOCAL registrado', [

                    'CDLOCAL_old' => $oldLocal,

                    'CDLOCAL_new' => $newLocal

                ]);

            } catch (\Throwable $e) {

                Log::warning('Falha ao gravar histÃ³rico de local', [

                    'patrimonio' => $patrimonio->NUSEQPATR,

                    'erro' => $e->getMessage()

                ]);

            }

        }



        // Registrar histÃ³rico quando o Projeto mudar

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

                Log::info('HistÃ³rico PROJETO registrado', [

                    'CDPROJETO_old' => $oldProjeto,

                    'CDPROJETO_new' => $newProjeto

                ]);

            } catch (\Throwable $e) {

                Log::warning('Falha ao gravar histÃ³rico de projeto', [

                    'patrimonio' => $patrimonio->NUSEQPATR,

                    'erro' => $e->getMessage()

                ]);

            }

        }



        // Registrar histÃ³rico quando a Situação mudar

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

                Log::info('HistÃ³rico SITUAÃÃO registrado', [

                    'SITUACAO_old' => $oldSituacao,

                    'SITUACAO_new' => $newSituacao

                ]);

            } catch (\Throwable $e) {

                Log::warning('Falha ao gravar histÃ³rico (situaÃ§Ã£o)', [

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

                $flashMessage = 'Patrimônio atualizado e verificado com sucesso!';

            } else {

                $flashMessage = 'Patrimônio atualizado e marcado como não verificado!';

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

     * Remove o Patrimônio do banco de dados.

     */

    public function destroy(Patrimonio $patrimonio)

    {

        \Illuminate\Support\Facades\Log::info('¸ [DESTROY] Iniciando deleção', [

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

            'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,

            'user' => Auth::user()->NMLOGIN ?? 'desconhecido',

            'user_id' => Auth::id(),

        ]);



        try {

            $this->authorize('delete', $patrimonio);

            

            \Illuminate\Support\Facades\Log::info(' [DESTROY] AutorizaÃ§Ã£o concedida', [

                'NUSEQPATR' => $patrimonio->NUSEQPATR,

            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {

            \Illuminate\Support\Facades\Log::error(' [DESTROY] AutorizaÃ§Ã£o negada', [

                'NUSEQPATR' => $patrimonio->NUSEQPATR,

                'erro' => $e->getMessage(),

            ]);

            

            if (request()->expectsJson()) {

                return response()->json([

                    'message' => 'Você não tem permissão para excluir este Patrimônio.',

                    'code' => 'authorization_failed',

                ], 403);

            }

            

            return redirect()->route('patrimonios.index')

                ->with('error', 'Você não tem permissão para excluir este Patrimônio.');

        }

        

        // Log da deleção

        \Illuminate\Support\Facades\Log::info('¾ [DESTROY] Deletando Patrimônio', [

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

            'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,

            'DEPATRIMONIO' => $patrimonio->DEPATRIMONIO,

            'deletado_por' => Auth::user()->NMLOGIN,

            'user_id' => Auth::id()

        ]);

        

        $patrimonio->delete();

        

        \Illuminate\Support\Facades\Log::info(' [DESTROY] Patrimônio deletado com sucesso', [

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

        ]);

        

        if (request()->expectsJson()) {

            return response()->json(['message' => 'Patrimônio deletado com sucesso!'], 204)

                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');

        }

        

        return redirect()->route('patrimonios.index')->with('success', 'Patrimônio deletado com sucesso!');

    }



    /**

     * ¸ NOVO MÃTODO DE DELEÃÃO SIMPLIFICADO

     * MÃ©todo alternativo para deletar Patrimônio por ID direto

     */

    public function deletePatrimonio($id)

    {

        \Illuminate\Support\Facades\Log::info('¸ [DELETE] Requisição recebida', [

            'id' => $id,

            'method' => request()->method(),

            'user' => Auth::user()->NMLOGIN ?? 'guest',

            'user_id' => Auth::id(),

            'ip' => request()->ip()

        ]);



        try {

            // Buscar Patrimônio

            $patrimonio = Patrimonio::where('NUSEQPATR', $id)->first();

            

            if (!$patrimonio) {

                \Illuminate\Support\Facades\Log::warning(' [DELETE] Patrimônio não encontrado', ['id' => $id]);

                return response()->json([

                    'success' => false,

                    'message' => 'Patrimônio não encontrado'

                ], 200);

            }



            \Illuminate\Support\Facades\Log::info(' [DELETE] Patrimônio encontrado', [

                'NUSEQPATR' => $patrimonio->NUSEQPATR,

                'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,

                'DEPATRIMONIO' => $patrimonio->DEPATRIMONIO

            ]);



            $this->authorize('delete', $patrimonio);

            \Illuminate\Support\Facades\Log::info(' [DELETE] AutorizaÃ§Ã£o OK');



            // Salvar dados antes de deletar

            $dadosPatrimonio = [

                'NUSEQPATR' => $patrimonio->NUSEQPATR,

                'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,

                'DEPATRIMONIO' => $patrimonio->DEPATRIMONIO

            ];



            // DELETAR

            $deleted = $patrimonio->delete();

            

            \Illuminate\Support\Facades\Log::info(' [DELETE] Patrimônio deletado!', [

                'resultado' => $deleted,

                'dados' => $dadosPatrimonio

            ]);



            return response()->json([

                'success' => true,

                'message' => 'Patrimônio deletado com sucesso!',

                'patrimonio' => $dadosPatrimonio

            ], 200);



        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {

            \Illuminate\Support\Facades\Log::warning(' [DELETE] AutorizaÃ§Ã£o negada', [

                'id' => $id,

                'erro' => $e->getMessage(),

            ]);



            return response()->json([

                'success' => false,

                'message' => 'VocÃª não tem permissÃ£o para deletar este Patrimônio.',

            ], 403);

        } catch (\Exception $e) {

            \Illuminate\Support\Facades\Log::error(' [DELETE] Erro ao deletar', [

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

     *  Exibe tela de duplicatas - Patrimônios com mesmo número

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



        // Se não hÃ¡ duplicatas, retornar mensagem

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

     * ¸ Deleta um Patrimônio (versÃ£o para duplicatas)

     * Usado na tela de removiÃ§Ã£o de duplicatas

     */

    public function deletarDuplicata(Request $request, Patrimonio $patrimonio): RedirectResponse

    {

        $this->authorize('delete', $patrimonio);



        $numero = $patrimonio->NUPATRIMONIO;

        Log::info('Deletando duplicata de Patrimônio', [

            'NUSEQPATR' => $patrimonio->NUSEQPATR,

            'NUPATRIMONIO' => $numero,

            'deletado_por' => Auth::user()->NMLOGIN

        ]);



        $patrimonio->delete();



        return redirect()->route('patrimonios.duplicatas')

            ->with('success', "Duplicata N° $numero deletada com sucesso!");

    }



    // --- MÃTODOS DE API PARA O FORMULÃRIO DINÃMICO ---



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

            //  VERIFICAR AUTORIZAÃÃO: O usuÃ¡rio pode ver este Patrimônio?
            $user = Auth::user();
            if (!$user) {
                // não autenticado
                return response()->json(['error' => 'não autorizado'], 403);
            }

            // TODOS os usuários autenticados podem ver patrimônio (sem restrição de supervisão)
            return response()->json($patrimonio);
        } catch (\Throwable $e) {
            Log::error('Erro ao buscar Patrimônio por número: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Erro ao buscar Patrimônio'], 500);
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

     * Buscar patrimônio por ID (NUSEQPATR) para modal de consultor

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
                    return response()->json(['success' => false, 'error' => 'Patrimônio não encontrado'], 404);
                }
            }

            if ($patrimonio) {
                $this->attachLocalCorreto($patrimonio);
            }

            if (!$patrimonio) {
                return response()->json(['success' => false, 'error' => 'Patrimônio não encontrado'], 404);
            }

            // TODOS os usuários autenticados podem ver patrimônio (sem restrição de supervisão)
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'error' => 'Não autenticado'], 403);
            }

            return response()->json(['success' => true, 'patrimonio' => $patrimonio]);
        } catch (\Throwable $e) {
            Log::error('🔴 [PATRIMONIOS] Erro buscarPorId: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'error' => 'Erro ao buscar patrimônio'], 500);
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

            return response()->json(['error' => 'Nenhum patrim?nio selecionado.'], 422);

        }



        $situacao = strtoupper($request->input('situacao'));

        /** @var User|null $user */

        $user = Auth::user();

        if ($user && ($user->PERFIL ?? null) === User::PERFIL_CONSULTOR) {

            return response()->json(['error' => 'Você não tem permissão para alterar patrimônios.'], 403);

        }



        $isAdmin = $user && $user->isAdmin();

        

        // Usuários com permissão total para alteração em massa

        $superUsers = ['BEATRIZ.SC', 'TIAGOP', 'BRUNO'];

        $isSuperUser = $user && in_array(strtoupper($user->NMLOGIN ?? ''), $superUsers, true);



        $patrimonios = Patrimonio::whereIn('NUSEQPATR', $ids)->get();

        if ($patrimonios->isEmpty()) {

            return response()->json(['error' => 'Patrim?nios n?o encontrados.'], 404);

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

                'error' => 'Voc? n?o tem permiss?o para alterar todos os itens selecionados.',

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

            return response()->json(['error' => 'Nenhum patrimônio selecionado.'], 422);

        }



        /** @var User|null $user */

        $user = Auth::user();

        // Todos os usuários autenticados podem deletar patrimônios
        // Os patrimônios vão para a tela de removidos para análise pelo Bruno



        $patrimonios = Patrimonio::whereIn('NUSEQPATR', $ids)->get();

        if ($patrimonios->isEmpty()) {

            return response()->json(['error' => 'Patrimônios não encontrados.'], 404);

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



        Log::info('🗑️ Bulk deleção de patrimônios', [

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



    // MÃ©todo pesquisarUsuarios removido apÃ³s migraÃ§Ã£o para FuncionarioController::pesquisar



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

            ->orderByRaw('CAST(CDPROJETO AS UNSIGNED) ASC')  // Ordenação numÃ©rica

            ->get()

            ->toArray();



        // Debug log

        Log::debug('pesquisarProjetos', [

            'termo' => $termo,

            'total_projetos' => count($projetos),

            'primeiros_projetos' => array_slice($projetos, 0, 5),

        ]);



        // Se hÃ¡ termo numÃ©rico, aplicar busca inteligente por magnitude

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

     * Busca projetos por magnitude numÃ©rica

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



            // Verificar se comeÃ§a com o termo

            if (strpos($codigo_str, $termo) === 0) {

                $resultados[] = $projeto;

                continue;

            }



            // Verificar magnitudes (dÃ©cimos, centenas, milhares)

            // DÃ©cimos: 8 -> 80-89

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

            'nome.max' => 'Nome muito longo (mÃ¡ximo 255 caracteres).',

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

                \Illuminate\Support\Facades\Log::warning('Local NÃO criado - dados insuficientes', [

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

     * Busca locais disponÃ­veis por código ou nome

     */

    public function buscarLocais(Request $request): JsonResponse

    {

        $termo = trim($request->input('termo', ''));

        $cdprojeto = trim($request->input('cdprojeto', ''));



        // BUSCAR NA TABELA LOCAIS_PROJETO (tem o cdlocal)

        $query = LocalProjeto::query();



        // Regra: projeto define locais. Sem projeto, só permite fallback quando o termo é um cdlocal específico.

        if ($cdprojeto === '') {

            if ($termo === '' || !is_numeric($termo)) {

                return response()->json([]);

            }

            $query->where('cdlocal', $termo);

        } else {

            $proj = Tabfant::where('CDPROJETO', $cdprojeto)->first(['id', 'CDPROJETO']);

            if (!$proj) {

                return response()->json([]);

            }

            $query->where('tabfant_id', $proj->id);

        }



        $locaisProjeto = $query->get();



        // Buscar informações do projeto na tabfant para cada local

        $locais = $locaisProjeto->map(function ($lp) {

            $tabfant = null;

            if ($lp->tabfant_id) {

                $tabfant = Tabfant::find($lp->tabfant_id);

            }



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



        // Aplicar filtro inteligente

        $filtrados = \App\Services\FilterService::filtrar(

            $locais,

            $termo,

            ['cdlocal', 'delocal'],  // campos de busca

            ['cdlocal' => 'número', 'delocal' => 'texto'],  // tipos de campo

            100  // limite

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



            // Primeiro tenta pelo ID (chave prim?ria)

            $local = LocalProjeto::with('projeto')->find($id);



            // Se o caller informou cdprojeto, nunca retornar local de outro projeto

            if ($local && $cdprojeto) {

                $cdProjetoDoLocal = $local->projeto?->CDPROJETO;

                if (!$cdProjetoDoLocal || (string) $cdProjetoDoLocal !== (string) $cdprojeto) {

                    $local = null;

                }

            }



            // Fallback: algumas telas ainda enviam o c?digo (cdlocal) em vez do ID

            if (!$local) {

                $query = LocalProjeto::with('projeto')->where('cdlocal', $id);



                if ($cdprojeto) {

                    $tabfant = Tabfant::where('CDPROJETO', $cdprojeto)->first();

                    if ($tabfant) {

                        $query->where('tabfant_id', $tabfant->id);

                    } else {

                        return response()->json(['error' => 'Local n?o encontrado'], 404);

                    }

                }



                $local = $query->first();

            }



            if (!$local) {

                return response()->json(['error' => 'Local n?o encontrado'], 404);

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

     * PÃ¡gina dedicada para atribuiÃ§Ã£o de códigos de termo

     */

    public function atribuir(Request $request): View

    {

        $query = Patrimonio::query();



        // Nota: Removido filtro por usuÃ¡rio para que todos os Patrimônios

        // apareÃ§am na tela de atribuiÃ§Ã£o de códigos (requisito de negÃ³cio).



        // Filtro por status - default volta a 'disponivel'

        $status = $request->get('status', 'disponivel');

        Log::info(' Filtro Status: ' . $status);



        if ($status === 'disponivel') {

            // Patrimônios sem código de termo (campo integer => apenas null significa "sem")

            $query->whereNull('NMPLANTA');

        } elseif ($status === 'indisponivel') {

            // Patrimônios com código de termo

            $query->whereNotNull('NMPLANTA');

        }

        // Se status for vazio ou 'todos', não aplica filtro de status



                // ObservaÃ§Ã£o: originalmente excluÃ­amos Patrimônios sem DEPATRIMONIO,

                // mas a regra atual exige que TODOS os Patrimônios cadastrados

                // apareÃ§am na tela de atribuiÃ§Ã£o. Portanto, removemos esse filtro.



        // Aplicar filtros se fornecidos

        if ($request->filled('filtro_numero')) {

            Log::info(' Filtro número: ' . $request->filtro_numero);

            $query->where('NUPATRIMONIO', 'like', '%' . $request->filtro_numero . '%');

        }



        if ($request->filled('filtro_descricao')) {

            Log::info(' Filtro Descrição: ' . $request->filtro_descricao);

            $query->where('DEPATRIMONIO', 'like', '%' . $request->filtro_descricao . '%');

        }



        if ($request->filled('filtro_modelo')) {

            Log::info(' Filtro Modelo: ' . $request->filtro_modelo);

            $query->where('MODELO', 'like', '%' . $request->filtro_modelo . '%');

        }



        // Filtro por projeto para atribuiÃ§Ã£o/termo

        if ($request->filled('filtro_projeto')) {

            Log::info(' Filtro Projeto: ' . $request->filtro_projeto);

            $query->where('CDPROJETO', $request->filtro_projeto);

        }



        // Filtro por termo (apenas na aba atribuidos)

        if ($request->filled('filtro_termo')) {

            Log::info(' Filtro Termo: ' . $request->filtro_termo);

            $query->where('NMPLANTA', $request->filtro_termo);

        }



        // Filtro por matrÃ­cula do responsÃ¡vel (CDMATRFUNCIONARIO)

        if ($request->filled('filtro_matr_responsavel')) {

            Log::info(' Filtro MatrÃ­cula Responsável: ' . $request->filtro_matr_responsavel);

            $query->where('CDMATRFUNCIONARIO', $request->filtro_matr_responsavel);

        }



        // Filtro por matrÃ­cula do cadastrador (USUARIO)

        if ($request->filled('filtro_matr_cadastrador')) {

            Log::info(' Filtro MatrÃ­cula Cadastrador: ' . $request->filtro_matr_cadastrador);

            // Buscar pelo NMLOGIN do usuÃ¡rio que cadastrou

            $query->whereHas('creator', function ($q) use ($request) {

                $q->where('CDMATRFUNCIONARIO', $request->filtro_matr_cadastrador);

            });

        }



        // Ordenação

        $query->orderBy('NMPLANTA', 'asc');

        $query->orderBy('NUPATRIMONIO', 'asc');



        // PaginaÃ§Ã£o configurÃ¡vel

        $perPage = (int) $request->input('per_page', 30);

        if ($perPage < 30) $perPage = 30;

        if ($perPage > 200) $perPage = 200;



        $patrimonios = $query->paginate($perPage);



        Log::info(' Total de Patrimônios apÃ³s filtro: ' . $patrimonios->total() . ' (PÃ¡gina ' . $patrimonios->currentPage() . ')');

        Log::info(' Patrimônios nesta pÃ¡gina: ' . count($patrimonios));



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

            // Prioridade: DEPATRIMONIO (campo), depois DEOBJETO via CODOBJETO, senão compor por Marca/Modelo/SÃ©rie

            $display = $p->DEPATRIMONIO ?: ($descMap[$p->CODOBJETO] ?? null);

            if (empty($display)) {

                $parts = array_filter([$p->MODELO ?? null, $p->MARCA ?? null, $p->NUSERIE ?? null, $p->COR ?? null]);

                $display = $parts ? implode(' - ', $parts) : null;

            }

            $p->DEPATRIMONIO = $display ?: '-';

        }



        // Agrupar por NMPLANTA para exibiÃ§Ã£o

        $patrimonios_grouped = $patrimonios->groupBy(function ($item) {

            return $item->NMPLANTA ?? '__sem_termo__';

        });



        return view('patrimonios.atribuir', compact('patrimonios', 'patrimonios_grouped'));

    }



    /**

     * PÃ¡gina isolada (clonada) para atribuiÃ§Ã£o de códigos de termo.

     * Reaproveita a mesma lÃ³gica de filtragem da pÃ¡gina principal para manter consistÃªncia.

     */

    public function atribuirCodigos(Request $request): View

    {

        $query = Patrimonio::query();



        // Nota: Removido filtro por usuÃ¡rio para que todos os Patrimônios

        // apareÃ§am na pÃ¡gina de atribuiÃ§Ã£o de códigos (requisito do produto).



        $status = $request->get('status', 'disponivel');

        Log::info('[atribuirCodigos]  Filtro Status: ' . $status);



        if ($status === 'disponivel') {

            $query->whereNull('NMPLANTA');

        } elseif ($status === 'indisponivel') {

            $query->whereNotNull('NMPLANTA');

        }



        if ($request->filled('filtro_numero')) {

            Log::info('[atribuirCodigos]  Filtro número: ' . $request->filtro_numero);

            $query->where('NUPATRIMONIO', 'like', '%' . $request->filtro_numero . '%');

        }

        if ($request->filled('filtro_descricao')) {

            Log::info('[atribuirCodigos]  Filtro Descrição: ' . $request->filtro_descricao);

            $query->where('DEPATRIMONIO', 'like', '%' . $request->filtro_descricao . '%');

        }

        if ($request->filled('filtro_modelo')) {

            Log::info('[atribuirCodigos]  Filtro Modelo: ' . $request->filtro_modelo);

            $query->where('MODELO', 'like', '%' . $request->filtro_modelo . '%');

        }

        if ($request->filled('filtro_projeto')) {

            Log::info('[atribuirCodigos]  Filtro Projeto: ' . $request->filtro_projeto);

            $query->where('CDPROJETO', $request->filtro_projeto);

        }

        if ($request->filled('filtro_termo')) {

            Log::info('[atribuirCodigos]  Filtro Termo: ' . $request->filtro_termo);

            $query->where('NMPLANTA', $request->filtro_termo);

        }



        $query->orderBy('NMPLANTA', 'asc');

        $query->orderBy('NUPATRIMONIO', 'asc');

        $perPage = (int) $request->input('per_page', 30);

        if ($perPage < 30) $perPage = 30;

        if ($perPage > 200) $perPage = 200;

        $patrimonios = $query->paginate($perPage);



        Log::info('[atribuirCodigos]  Total de Patrimônios apÃ³s filtro: ' . $patrimonios->total() . ' (PÃ¡gina ' . $patrimonios->currentPage() . ')');

        Log::info('[atribuirCodigos]  Patrimônios nesta pÃ¡gina: ' . count($patrimonios));



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



        // Agrupar por NMPLANTA para exibiÃ§Ã£o

        $patrimonios_grouped = $patrimonios->groupBy(function ($item) {

            return $item->NMPLANTA ?? '__sem_termo__';

        });



        // Reutiliza a mesma view principal de atribuiÃ§Ã£o; evita duplicaÃ§Ã£o e problemas de alias

        return view('patrimonios.atribuir', compact('patrimonios', 'patrimonios_grouped'));

    }



    /**

     * Processar a atribuiÃ§Ã£o/desatribuiÃ§Ã£o de códigos de termo

     */

    public function processarAtribuicao(Request $request): RedirectResponse

    {

        // Verificar autorizaÃ§Ã£o de atribuiÃ§Ã£o

        $this->authorize('atribuir', Patrimonio::class);



        // Verificar se Ã© uma operaÃ§Ã£o de desatribuiÃ§Ã£o

        if ($request->filled('desatribuir')) {

            return $this->processarDesatribuicao($request);

        }

        // ValidaÃ§Ã£o condicional (caso envie código manualmente ainda funciona, mas não Ã© mais o fluxo principal)

        $rules = [

            'patrimonios' => 'required|array|min:1',

            'patrimonios.*' => 'integer|exists:PATR,NUSEQPATR'

        ];

        if ($request->filled('codigo_termo')) {

            $rules['codigo_termo'] = 'required|integer|min:1';

        }



        // Log para verificar se o campo ids (ou patrimonios) estÃ¡ faltando ou vazio

        $fieldName = $request->has('ids') ? 'ids' : 'patrimonios';

        if (!$request->has($fieldName) || empty($request->input($fieldName))) {

            Log::warning('Erro de validaÃ§Ã£o: campo de Patrimônios obrigatÃ³rio não foi preenchido', [

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



        // Se recebeu 'ids' ao invÃ©s de 'patrimonios', renomear para validaÃ§Ã£o consistente

        if ($request->has('ids') && !$request->has('patrimonios')) {

            $request->merge(['patrimonios' => $request->input('ids')]);

        }



        $request->validate($rules);



        try {

            $patrimoniosIds = $request->patrimonios;



            // Novo fluxo: se não veio um código explÃ­cito, o sistema determina automaticamente.

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

                    // registra para manter histÃ³rico de códigos gerados

                    TermoCodigo::firstOrCreate([

                        'codigo' => $codigoTermo

                    ], [

                        'created_by' => (Auth::user()->NMLOGIN ?? 'SISTEMA')

                    ]);

                }

            }



            // Verificar quais Patrimônios jÃ¡ estÃ£o atribuÃ­dos

            $jaAtribuidos = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)

                ->whereNotNull('NMPLANTA')

                ->count();



            // Atualizar apenas os Patrimônios disponÃ­veis

            $updated = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)

                ->whereNull('NMPLANTA')

                ->update(['NMPLANTA' => $codigoTermo]);



            $message = "Código de termo {$codigoTermo} atribuÃ­do a {$updated} Patrimônio(s) com sucesso!";



            // Log detalhado quando a mensagem de sucesso/erro Ã© exibida

            Log::info('AtribuiÃ§Ã£o de Termo Processada', [

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



            // HistÃ³rico de atribuiÃ§Ã£o de termo

            if ($updated > 0) {

                try {

                    $patrimoniosAlterados = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)->get(['NUPATRIMONIO']);

                    foreach ($patrimoniosAlterados as $p) {

                        $coAutor = null;

                        $actorMat = Auth::user()->CDMATRFUNCIONARIO ?? null;

                        // Aqui não temos o dono do Patrimônio carregado; buscar rapidamente

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

                    Log::warning('Falha ao gravar histÃ³rico atribuiÃ§Ã£o de termo', ['erro' => $e->getMessage()]);

                }

            }



            if ($jaAtribuidos > 0) {

                $message .= " ({$jaAtribuidos} Patrimônio(s) jÃ¡ estavam atribuÃ­dos e foram ignorados)";

            }



            return redirect()->route('patrimonios.atribuir.codigos', ['status' => 'indisponivel'])

                ->with('success', $message);

        } catch (\Exception $e) {

            Log::error('Erro ao processar atribuiÃ§Ã£o de termo: ' . $e->getMessage());

            return redirect()->route('patrimonios.atribuir.codigos')

                ->with('error', 'Erro ao processar atribuiÃ§Ã£o. Tente novamente.');

        }

    }



    /**

     * Processar desatribuiÃ§Ã£o de códigos de termo

     */

    private function processarDesatribuicao(Request $request): RedirectResponse

    {

        // Verificar autorizaÃ§Ã£o de desatribuiÃ§Ã£o

        $this->authorize('desatribuir', Patrimonio::class);



        // Log para verificar se o campo ids (ou patrimonios) estÃ¡ faltando ou vazio

        $fieldName = $request->has('ids') ? 'ids' : 'patrimonios';

        if (!$request->has($fieldName) || empty($request->input($fieldName))) {

            Log::warning('Erro de validaÃ§Ã£o: campo de Patrimônios obrigatÃ³rio não foi preenchido (desatribuiÃ§Ã£o)', [

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



        // Se recebeu 'ids' ao invÃ©s de 'patrimonios', renomear para validaÃ§Ã£o consistente

        if ($request->has('ids') && !$request->has('patrimonios')) {

            $request->merge(['patrimonios' => $request->input('ids')]);

        }



        $request->validate([

            'patrimonios' => 'required|array|min:1',

            'patrimonios.*' => 'integer|exists:PATR,NUSEQPATR'

        ]);



        try {

            $patrimoniosIds = $request->patrimonios;



            // Buscar informações antes da desatribuiÃ§Ã£o para feedback

            $patrimonio = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)->first();

            $codigoAnterior = $patrimonio ? $patrimonio->NMPLANTA : 'N/A';



            // Desatribuir (limpar campo NMPLANTA)

            $updated = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)

                ->whereNotNull('NMPLANTA')

                ->update(['NMPLANTA' => null]);



            if ($updated > 0) {

                // HistÃ³rico de desatribuiÃ§Ã£o de termo

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

                    Log::warning('Falha ao gravar histÃ³rico desatribuiÃ§Ã£o de termo', ['erro' => $e->getMessage()]);

                }

                return redirect()->route('patrimonios.atribuir')

                    ->with('success', "Código de termo {$codigoAnterior} removido de {$updated} Patrimônio(s) com sucesso!");

            } else {

                return redirect()->route('patrimonios.atribuir')

                    ->with('warning', 'Nenhum Patrimônio foi desatribuÃ­do. Verifique se os Patrimônios selecionados possuem código de termo.');

            }

        } catch (\Exception $e) {

            Log::error('Erro ao processar desatribuiÃ§Ã£o de termo: ' . $e->getMessage());

            return redirect()->route('patrimonios.atribuir')

                ->with('error', 'Erro ao processar desatribuiÃ§Ã£o. Tente novamente.');

        }

    }



    /**

     * ¯ API: Retorna lista de cadastradores disponÃ­veis para filtro multi-select

     * Retorna usuÃ¡rios ativos + SISTEMA

     */

    public function listarCadradores(Request $request): JsonResponse

    {

        try {

            /** @var \App\Models\User $user */

            $user = Auth::user();



            $cadastradores = [];



            // SISTEMA (sempre disponÃ­vel)

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



            Log::info(' [API] Listar cadastradores executado', [

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



    // --- MÃTODOS AUXILIARES ---



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

            // Se vier como string separada por vÃ­rgula, converter para array

            $cadastradoresMulti = array_filter(array_map('trim', explode(',', $cadastradoresMulti)));

        }



        if (!empty($cadastradoresMulti)) {

            Log::info('¯ [FILTRO MULTI] Cadastradores mÃºltiplos solicitados', [

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

                Log::info('¯ [FILTRO MULTI] Aplicando filtro com usuÃ¡rios permitidos', [

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

        Log::info(' [FILTROS] Antes de aplicar filtros', [

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

                    Log::info(' [FILTRO] nupatrimonio aplicado (INT)', ['val' => $intVal]);

                    $query->where('NUPATRIMONIO', $intVal);

                } else {

                    Log::info(' [FILTRO] nupatrimonio aplicado (LIKE)', ['val' => $val]);

                    $query->whereRaw('LOWER(CAST(NUPATRIMONIO AS CHAR)) LIKE ?', ['%' . mb_strtolower($val) . '%']);

                }

            } else {

                Log::info(' ¸  [FILTRO] nupatrimonio vazio (não aplicado)');

            }

        }



        if ($request->filled('cdprojeto')) {

            $val = trim((string)$request->input('cdprojeto'));

            if ($val !== '') {

                Log::info(' [FILTRO] cdprojeto aplicado', ['val' => $val]);

                $query->where(function($q) use ($val) {

                    $q->where('CDPROJETO', $val)

                      ->orWhereHas('local.projeto', function($q2) use ($val) {

                          $q2->where('CDPROJETO', $val);

                      });

                });

            } else {

                Log::info(' ¸  [FILTRO] cdprojeto vazio (não aplicado)');

            }

        }



        if ($request->filled('descricao')) {

            $val = trim((string)$request->input('descricao'));

            if ($val !== '') {

                $like = '%' . mb_strtolower($val) . '%';

                Log::info(' [FILTRO] descricao aplicado', ['val' => $val]);

                $query->whereRaw('LOWER(DEPATRIMONIO) LIKE ?', [$like]);

            } else {

                Log::info(' ¸  [FILTRO] descricao vazio (não aplicado)');

            }

        }



        if ($request->filled('situacao')) {

            $val = trim((string)$request->input('situacao'));

            if ($val !== '') {

                Log::info(' [FILTRO] situacao aplicado', ['val' => $val]);

                $query->where('SITUACAO', $val);

            } else {

                Log::info(' ¸  [FILTRO] situacao vazio (não aplicado)');

            }

        }



        if ($request->filled('modelo')) {

            $val = trim((string)$request->input('modelo'));

            if ($val !== '') {

                Log::info(' [FILTRO] modelo aplicado', ['val' => $val]);

                $query->whereRaw('LOWER(MODELO) LIKE ?', ['%' . mb_strtolower($val) . '%']);

            } else {

                Log::info(' ¸  [FILTRO] modelo vazio (não aplicado)');

            }

        }



        if ($request->filled('nmplanta')) {

            $val = trim((string)$request->input('nmplanta'));

            if ($val !== '') {

                Log::info(' [FILTRO] nmplanta aplicado', ['val' => $val]);

                $query->where('NMPLANTA', $val);

            } else {

                Log::info(' ¸  [FILTRO] nmplanta vazio (não aplicado)');

            }

        }



        if ($request->filled('matr_responsavel')) {

            $val = trim((string)$request->input('matr_responsavel'));

            if ($val !== '') {

                Log::info(' [FILTRO] matr_responsavel aplicado', ['val' => $val]);

                if (is_numeric($val)) {

                    $query->where('CDMATRFUNCIONARIO', $val);

                } else {

                    $usuarioFiltro = User::where('NMLOGIN', $val)->orWhereRaw('LOWER(NOMEUSER) LIKE ?', ['%' . mb_strtolower($val) . '%'])->first();

                    if ($usuarioFiltro) {

                        Log::info('¤ [FILTRO] matr_responsavel encontrado usuÃ¡rio', ['cdmatr' => $usuarioFiltro->CDMATRFUNCIONARIO, 'nmlogin' => $usuarioFiltro->NMLOGIN]);

                        $query->where('CDMATRFUNCIONARIO', $usuarioFiltro->CDMATRFUNCIONARIO);

                    } else {

                        Log::info(' [FILTRO] matr_responsavel usuÃ¡rio NÃO encontrado', ['val' => $val]);

                        $query->whereHas('funcionario', function($q) use ($val) {

                            $q->whereRaw('LOWER(NOMEFUNCIONARIO) LIKE ?', ['%' . mb_strtolower($val) . '%']);

                        });

                    }

                }

            } else {

                Log::info(' ¸  [FILTRO] matr_responsavel vazio (não aplicado)');

            }

        }





        // Filtro de UF (multi-select)

        // REGRA baseada em dados reais:

        // 1º) Se patr.UF está preenchido → usa direto

        // 2º) Se patr.UF é NULL → busca projeto.UF → local.UF → fallback 'SC' se SEDE

        if ($request->filled('uf')) {

            $ufs = $request->input('uf', []);

            if (is_string($ufs)) {

                $ufs = array_filter(array_map('trim', explode(',', $ufs)));

            }

            $ufs = array_filter($ufs);



            if (!empty($ufs)) {

                Log::info('🗺️ [FILTRO] UF aplicado', ['ufs' => $ufs]);

                

                $query->where(function($q) use ($ufs) {

                    // PRIORIDADE 1: UF diretamente na tabela patr

                    $q->whereIn('UF', $ufs)

                    

                    // OU (para patrimônios com patr.UF = NULL):

                    ->orWhere(function($q2) use ($ufs) {

                        // Garantir que patr.UF é NULL

                        $q2->whereNull('UF')

                        

                        ->where(function($q3) use ($ufs) {

                            // PRIORIDADE 2: UF do projeto (via local.projeto)

                            $q3->whereHas('local.projeto', function($q4) use ($ufs) {

                                $q4->whereIn('UF', $ufs);

                            })

                            

                            // OU PRIORIDADE 3: UF do local (quando projeto não tem UF)

                            ->orWhere(function($q4) use ($ufs) {

                                $q4->whereHas('local', function($q5) use ($ufs) {

                                    $q5->whereIn('UF', $ufs);

                                })

                                // E projeto não tem UF

                                ->whereDoesntHave('local.projeto', function($q5) {

                                    $q5->whereNotNull('UF')->where('UF', '!=', '');

                                });

                            });

                            

                            // PRIORIDADE 4: Fallback SC para SEDE (somente se 'SC' está nos filtros)

                            if (in_array('SC', $ufs)) {

                                $q3->orWhere(function($q4) {

                                    // Patrimônio do projeto SEDE (8)

                                    $q4->where(function($q5) {

                                        $q5->where('CDPROJETO', '8')

                                           ->orWhereHas('local.projeto', function($q6) {

                                               $q6->where('CDPROJETO', '8');

                                           });

                                    })

                                    // E projeto não tem UF

                                    ->whereDoesntHave('local.projeto', function($q5) {

                                        $q5->whereNotNull('UF')->where('UF', '!=', '');

                                    })

                                    // E local não tem UF

                                    ->whereDoesntHave('local', function($q5) {

                                        $q5->whereNotNull('UF')->where('UF', '!=', '');

                                    });

                                });

                            }

                        });

                    });

                });

            } else {

                Log::info('⚠️  [FILTRO] UF vazio (não aplicado)');

            }

        }

        Log::info(' [QUERY] SQL gerada', [

            'sql' => $query->toSql(),

            'bindings' => $query->getBindings(),

        ]);



        // Priorizar lanÃ§amentos do usuÃ¡rio autenticado no topo, depois ordenar por DTOPERACAO desc

        try {

            $nmLogin = (string) ($user->NMLOGIN ?? '');

            $cdMatr = $user->CDMATRFUNCIONARIO ?? null;

            // CASE: 0 para registros do usuÃ¡rio (por login ou matrÃ­cula), 1 para outros

            $query->orderByRaw("CASE WHEN LOWER(USUARIO) = LOWER(?) OR CDMATRFUNCIONARIO = ? THEN 0 ELSE 1 END", [$nmLogin, $cdMatr]);

            $query->orderBy('DTOPERACAO', 'desc');

        } catch (\Throwable $e) {

            // se algo falhar, não interromper; continuar com Ordenação padrÃ£o

            Log::warning('Falha ao aplicar Ordenação por usuÃ¡rio/DTOPERACAO: ' . $e->getMessage());

        }



        // Permitir ordenar tambÃ©m por DTAQUISICAO (ordena apÃ³s a prioridade do usuÃ¡rio)

        $sortableColumns = ['NUPATRIMONIO', 'MODELO', 'DEPATRIMONIO', 'SITUACAO', 'DTAQUISICAO'];

        $sortColumn = $request->input('sort', 'DTAQUISICAO');

        $sortDirection = $request->input('direction', 'asc');

        if (in_array($sortColumn, $sortableColumns)) {

            $query->orderBy($sortColumn, $sortDirection);

        } else {

            // Ordenação padrÃ£o por data de aquisiÃ§Ã£o crescente

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



            // Query para Patrimônios disponÃ­veis (sem termo atribuÃ­do ou conforme regra de negÃ³cio)

            $query = Patrimonio::with(['funcionario'])

                ->whereNull('NMPLANTA') // Sem código de termo

                ->orWhere('NMPLANTA', '') // Ou código vazio

                ->orderBy('NUPATRIMONIO', 'asc');



            // Nota: Removido filtro de seguranÃ§a que restringia Patrimônios

            // para não-admins. Todos os Patrimônios serÃ£o retornados para a

            // listagem de disponibilidade/atribuiÃ§Ã£o conforme regra de negÃ³cio.



            // Paginar manualmente

            $total = $query->count();

            $patrimonios = $query->skip(($page - 1) * $perPage)

                ->take($perPage)

                ->get();



            return response()->json([

                'data' => $patrimonios->map(function ($p) use ($patrimonios) {

                        // Definir texto de exibiÃ§Ã£o com prioridade: DEPATRIMONIO -> MODELO -> MARCA -> OBJETO(DEOBJETO) -> fallback

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

                            // Ãltimo fallback: tentar juntar campos menores (número sÃ©rie, cor) ou usar texto padrÃ£o

                            $parts = array_filter([$p->NUSERIE ?? null, $p->COR ?? null]);

                            $displayText = $parts ? implode(' - ', $parts) : '-';

                            $displaySource = $parts ? 'COMPOSITE' : 'FALLBACK';

                        }



                        return [

                            'NUSEQPATR' => $p->NUSEQPATR,

                            'NUPATRIMONIO' => $p->NUPATRIMONIO,

                            // DEPATRIMONIO entregue como texto amigÃ¡vel de exibiÃ§Ã£o (nunca vazio)

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

        //  Debug inicial

        Log::info(' [VALIDATE] InÃ­cio da validaÃ§Ã£o', [

            'request_all' => $request->all(),

        ]);



        // 1) Validar campos bÃ¡sicos; aceitar tanto o fluxo novo (NUSEQOBJ/DEOBJETO)

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

        Log::info(' [VALIDATE] Dados apÃ³s validaÃ§Ã£o inicial', [

            'data' => $data,

        ]);



        // 2) Resolver o código do objeto a partir de NUSEQOBJ (preferencial) ou CODOBJETO (fallback)

        $codigoInput = $request->input('NUSEQOBJ', $request->input('CODOBJETO'));

        // Se não informar código, permitir NULL (patrimônios com objeto indefinido)
        if ($codigoInput === null || $codigoInput === '') {
            $codigoInput = null;
        }

        if ($codigoInput !== null && !is_numeric($codigoInput)) {

            throw ValidationException::withMessages([

                'NUSEQOBJ' => 'O código do objeto deve ser numÃ©rico.'

            ]);

        }

        $codigo = $codigoInput !== null ? (int) $codigoInput : null;



        // 3) Garantir existÃªncia do registro em OBJETOPATR (se código informado)

        $objeto = null;
        if ($codigo !== null) {
            $objeto = ObjetoPatr::find($codigo);

            if (!$objeto) {
                $descricao = trim((string) $request->input('DEOBJETO', ''));
                $isSameCodigoAtual = $patrimonio && (string) $patrimonio->CODOBJETO === (string) $codigo;

                // Em modo UPDATE, não bloquear alterações simples (ex.: SITUACAO) por causa de legado
                // onde o código existe em PATR mas ainda não está em OBJETOPATR.
                if ($descricao === '') {
                    if (!$isSameCodigoAtual) {
                        throw ValidationException::withMessages([
                            'DEOBJETO' => 'Informe a descrição do novo código.',
                        ]);
                    }

                    Log::warning('⚠️ [VALIDATE] Código do objeto não encontrado em OBJETOPATR; mantendo legado sem criar', [
                        'NUSEQPATR' => $patrimonio?->NUSEQPATR,
                        'NUPATRIMONIO' => $patrimonio?->NUPATRIMONIO,
                        'CODOBJETO' => $codigo,
                    ]);
                } else {
                    $objeto = ObjetoPatr::create([
                        'NUSEQOBJ' => $codigo,
                        'DEOBJETO' => $descricao,
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



        Log::info(' [VALIDATE] ApÃ³s mapear código do objeto', [

            'CODOBJETO' => $data['CODOBJETO'],

            'DEPATRIMONIO' => $data['DEPATRIMONIO'] ?? null,

        ]);



        // 5) Sincroniza??o projeto-local: alinhar projeto e gravar o cdlocal (n?mero do local)
        $shouldValidateLocal = true;
        if ($patrimonio) {
            $incomingCdProjeto = $data['CDPROJETO'] ?? $patrimonio->CDPROJETO;
            $incomingCdLocal = $data['CDLOCAL'] ?? $patrimonio->CDLOCAL;
            $shouldValidateLocal =
                (string) $incomingCdLocal !== (string) $patrimonio->CDLOCAL
                || (string) $incomingCdProjeto !== (string) $patrimonio->CDPROJETO;
        }

        if ($shouldValidateLocal && !empty($data['CDLOCAL'])) {
            $localProjeto = $this->validateLocalBelongsToProjeto(
                $data['CDPROJETO'] ?? null,
                $data['CDLOCAL'],
                'atualiza??o de patrim?nio'
            );

            if ($localProjeto) {
                $data['CDLOCAL'] = (int) $localProjeto->cdlocal;
                if ($localProjeto->projeto) {
                    $data['CDPROJETO'] = (int) $localProjeto->projeto->CDPROJETO;
                }
            }
        }



        Log::info('[VALIDATE] Dados finais que ser?o retornados', [

            'final_data' => $data,

        ]);



        return $data;

    }



    /* === Rotas solicitadas para geraÃ§Ã£o e atribuiÃ§Ã£o direta de códigos (fluxo simplificado) === */

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

        // Aceita código numÃ©rico vindo como number ou string

        $request->validate([

            'code' => 'required', // pode vir number no JSON, entÃ£o não restringimos a string

            'ids' => 'required|array|min:1',

            'ids.*' => 'integer'

        ]);

        try {

            $codigo = (int) $request->input('code');

            if ($codigo <= 0) {

                return response()->json(['message' => 'Código invÃ¡lido'], 422);

            }

            $resultado = $service->atribuirCodigo($codigo, $request->ids);

            if ($resultado['already_used']) {

                return response()->json(['message' => 'Código jÃ¡ utilizado'], 422);

            }

            return response()->json([

                'code' => $resultado['code'],

                'updated_ids' => $resultado['updated'],

                'message' => 'AtribuÃ­do.'

            ]);

        } catch (\Throwable $e) {

            Log::error('Falha atribuirCodigo', ['erro' => $e->getMessage()]);

            return response()->json(['message' => 'Erro ao atribuir código'], 500);

        }

    }



    /**

     * Desatribui (remove) o código de termo de uma lista de Patrimônios (API JSON usada na pÃ¡gina de atribuiÃ§Ã£o)

     */

    public function desatribuirCodigo(Request $request): JsonResponse

    {

        $request->validate([

            'ids' => 'required|array|min:1',

            'ids.*' => 'integer'

        ]);

        try {

            $ids = $request->input('ids', []);

            // Seleciona Patrimônios que realmente tÃªm código para evitar updates desnecessÃ¡rios

            $patrimonios = Patrimonio::whereIn('NUSEQPATR', $ids)->whereNotNull('NMPLANTA')->get(['NUSEQPATR', 'NUPATRIMONIO', 'NMPLANTA', 'CDMATRFUNCIONARIO']);

            if ($patrimonios->isEmpty()) {

                return response()->json(['message' => 'Nenhum Patrimônio elegÃ­vel para desatribuir', 'updated_ids' => []], 200);

            }

            $idsParaUpdate = $patrimonios->pluck('NUSEQPATR')->all();

            Patrimonio::whereIn('NUSEQPATR', $idsParaUpdate)->update(['NMPLANTA' => null]);



            // HistÃ³rico

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

                    Log::warning('Falha histÃ³rico desatribuirCodigo', ['id' => $p->NUSEQPATR, 'erro' => $e->getMessage()]);

                }

            }



            return response()->json([

                'message' => 'DesatribuiÃ§Ã£o concluÃ­da',

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

            'cdlocal.required' => 'Código do local Ã© obrigatÃ³rio.',

            'delocal.required' => 'Nome do local Ã© obrigatÃ³rio.',

        ]);



        try {

            $cdlocal = $request->input('cdlocal');

            $delocal = $request->input('delocal');

            $nomeProjeto = $request->input('projeto');



            // Verificar se jÃ¡ existe local com esse código

            $localExistente = LocalProjeto::where('cdlocal', $cdlocal)->first();

            if ($localExistente) {

                return response()->json([

                    'success' => false,

                    'message' => 'JÃ¡ existe um local com este código.'

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

     * Usado no modal de criar local do formulário de Patrimônio.

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

                'local.required' => 'Nome do local Ã© obrigatÃ³rio.',

                'cdprojeto.required' => 'Código do projeto Ã© obrigatÃ³rio.',

                'cdlocal.required' => 'Código do local base Ã© obrigatÃ³rio.',

            ]);



            if ($validator->fails()) {

                Log::warning('ValidaÃ§Ã£o falhou', ['erros' => $validator->errors()->toArray()]);

                return response()->json([

                    'success' => false,

                    'message' => 'Erro de validaÃ§Ã£o.',

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

            'nome.required' => 'Nome do projeto Ã© obrigatÃ³rio.',

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



                    // Verificar se o local jÃ¡ existe

                    $localExistente = LocalProjeto::where('cdlocal', $cdlocal)->first();



                    if ($localExistente) {

                        // Criar nova associaÃ§Ã£o local-projeto (permitir mÃºltiplos projetos por local)

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

     * Cria local e/ou projeto baseado nos dados do formulário de Patrimônio.

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

                'cdlocal.required' => 'Código do local Ã© obrigatÃ³rio',

                'nomeLocal.max' => 'Nome do local muito longo (mÃ¡ximo 255 caracteres)',

                'nomeProjeto.max' => 'Nome do projeto muito longo (mÃ¡ximo 255 caracteres)',

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

            // Se foi fornecido nome do local, criar apenas se NÃO houver projeto

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



            // Se foi criado um projeto, SEMPRE criar uma nova entrada na tabela locais_projeto para a associaÃ§Ã£o

            if ($projeto) {

                // Pegar o nome do local - prioridade: nomeLocal > nomeLocalAtual > "Local {cdlocal}"

                $nomeLocalParaAssociacao = $nomeLocal ?: ($nomeLocalAtual ?: "Local {$cdlocal}");



                // Criar apenas a associaÃ§Ã£o local-projeto

                $local = LocalProjeto::create([

                    'cdlocal' => $cdlocal,

                    'delocal' => $nomeLocalParaAssociacao,

                    'tabfant_id' => $projeto->id,

                    'flativo' => true,

                ]);



                \Illuminate\Support\Facades\Log::info('Nova associaÃ§Ã£o local-projeto criada:', [

                    'id' => $local->id,

                    'cdlocal' => $local->cdlocal,

                    'delocal' => $local->delocal,

                    'tabfant_id' => $local->tabfant_id,

                    'projeto_codigo' => $projeto->CDPROJETO,

                    'projeto_nome' => $projeto->NOMEPROJETO

                ]);

            }



            DB::commit();



            \Illuminate\Support\Facades\Log::info('CriaÃ§Ã£o finalizada com sucesso:', [

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

                'message' => 'Dados invÃ¡lidos: ' . implode(', ', $e->validator->errors()->all())

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

     * Regras de negÃ³cio para almoxarifado central (999915) e em trÃ¢nsito (2002) na criaÃ§Ã£o.

     */

    



    /**

     * Regras de neg?cio para almoxarifado central (999915) e em tr?nsito (2002) na cria??o.

     */

    private function enforceAlmoxRulesOnCreate($cdlocal): void

    {

        // ✅ Validações de almoxarifado removidas - BEATRIZ.SC e TIAGOP podem criar normalmente

        // Ambos podem criar em qualquer local sem restrições

        return;

    }



    /**

     * Regras de neg?cio para almoxarifado central (999915) e em tr?nsito (2002) na edi??o.

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

     * Resolve o c?digo (cdlocal) a partir do ID do LocalProjeto.

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

                    'CDPROJETO' => "Projeto com c?digo {$cdprojeto} n?o encontrado no sistema.",

                ]);

            }

        }



        // Preferir busca por c?digo (cdlocal) dentro do projeto quando CDPROJETO informado.
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

            // Existe esse c?digo em outro projeto? Mostrar mensagem clara

            $localOutroProjeto = LocalProjeto::with('projeto')->where('cdlocal', $cdlocal)->first();

            if ($localOutroProjeto) {

                $nomeProjetoOutro = $localOutroProjeto->projeto?->NOMEPROJETO ?? 'desconhecido';

                throw ValidationException::withMessages([

                    'CDLOCAL' => "ERRO: O c?digo de local '{$cdlocal}' existe, mas pertence ao projeto '{$nomeProjetoOutro}'. Selecione um local associado ao projeto escolhido.",

                ]);

            }



            throw ValidationException::withMessages([

                'CDLOCAL' => "Local com c?digo/ID {$cdlocal} n?o encontrado no sistema.",

            ]);

        }



        // Se o local tem projeto vinculado, mas nenhum projeto foi informado, usar o projeto do local

        if (!$projeto && $local->projeto) {

            $projeto = $local->projeto;

        }



        // Verifica??o cr?tica: o local precisa estar ligado ao projeto informado

        if ($projeto) {

            if (!$local->tabfant_id) {

                throw ValidationException::withMessages([

                    'CDLOCAL' => "ERRO: O local '{$local->cdlocal} - {$local->delocal}' n?o est? vinculado a nenhum projeto.",

                ]);

            }



            if ($local->tabfant_id !== $projeto->id) {

                $nomeProjetoSelecionado = $projeto->NOMEPROJETO ?? "Projeto {$cdprojeto}";

                $nomeProjetoDoLocal = $local->projeto ? $local->projeto->NOMEPROJETO : 'desconhecido';

                $codigoLocal = $local->cdlocal ?? $cdlocal;

                $nomeLocal = $local->delocal ?? 'Local sem nome';



                throw ValidationException::withMessages([

                    'CDLOCAL' => "ERRO CR?TICO: O local '{$codigoLocal} - {$nomeLocal}' N?O pertence ao projeto '{$nomeProjetoSelecionado}'. " .

                                 "Este local pertence ao projeto '{$nomeProjetoDoLocal}'. " .

                                 "Regra: o projeto define os locais dispon?veis. Selecione um local que perten?a ao projeto escolhido.",

                ]);

            }

        }



        $codigoProjeto = $projeto ? $projeto->CDPROJETO : 'N/A';



        Log::info("Validação OK [{$operacao}]: Local {$local->cdlocal} ({$local->delocal}) pertence ao projeto {$codigoProjeto}");



        return $local;

    }



}



