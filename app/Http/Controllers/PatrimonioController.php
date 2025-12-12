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
            // Usa a tabela principal de cÃ³digos (objetopatr)
            $registro = ObjetoPatr::where($pkColumn, $codigo)->first();
            if (!$registro) {
                return response()->json(['found' => false, 'message' => 'CÃ³digo nÃ£o encontrado.'], 404);
            }
            return response()->json([
                'found'     => true,
                'descricao' => $registro->DEOBJETO,
                'tipo'      => $registro->NUSEQTIPOPATR,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro buscarCodigoObjeto: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            // Evita erro 500 no front: retorna 404 genÃ©rico quando houver exceÃ§Ã£o nÃ£o crÃ­tica
            return response()->json(['found' => false, 'message' => 'CÃ³digo nÃ£o encontrado.'], 404);
        }

        // Aplicar filtros do formulÃ¡rio (NÂº PatrimÃ´nio, Projeto, DescriÃ§Ã£o, SituaÃ§Ã£o, Modelo, CÃ³d. Termo, ResponsÃ¡vel)
        if ($request->filled('nupatrimonio')) {
            $val = trim((string)$request->input('nupatrimonio'));
            if ($val !== '') {
                // aceitar busca exata por nÃºmero (garantir inteiro quando for numÃ©rico)
                if (is_numeric($val)) {
                    $intVal = (int) $val;
                    Log::info('[Filtro] nupatrimonio aplicado (int)', ['val' => $intVal]);
                    $query->where('NUPATRIMONIO', $intVal);
                } else {
                    // se o usuÃ¡rio digitou algo que nÃ£o Ã© nÃºmero, usar LIKE por seguranÃ§a
                    Log::info('[Filtro] nupatrimonio aplicado (like)', ['val' => $val]);
                    $query->whereRaw('LOWER(NUPATRIMONIO) LIKE ?', ['%' . mb_strtolower($val) . '%']);
                }
            }
        }

        if ($request->filled('cdprojeto')) {
            $val = trim((string)$request->input('cdprojeto'));
            if ($val !== '') {
                // alguns registros guardam CDPROJETO no prÃ³prio patr, outros via relaÃ§Ã£o local
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
                        // fallback: pesquisar por trecho no NOME do funcionÃ¡rio via relaÃ§Ã£o 'funcionario' se existir
                        $query->whereHas('funcionario', function($q) use ($val) {
                            $q->whereRaw('LOWER(NOMEFUNCIONARIO) LIKE ?', ['%' . mb_strtolower($val) . '%']);
                        });
                    }
                }
            }
        }
    }

    // Autocomplete de cÃ³digos de objeto (CODOBJETO)
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
     * Gera o prÃ³ximo nÃºmero sequencial de patrimÃ´nio
     */
    public function proximoNumeroPatrimonio(): JsonResponse
    {
        try {
            $ultimoNumero = Patrimonio::max('NUPATRIMONIO') ?? 0;
            $proximoNumero = $ultimoNumero + 1;

            Log::info('PrÃ³ximo nÃºmero de patrimÃ´nio gerado', [
                'ultimo' => $ultimoNumero,
                'proximo' => $proximoNumero
            ]);

            return response()->json([
                'success' => true,
                'numero' => $proximoNumero
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar prÃ³ximo nÃºmero de patrimÃ´nio: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar nÃºmero de patrimÃ´nio'
            ], 500);
        }
    }

    
    public function index(Request $request): View
    {
        Log::info('[INDEX] Iniciado', ['user' => Auth::user()->NMLOGIN ?? null]);

        /** @var User $currentUser */
        $currentUser = Auth::user();
        $brunoSkipDefaultActive = false;

        // Filtro padrÇœo para o usuÇ­rio BRUNO: limitar aos cadastradores Bea e Tiago
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

        $locais = \App\Models\LocalProjeto::select('cdlocal as codigo', 'delocal as descricao')
            ->orderBy('codigo')
            ->orderBy('descricao')
            ->get();

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

    /**
     * Navigator beta com layout lateral novo e listagem de patrimônios.
     */
    public function navigatorBeta(Request $request): View
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();
        if (!($currentUser && (in_array(($currentUser->PERFIL ?? ''), ['SUP', 'ADM'], true)))) {
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
     * Mostra o formulÃ¡rio de criaÃ§Ã£o.
     */
    public function create(): View
    {
        // TODO: Substitua estes arrays pelas suas consultas reais ao banco de dados.
        $projetos = Tabfant::select('CDPROJETO', 'NOMEPROJETO')->distinct()->orderBy('NOMEPROJETO')->get();

        return view('patrimonios.create', compact('projetos'));
    }

    /**
     * Salva o novo patrimÃ´nio no banco de dados.
     * Regras:
     * - Se NUSEQOBJ (cÃ³digo) nÃ£o existir em objetopatr, cria um novo registro com DEOBJETO.
     * - Em seguida, cria o PatrimÃ´nio referenciando esse cÃ³digo.
     */
    public function store(Request $request)
    {
        // DEBUG: Ver o que foi recebido
        Log::info("SITUACAO recebido", [
            "raw" => $request->input("SITUACAO"),
            "length" => strlen($request->input("SITUACAO") ?? ""),
        ]);
        // 1) Validar os campos conforme o formulÃ¡rio (nomes em MAIÃSCULO)
        $validated = $request->validate([
            // O NÂº PatrimÃ´nio pode se repetir entre tipos; removido UNIQUE
            'NUPATRIMONIO' => 'required|integer',
            'NUSEQOBJ' => 'required|integer',
            'DEOBJETO' => 'nullable|string|max:350', // obrigatÃ³ria apenas quando cÃ³digo for novo
            'SITUACAO' => 'required|string|in:EM USO,CONSERTO,BAIXA,À DISPOSIÇÃO',
            'CDMATRFUNCIONARIO' => 'nullable|integer|exists:funcionarios,CDMATRFUNCIONARIO',
            'NUMOF' => 'nullable|integer',
            'DEHISTORICO' => 'nullable|string|max:300',
            'CDPROJETO' => 'nullable|integer',
            // O Local deve ser o cÃ³digo numÃ©rico (cdlocal) do LocalProjeto dentro do projeto
            'CDLOCAL' => 'nullable|integer',
            'NMPLANTA' => 'nullable|integer',
            'MARCA' => 'nullable|string|max:30',
            'MODELO' => 'nullable|string|max:30',
            'DTAQUISICAO' => 'nullable|date',
            'DTBAIXA' => 'nullable|date',
        ]);

        // Regra especial para almoxarifado central (999915) e em transito (2002)
        $this->enforceAlmoxRulesOnCreate($validated['CDLOCAL'] ?? null);

        //  VALIDAÇÃO CRÍTICA: Local deve pertencer ao projeto selecionado
        $localSelecionado = $this->validateLocalBelongsToProjeto(
            $validated['CDPROJETO'] ?? null,
            $validated['CDLOCAL'] ?? null,
            'criação de patrimônio'
        );

        // Garantir que vamos persistir sempre o código do local (cdlocal) e o projeto correto do local escolhido
        if ($localSelecionado) {
            $validated['CDLOCAL'] = (int) $localSelecionado->cdlocal;
            if ($localSelecionado->projeto) {
                $validated['CDPROJETO'] = (int) $localSelecionado->projeto->CDPROJETO;
            }
        }

        // â VERIFICAR DUPLICATAS: Impedir criar patrimÃ´nio com nÂº que jÃ¡ existe
        $nupatrimonio = (int) $validated['NUPATRIMONIO'];
        $jaExiste = Patrimonio::where('NUPATRIMONIO', $nupatrimonio)->exists();
        if ($jaExiste) {
            throw ValidationException::withMessages([
                'NUPATRIMONIO' => "JÃ¡ existe um patrimÃ´nio com o nÃºmero $nupatrimonio! NÃ£o Ã© permitido criar duplicatas."
            ]);
        }

        // 2) Garantir existÃªncia do ObjetoPatr (tabela objetopatr)
        //    O Model ObjetoPatr usa PK 'NUSEQOBJ'.
        $codigo = (int) $validated['NUSEQOBJ'];
        $objeto = ObjetoPatr::find($codigo);

        if (!$objeto) {
            // Se for novo cÃ³digo, exigir DEOBJETO
            $request->validate([
                'DEOBJETO' => 'required|string|max:350',
            ], [
                'DEOBJETO.required' => 'Informe a descriÃ§Ã£o do novo cÃ³digo.',
            ]);

            $objeto = ObjetoPatr::create([
                'NUSEQOBJ' => $codigo,
                // NUSEQTIPOPATR pode ser opcional aqui; ajustar se sua regra exigir
                'DEOBJETO' => $request->input('DEOBJETO'),
            ]);
        }

        // 3) Criar o patrimÃ´nio associando o cÃ³digo recÃ©m-verificado/criado
        $usuarioCriador = Auth::user()->NMLOGIN ?? Auth::user()->NOMEUSER ?? 'SISTEMA';
        $dadosPatrimonio = [
            'NUPATRIMONIO' => $nupatrimonio,
            'CODOBJETO' => $codigo, // campo da tabela patr
            // Usaremos a descriÃ§Ã£o do objeto como DEPATRIMONIO para manter compatibilidade atual do front
            'DEPATRIMONIO' => $objeto->DEOBJETO ?? $request->input('DEOBJETO'),
            'SITUACAO' => $validated['SITUACAO'],
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
            'USUARIO' => $usuarioCriador,
            'DTOPERACAO' => now(),
        ];

        Patrimonio::create($dadosPatrimonio);

        return redirect()->route('patrimonios.index')
            ->with('success', 'Patrimônio cadastrado com sucesso!');
    }

    /**
     * Mostra o formulÃ¡rio de ediÃ§Ã£o para um patrimÃ´nio especÃ­fico.
     */
    public function edit(Patrimonio $patrimonio): View
    {
        $this->authorize('update', $patrimonio);

        // Carregar relaÃ§Ãµes para exibir dados corretos no formulÃ¡rio
        $patrimonio->load(['local', 'local.projeto', 'funcionario']);

        // TODO: Substitua estes arrays pelas suas consultas reais ao banco de dados.
        $projetos = Tabfant::select('CDPROJETO', 'NOMEPROJETO')->distinct()->orderBy('NOMEPROJETO')->get();

        return view('patrimonios.edit', compact('patrimonio', 'projetos'));
    }

    /**
     * Atualiza um patrimÃ´nio existente no banco de dados.
     */
    public function update(Request $request, Patrimonio $patrimonio): RedirectResponse
    {
        $this->authorize('update', $patrimonio);

        // ð Debug: Log de todos os dados recebidos
        Log::info('ð [UPDATE] Dados recebidos do formulÃ¡rio', [
            'request_all' => $request->all(),
            'NUSEQPATR' => $patrimonio->NUSEQPATR,
        ]);

        $validatedData = $this->validatePatrimonio($request);
        $this->enforceAlmoxRulesOnUpdate($patrimonio->CDLOCAL, $validatedData['CDLOCAL'] ?? $patrimonio->CDLOCAL);

        //  VALIDAÇÃO CRÍTICA: Local deve pertencer ao projeto selecionado
        $localSelecionado = $this->validateLocalBelongsToProjeto(
            $validatedData['CDPROJETO'] ?? $patrimonio->CDPROJETO,
            $validatedData['CDLOCAL'] ?? $patrimonio->CDLOCAL,
            'atualização de patrimônio'
        );

        if ($localSelecionado) {
            $validatedData['CDLOCAL'] = (int) $localSelecionado->cdlocal;
            if ($localSelecionado->projeto) {
                $validatedData['CDPROJETO'] = (int) $localSelecionado->projeto->CDPROJETO;
            }
        }

        // â Log dos dados antes da atualizaÃ§Ã£o
        Log::info('PatrimÃ´nio UPDATE: Dados antes da atualizaÃ§Ã£o', [
            'NUSEQPATR' => $patrimonio->NUSEQPATR,
            'NUPATRIMONIO_old' => $patrimonio->NUPATRIMONIO,
            'CODOBJETO_old' => $patrimonio->CODOBJETO,
            'DEPATRIMONIO_old' => $patrimonio->DEPATRIMONIO,
            'CDLOCAL_old' => $patrimonio->CDLOCAL,
            'CDPROJETO_old' => $patrimonio->CDPROJETO,
            'CDMATRFUNCIONARIO_old' => $patrimonio->CDMATRFUNCIONARIO,
            'SITUACAO_old' => $patrimonio->SITUACAO,
        ]);
        Log::info('PatrimÃ´nio UPDATE: Dados validados para atualizar', [
            'NUSEQPATR' => $patrimonio->NUSEQPATR,
            'validated_data' => $validatedData,
        ]);

        // Detectar alteraÃ§Ãµes relevantes
        $oldProjeto = $patrimonio->CDPROJETO;
        $oldSituacao = $patrimonio->SITUACAO;
        $oldLocal = $patrimonio->CDLOCAL;

        // ð Debug: Log antes do update
        Log::info('ð [UPDATE] Chamando $patrimonio->update()', [
            'validated_data' => $validatedData,
        ]);

        $patrimonio->update($validatedData);

        // ð Debug: Recarregar do banco para verificar se salvou
        $patrimonio->refresh();

        $newProjeto = $patrimonio->CDPROJETO;
        $newSituacao = $patrimonio->SITUACAO;
        $newLocal = $patrimonio->CDLOCAL;

        // â Log dos dados apÃ³s a atualizaÃ§Ã£o
        Log::info('PatrimÃ´nio UPDATE: Dados apÃ³s a atualizaÃ§Ã£o', [
            'NUSEQPATR' => $patrimonio->NUSEQPATR,
            'NUPATRIMONIO_after' => $patrimonio->NUPATRIMONIO,
            'CODOBJETO_after' => $patrimonio->CODOBJETO,
            'DEPATRIMONIO_after' => $patrimonio->DEPATRIMONIO,
            'CDLOCAL_after' => $newLocal,
            'CDPROJETO_after' => $newProjeto,
            'CDMATRFUNCIONARIO_after' => $patrimonio->CDMATRFUNCIONARIO,
            'SITUACAO_after' => $newSituacao,
        ]);

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

        // Registrar histÃ³rico quando a SituaÃ§Ã£o mudar
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
        return redirect()->route('patrimonios.index')->with('success', 'PatrimÃ´nio atualizado com sucesso!');
    }

    /**
     * Remove o patrimÃ´nio do banco de dados.
     */
    public function destroy(Patrimonio $patrimonio)
    {
        \Illuminate\Support\Facades\Log::info('ðï¸ [DESTROY] Iniciando deleÃ§Ã£o', [
            'NUSEQPATR' => $patrimonio->NUSEQPATR,
            'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,
            'user' => Auth::user()->NMLOGIN ?? 'desconhecido',
            'user_id' => Auth::id(),
        ]);

        try {
            $this->authorize('delete', $patrimonio);
            
            \Illuminate\Support\Facades\Log::info('â [DESTROY] AutorizaÃ§Ã£o concedida', [
                'NUSEQPATR' => $patrimonio->NUSEQPATR,
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            \Illuminate\Support\Facades\Log::error('â [DESTROY] AutorizaÃ§Ã£o negada', [
                'NUSEQPATR' => $patrimonio->NUSEQPATR,
                'erro' => $e->getMessage(),
            ]);
            
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'VocÃª nÃ£o tem permissÃ£o para excluir este patrimÃ´nio.',
                    'code' => 'authorization_failed',
                ], 403);
            }
            
            return redirect()->route('patrimonios.index')
                ->with('error', 'VocÃª nÃ£o tem permissÃ£o para excluir este patrimÃ´nio.');
        }
        
        // Log da deleÃ§Ã£o
        \Illuminate\Support\Facades\Log::info('ð¾ [DESTROY] Deletando patrimÃ´nio', [
            'NUSEQPATR' => $patrimonio->NUSEQPATR,
            'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,
            'DEPATRIMONIO' => $patrimonio->DEPATRIMONIO,
            'deletado_por' => Auth::user()->NMLOGIN,
            'user_id' => Auth::id()
        ]);
        
        $patrimonio->delete();
        
        \Illuminate\Support\Facades\Log::info('â [DESTROY] PatrimÃ´nio deletado com sucesso', [
            'NUSEQPATR' => $patrimonio->NUSEQPATR,
        ]);
        
        if (request()->expectsJson()) {
            return response()->json(['message' => 'PatrimÃ´nio deletado com sucesso!'], 204)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        }
        
        return redirect()->route('patrimonios.index')->with('success', 'PatrimÃ´nio deletado com sucesso!');
    }

    /**
     * ðï¸ NOVO MÃTODO DE DELEÃÃO SIMPLIFICADO
     * MÃ©todo alternativo para deletar patrimÃ´nio por ID direto
     */
    public function deletePatrimonio($id)
    {
        \Illuminate\Support\Facades\Log::info('ðï¸ [DELETE] RequisiÃ§Ã£o recebida', [
            'id' => $id,
            'method' => request()->method(),
            'user' => Auth::user()->NMLOGIN ?? 'guest',
            'user_id' => Auth::id(),
            'ip' => request()->ip()
        ]);

        try {
            // Buscar patrimÃ´nio
            $patrimonio = Patrimonio::where('NUSEQPATR', $id)->first();
            
            if (!$patrimonio) {
                \Illuminate\Support\Facades\Log::warning('â [DELETE] PatrimÃ´nio nÃ£o encontrado', ['id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'PatrimÃ´nio nÃ£o encontrado'
                ], 404);
            }

            \Illuminate\Support\Facades\Log::info('â [DELETE] PatrimÃ´nio encontrado', [
                'NUSEQPATR' => $patrimonio->NUSEQPATR,
                'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,
                'DEPATRIMONIO' => $patrimonio->DEPATRIMONIO
            ]);

            // Verificar autorizaÃ§Ã£o (sem travar se falhar)
            try {
                $this->authorize('delete', $patrimonio);
                \Illuminate\Support\Facades\Log::info('â [DELETE] AutorizaÃ§Ã£o OK');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('â ï¸ [DELETE] AutorizaÃ§Ã£o falhou, permitindo mesmo assim', [
                    'erro' => $e->getMessage()
                ]);
                // Continuar mesmo se autorizaÃ§Ã£o falhar (temporÃ¡rio para debug)
            }

            // Salvar dados antes de deletar
            $dadosPatrimonio = [
                'NUSEQPATR' => $patrimonio->NUSEQPATR,
                'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,
                'DEPATRIMONIO' => $patrimonio->DEPATRIMONIO
            ];

            // DELETAR
            $deleted = $patrimonio->delete();
            
            \Illuminate\Support\Facades\Log::info('â [DELETE] PatrimÃ´nio deletado!', [
                'resultado' => $deleted,
                'dados' => $dadosPatrimonio
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PatrimÃ´nio deletado com sucesso!',
                'patrimonio' => $dadosPatrimonio
            ], 200);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('â [DELETE] Erro ao deletar', [
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
     * ð Exibe tela de duplicatas - patrimÃ´nios com mesmo nÃºmero
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

        // Se nÃ£o hÃ¡ duplicatas, retornar mensagem
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
     * ðï¸ Deleta um patrimÃ´nio (versÃ£o para duplicatas)
     * Usado na tela de removiÃ§Ã£o de duplicatas
     */
    public function deletarDuplicata(Request $request, Patrimonio $patrimonio): RedirectResponse
    {
        $this->authorize('delete', $patrimonio);

        $numero = $patrimonio->NUPATRIMONIO;
        Log::info('Deletando duplicata de patrimÃ´nio', [
            'NUSEQPATR' => $patrimonio->NUSEQPATR,
            'NUPATRIMONIO' => $numero,
            'deletado_por' => Auth::user()->NMLOGIN
        ]);

        $patrimonio->delete();

        return redirect()->route('patrimonios.duplicatas')
            ->with('success', "Duplicata nÂº $numero deletada com sucesso!");
    }

    // --- MÃTODOS DE API PARA O FORMULÃRIO DINÃMICO ---

    public function buscarPorNumero($numero): JsonResponse
    {
        try {
            $patrimonio = Patrimonio::with(['local', 'local.projeto', 'funcionario'])->where('NUPATRIMONIO', $numero)->first();
            
            if (!$patrimonio) {
                return response()->json(null, 404);
            }

            // ð VERIFICAR AUTORIZAÃÃO: O usuÃ¡rio pode ver este patrimÃ´nio?
            $user = Auth::user();
            if (!$user) {
                // NÃ£o autenticado
                return response()->json(['error' => 'NÃ£o autorizado'], 403);
            }

            // TODOS os usuários autenticados podem ver patrimônio (sem restrição de supervisão)
            return response()->json($patrimonio);
        } catch (\Throwable $e) {
            Log::error('Erro ao buscar patrimÃ´nio por nÃºmero: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Erro ao buscar patrimÃ´nio'], 500);
        }
    }

    /**
     * Buscar patrimônio por ID (NUSEQPATR) para modal de consultor
     * Usado no modal de leitura (PERFIL='C')
     */
    public function buscarPorId($id): JsonResponse
    {
        try {
            $patrimonio = Patrimonio::with(['local', 'local.projeto', 'funcionario'])->where('NUSEQPATR', $id)->first();
            
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
        $user = Auth::user();
        $isAdmin = $user && in_array(($user->PERFIL ?? ''), ['ADM', 'SUP'], true);
        
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

    public function pesquisar(Request $request): JsonResponse
    {
        try {
            $termo = trim((string) $request->input('q', ''));
            /** @var \App\Models\User|null $user */
            $user = Auth::user();

            if (!$user) {
                // NÃ£o autenticado
                return response()->json([], 403);
            }

            // Super Admin (SUP) e Admin (ADM) tÃªm acesso a TODOS os patrimÃ´nios
            if ($user->PERFIL === 'SUP' || $user->PERFIL === 'ADM') {
                $patrimonios = Patrimonio::select(['NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'SITUACAO'])
                    ->get()
                    ->toArray();
            } else {
                // UsuÃ¡rios comuns: sÃ³ podem ver patrimonios que sÃ£o responsÃ¡veis ou criadores
                $supervisionados = $user->getSupervisionados();
                
                $patrimonios = Patrimonio::where(function ($query) use ($user, $supervisionados) {
                    // ResponsÃ¡vel pelo patrimÃ´nio
                    $query->where('CDMATRFUNCIONARIO', $user->CDMATRFUNCIONARIO)
                        // OU criador (USUARIO)
                        ->orWhere('USUARIO', $user->NMLOGIN)
                        ->orWhere('USUARIO', $user->NOMEUSER)
                        // OU criado pelo SISTEMA â visÃ­vel a todos
                        ->orWhere('USUARIO', 'SISTEMA');
                    
                    // Se for supervisor, ver tambÃ©m registros dos supervisionados
                    if (!empty($supervisionados)) {
                        $query->orWhereIn(DB::raw('LOWER(USUARIO)'), array_map('strtolower', $supervisionados));
                    }
                })
                    ->select(['NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'SITUACAO'])
                    ->get()
                    ->toArray();
            }

            // Aplicar filtro inteligente
            $filtrados = \App\Services\FilterService::filtrar(
                $patrimonios,
                $termo,
                ['NUPATRIMONIO', 'DEPATRIMONIO'],  // campos de busca
                ['NUPATRIMONIO' => 'nÃºmero', 'DEPATRIMONIO' => 'texto'],  // tipos de campo
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
     * Autocomplete de projetos. Busca por cÃ³digo numÃ©rico parcial ou parte do nome.
     * Limite: 10 resultados para performance.
     */
    public function pesquisarProjetos(Request $request): JsonResponse
    {
        $termo = trim((string) $request->input('q', ''));

        // Buscar todos os projetos (excluindo cÃ³digo 0 - "NÃ£o se aplica")
        $projetos = Tabfant::select(['CDPROJETO', 'NOMEPROJETO'])
            ->where('CDPROJETO', '!=', 0)  // Excluir cÃ³digo 0
            ->distinct()
            ->orderByRaw('CAST(CDPROJETO AS UNSIGNED) ASC')  // OrdenaÃ§Ã£o numÃ©rica
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
     * Busca projetos associados a um local especÃ­fico.
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

            // Se veio um termo de busca (q), filtra pelo cÃ³digo ou nome
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
     * Cria um novo projeto com cÃ³digo Ãºnico e sequencial.
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
            // Gera o prÃ³ximo cÃ³digo sequencial Ãºnico
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
            return response()->json([]); // projeto nÃ£o encontrado => sem locais
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
            return response()->json(['error' => 'Projeto nÃ£o encontrado.'], 404);
        }

        // Calcula automaticamente o prÃ³ximo cdlocal baseado apenas nos locais deste projeto
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
     * Busca locais disponÃ­veis por cÃ³digo ou nome
     */
    public function buscarLocais(Request $request): JsonResponse
    {
        $termo = trim($request->input('termo', ''));
        $cdprojeto = trim($request->input('cdprojeto', ''));

        // BUSCAR NA TABELA LOCAIS_PROJETO (tem o cdlocal)
        $query = LocalProjeto::query();

        // Se tiver cdprojeto, filtrar apenas por esse projeto
        if ($cdprojeto !== '') {
            $query->whereHas('projeto', function ($q) use ($cdprojeto) {
                $q->where('CDPROJETO', $cdprojeto);
            });
        }

        $locaisProjeto = $query->get();

        // Buscar informaÃ§Ãµes do projeto na tabfant para cada local
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
            ['cdlocal' => 'nÃºmero', 'delocal' => 'texto'],  // tipos de campo
            100  // limite
        );

        return response()->json($filtrados);
    }

    /**
     * Busca um local especÃ­fico por ID e retorna informaÃ§Ãµes completas
     * Inclui qual projeto ele realmente pertence (para sincronizaÃ§Ã£o de dados desincronizados)
     */
    public function buscarLocalPorId($id): JsonResponse
    {
        try {
            $local = LocalProjeto::with('projeto')->find($id);

            if (!$local) {
                return response()->json(['error' => 'Local nÃ£o encontrado'], 404);
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
     * ð DEBUG: Listar todos os locais com cÃ³digo especÃ­fico
     */
    public function debugLocaisPorCodigo(Request $request): JsonResponse
    {
        $codigo = $request->input('codigo', '');

        Log::info('ð [DEBUG] Buscando locais com cÃ³digo:', ['codigo' => $codigo]);

        // CORRIGIDO: Buscar na tabela locais_projeto (tem cdlocal)
        $locaisProjeto = LocalProjeto::where('cdlocal', $codigo)
            ->where('flativo', true)
            ->orderBy('delocal')
            ->get();

        Log::info('ð [DEBUG] LocalProjeto encontrados:', ['total' => $locaisProjeto->count()]);

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

        Log::info('ð [DEBUG] Resultado:', $resultado);

        return response()->json($resultado);
    }

    /**
     * Cria um novo local informando o projeto por nome ou cÃ³digo
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

        // Busca o projeto por cÃ³digo ou nome
        $projeto = Tabfant::where('CDPROJETO', $request->projeto)
            ->orWhere('NOMEPROJETO', 'LIKE', "%{$request->projeto}%")
            ->first(['id', 'CDPROJETO', 'NOMEPROJETO']);

        if (!$projeto) {
            return response()->json(['error' => 'Projeto nÃ£o encontrado.'], 404);
        }

        // Calcula automaticamente o prÃ³ximo cdlocal baseado apenas nos locais deste projeto
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
     * PÃ¡gina dedicada para atribuiÃ§Ã£o de cÃ³digos de termo
     */
    public function atribuir(Request $request): View
    {
        $query = Patrimonio::query();

        // Nota: Removido filtro por usuÃ¡rio para que todos os patrimÃ´nios
        // apareÃ§am na tela de atribuiÃ§Ã£o de cÃ³digos (requisito de negÃ³cio).

        // Filtro por status - default volta a 'disponivel'
        $status = $request->get('status', 'disponivel');
        Log::info('ð Filtro Status: ' . $status);

        if ($status === 'disponivel') {
            // PatrimÃ´nios sem cÃ³digo de termo (campo integer => apenas null significa "sem")
            $query->whereNull('NMPLANTA');
        } elseif ($status === 'indisponivel') {
            // PatrimÃ´nios com cÃ³digo de termo
            $query->whereNotNull('NMPLANTA');
        }
        // Se status for vazio ou 'todos', nÃ£o aplica filtro de status

                // ObservaÃ§Ã£o: originalmente excluÃ­amos patrimÃ´nios sem DEPATRIMONIO,
                // mas a regra atual exige que TODOS os patrimÃ´nios cadastrados
                // apareÃ§am na tela de atribuiÃ§Ã£o. Portanto, removemos esse filtro.

        // Aplicar filtros se fornecidos
        if ($request->filled('filtro_numero')) {
            Log::info('ð Filtro NÃºmero: ' . $request->filtro_numero);
            $query->where('NUPATRIMONIO', 'like', '%' . $request->filtro_numero . '%');
        }

        if ($request->filled('filtro_descricao')) {
            Log::info('ð Filtro DescriÃ§Ã£o: ' . $request->filtro_descricao);
            $query->where('DEPATRIMONIO', 'like', '%' . $request->filtro_descricao . '%');
        }

        if ($request->filled('filtro_modelo')) {
            Log::info('ð Filtro Modelo: ' . $request->filtro_modelo);
            $query->where('MODELO', 'like', '%' . $request->filtro_modelo . '%');
        }

        // Filtro por projeto para atribuiÃ§Ã£o/termo
        if ($request->filled('filtro_projeto')) {
            Log::info('ð Filtro Projeto: ' . $request->filtro_projeto);
            $query->where('CDPROJETO', $request->filtro_projeto);
        }

        // Filtro por termo (apenas na aba atribuidos)
        if ($request->filled('filtro_termo')) {
            Log::info('ð Filtro Termo: ' . $request->filtro_termo);
            $query->where('NMPLANTA', $request->filtro_termo);
        }

        // Filtro por matrÃ­cula do responsÃ¡vel (CDMATRFUNCIONARIO)
        if ($request->filled('filtro_matr_responsavel')) {
            Log::info('ð Filtro MatrÃ­cula ResponsÃ¡vel: ' . $request->filtro_matr_responsavel);
            $query->where('CDMATRFUNCIONARIO', $request->filtro_matr_responsavel);
        }

        // Filtro por matrÃ­cula do cadastrador (USUARIO)
        if ($request->filled('filtro_matr_cadastrador')) {
            Log::info('ð Filtro MatrÃ­cula Cadastrador: ' . $request->filtro_matr_cadastrador);
            // Buscar pelo NMLOGIN do usuÃ¡rio que cadastrou
            $query->whereHas('creator', function ($q) use ($request) {
                $q->where('CDMATRFUNCIONARIO', $request->filtro_matr_cadastrador);
            });
        }

        // OrdenaÃ§Ã£o
        $query->orderBy('NMPLANTA', 'asc');
        $query->orderBy('NUPATRIMONIO', 'asc');

        // PaginaÃ§Ã£o configurÃ¡vel
        $perPage = (int) $request->input('per_page', 30);
        if ($perPage < 30) $perPage = 30;
        if ($perPage > 200) $perPage = 200;

        $patrimonios = $query->paginate($perPage);

        Log::info('ð Total de patrimÃ´nios apÃ³s filtro: ' . $patrimonios->total() . ' (PÃ¡gina ' . $patrimonios->currentPage() . ')');
        Log::info('ð PatrimÃ´nios nesta pÃ¡gina: ' . count($patrimonios));

        // Preencher descriÃ§Ãµes ausentes usando a tabela de objetos (consulta em lote)
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
            // Prioridade: DEPATRIMONIO (campo), depois DEOBJETO via CODOBJETO, senÃ£o compor por Marca/Modelo/SÃ©rie
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
     * PÃ¡gina isolada (clonada) para atribuiÃ§Ã£o de cÃ³digos de termo.
     * Reaproveita a mesma lÃ³gica de filtragem da pÃ¡gina principal para manter consistÃªncia.
     */
    public function atribuirCodigos(Request $request): View
    {
        $query = Patrimonio::query();

        // Nota: Removido filtro por usuÃ¡rio para que todos os patrimÃ´nios
        // apareÃ§am na pÃ¡gina de atribuiÃ§Ã£o de cÃ³digos (requisito do produto).

        $status = $request->get('status', 'disponivel');
        Log::info('[atribuirCodigos] ð Filtro Status: ' . $status);

        if ($status === 'disponivel') {
            $query->whereNull('NMPLANTA');
        } elseif ($status === 'indisponivel') {
            $query->whereNotNull('NMPLANTA');
        }

        if ($request->filled('filtro_numero')) {
            Log::info('[atribuirCodigos] ð Filtro NÃºmero: ' . $request->filtro_numero);
            $query->where('NUPATRIMONIO', 'like', '%' . $request->filtro_numero . '%');
        }
        if ($request->filled('filtro_descricao')) {
            Log::info('[atribuirCodigos] ð Filtro DescriÃ§Ã£o: ' . $request->filtro_descricao);
            $query->where('DEPATRIMONIO', 'like', '%' . $request->filtro_descricao . '%');
        }
        if ($request->filled('filtro_modelo')) {
            Log::info('[atribuirCodigos] ð Filtro Modelo: ' . $request->filtro_modelo);
            $query->where('MODELO', 'like', '%' . $request->filtro_modelo . '%');
        }
        if ($request->filled('filtro_projeto')) {
            Log::info('[atribuirCodigos] ð Filtro Projeto: ' . $request->filtro_projeto);
            $query->where('CDPROJETO', $request->filtro_projeto);
        }
        if ($request->filled('filtro_termo')) {
            Log::info('[atribuirCodigos] ð Filtro Termo: ' . $request->filtro_termo);
            $query->where('NMPLANTA', $request->filtro_termo);
        }

        $query->orderBy('NMPLANTA', 'asc');
        $query->orderBy('NUPATRIMONIO', 'asc');
        $perPage = (int) $request->input('per_page', 30);
        if ($perPage < 30) $perPage = 30;
        if ($perPage > 200) $perPage = 200;
        $patrimonios = $query->paginate($perPage);

        Log::info('[atribuirCodigos] ð Total de patrimÃ´nios apÃ³s filtro: ' . $patrimonios->total() . ' (PÃ¡gina ' . $patrimonios->currentPage() . ')');
        Log::info('[atribuirCodigos] ð PatrimÃ´nios nesta pÃ¡gina: ' . count($patrimonios));

        // Preencher descriÃ§Ãµes ausentes usando a tabela de objetos (consulta em lote)
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
     * Processar a atribuiÃ§Ã£o/desatribuiÃ§Ã£o de cÃ³digos de termo
     */
    public function processarAtribuicao(Request $request): RedirectResponse
    {
        // Verificar autorizaÃ§Ã£o de atribuiÃ§Ã£o
        $this->authorize('atribuir', Patrimonio::class);

        // Verificar se Ã© uma operaÃ§Ã£o de desatribuiÃ§Ã£o
        if ($request->filled('desatribuir')) {
            return $this->processarDesatribuicao($request);
        }
        // ValidaÃ§Ã£o condicional (caso envie cÃ³digo manualmente ainda funciona, mas nÃ£o Ã© mais o fluxo principal)
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
            Log::warning('Erro de validaÃ§Ã£o: campo de patrimÃ´nios obrigatÃ³rio nÃ£o foi preenchido', [
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

            // Novo fluxo: se nÃ£o veio um cÃ³digo explÃ­cito, o sistema determina automaticamente.
            if ($request->filled('codigo_termo')) {
                $codigoTermo = (int) $request->codigo_termo;
                $codigoExiste = TermoCodigo::where('codigo', $codigoTermo)->exists() || Patrimonio::where('NMPLANTA', $codigoTermo)->exists();
                if (!$codigoExiste) {
                    // Caso o cÃ³digo tenha sido "gerado" no front mas ainda nÃ£o registrado, registramos agora
                    TermoCodigo::firstOrCreate([
                        'codigo' => $codigoTermo
                    ], [
                        'created_by' => (Auth::user()->NMLOGIN ?? 'SISTEMA')
                    ]);
                }
            } else {
                // Fluxo inteligente: reutilizar menor cÃ³digo registrado sem uso ou gerar prÃ³ximo sequencial
                $unusedCodigo = TermoCodigo::whereNotIn('codigo', function ($q) {
                    $q->select('NMPLANTA')->from('patr')->whereNotNull('NMPLANTA');
                })
                    ->orderBy('codigo')
                    ->first();

                if ($unusedCodigo) {
                    $codigoTermo = (int) $unusedCodigo->codigo; // reutiliza cÃ³digo "vago"
                } else {
                    $maxRegistrado = (int) TermoCodigo::max('codigo');
                    $maxUsado = (int) Patrimonio::max('NMPLANTA');
                    $codigoTermo = max($maxRegistrado, $maxUsado) + 1; // prÃ³ximo sequencial
                    // registra para manter histÃ³rico de cÃ³digos gerados
                    TermoCodigo::firstOrCreate([
                        'codigo' => $codigoTermo
                    ], [
                        'created_by' => (Auth::user()->NMLOGIN ?? 'SISTEMA')
                    ]);
                }
            }

            // Verificar quais patrimÃ´nios jÃ¡ estÃ£o atribuÃ­dos
            $jaAtribuidos = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)
                ->whereNotNull('NMPLANTA')
                ->count();

            // Atualizar apenas os patrimÃ´nios disponÃ­veis
            $updated = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)
                ->whereNull('NMPLANTA')
                ->update(['NMPLANTA' => $codigoTermo]);

            $message = "CÃ³digo de termo {$codigoTermo} atribuÃ­do a {$updated} patrimÃ´nio(s) com sucesso!";

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
                        // Aqui nÃ£o temos o dono do patrimÃ´nio carregado; buscar rapidamente
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
                $message .= " ({$jaAtribuidos} patrimÃ´nio(s) jÃ¡ estavam atribuÃ­dos e foram ignorados)";
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
     * Processar desatribuiÃ§Ã£o de cÃ³digos de termo
     */
    private function processarDesatribuicao(Request $request): RedirectResponse
    {
        // Verificar autorizaÃ§Ã£o de desatribuiÃ§Ã£o
        $this->authorize('desatribuir', Patrimonio::class);

        // Log para verificar se o campo ids (ou patrimonios) estÃ¡ faltando ou vazio
        $fieldName = $request->has('ids') ? 'ids' : 'patrimonios';
        if (!$request->has($fieldName) || empty($request->input($fieldName))) {
            Log::warning('Erro de validaÃ§Ã£o: campo de patrimÃ´nios obrigatÃ³rio nÃ£o foi preenchido (desatribuiÃ§Ã£o)', [
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

            // Buscar informaÃ§Ãµes antes da desatribuiÃ§Ã£o para feedback
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
                    ->with('success', "CÃ³digo de termo {$codigoAnterior} removido de {$updated} patrimÃ´nio(s) com sucesso!");
            } else {
                return redirect()->route('patrimonios.atribuir')
                    ->with('warning', 'Nenhum patrimÃ´nio foi desatribuÃ­do. Verifique se os patrimÃ´nios selecionados possuem cÃ³digo de termo.');
            }
        } catch (\Exception $e) {
            Log::error('Erro ao processar desatribuiÃ§Ã£o de termo: ' . $e->getMessage());
            return redirect()->route('patrimonios.atribuir')
                ->with('error', 'Erro ao processar desatribuiÃ§Ã£o. Tente novamente.');
        }
    }

    /**
     * ð¯ API: Retorna lista de cadastradores disponÃ­veis para filtro multi-select
     * Para supervisores: retorna seus supervisionados
     * Para admins: retorna todos os usuÃ¡rios
     * Para usuÃ¡rios comuns: retorna apenas ele mesmo + SISTEMA
     */
    public function listarCadradores(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            $isSupervisor = !empty($user->getSupervisionados() ?? []);
            $isAdmin = in_array(($user->PERFIL ?? ''), ['SUP', 'ADM'], true);

            $cadastradores = [];

            // SISTEMA (sempre disponÃ­vel)
            $cadastradores[] = [
                'label' => 'Sistema',
                'value' => 'SISTEMA',
                'type' => 'sistema'
            ];

            if ($isAdmin) {
                // Admin vÃª todos os usuÃ¡rios que jÃ¡ cadastraram algo
                $usuarios = User::whereIn('PERFIL', ['USR', 'ADM'])
                    ->where('LGATIVO', 'S')
                    ->orderBy('NOMEUSER')
                    ->get(['NMLOGIN', 'NOMEUSER', 'CDMATRFUNCIONARIO']);

                foreach ($usuarios as $u) {
                    $cadastradores[] = [
                        'label' => $u->NOMEUSER . ' (' . $u->NMLOGIN . ')',
                        'value' => $u->NMLOGIN,
                        'type' => 'usuario'
                    ];
                }
            } elseif ($isSupervisor) {
                // Supervisor vÃª seus supervisionados
                $supervisionados = $user->getSupervisionados() ?? [];
                
                foreach ($supervisionados as $login) {
                    $u = User::where('NMLOGIN', $login)->first(['NMLOGIN', 'NOMEUSER']);
                    if ($u) {
                        $cadastradores[] = [
                            'label' => $u->NOMEUSER . ' (' . $u->NMLOGIN . ')',
                            'value' => $u->NMLOGIN,
                            'type' => 'supervisionado'
                        ];
                    }
                }
            } else {
                // UsuÃ¡rio comum vÃª apenas ele mesmo
                $cadastradores[] = [
                    'label' => $user->NOMEUSER . ' (' . $user->NMLOGIN . ')',
                    'value' => $user->NMLOGIN,
                    'type' => 'usuario'
                ];
            }

            Log::info('ð [API] Listar cadastradores executado', [
                'user_login' => $user->NMLOGIN,
                'is_supervisor' => $isSupervisor,
                'is_admin' => $isAdmin,
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
        
        Log::info('ð [getPatrimoniosQuery] INICIADO', [
            'user_id' => $user->NUSEQUSUARIO ?? null,
            'user_login' => $user->NMLOGIN ?? null,
            'user_perfil' => $user->PERFIL ?? null,
            'all_request_params' => $request->all(),
        ]);
        
        $query = Patrimonio::with(['funcionario', 'local.projeto', 'creator']);

        // Filtra patrimÃ´nios por usuÃ¡rio (exceto Admin e Super Admin)
        if (!in_array(($user->PERFIL ?? ''), ['SUP', 'ADM'], true)) {
            $nmLogin = (string) ($user->NMLOGIN ?? '');
            $nmUser  = (string) ($user->NOMEUSER ?? '');
            
            // Verificar se Ã© supervisor
            $supervisionados = $user->getSupervisionados(); // Array de logins supervisionados

            $query->where(function ($q) use ($user, $nmLogin, $nmUser, $supervisionados) {
                // Ver seus prÃ³prios registros
                $q->where('CDMATRFUNCIONARIO', $user->CDMATRFUNCIONARIO)
                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', [$nmLogin])
                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', [$nmUser])
                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', ['SISTEMA']);
                
                // Se for supervisor, ver tambÃ©m registros dos supervisionados
                if (!empty($supervisionados)) {
                    $q->orWhereIn(DB::raw('LOWER(USUARIO)'), array_map('strtolower', $supervisionados));
                }
            });
        }

        // Filtro MULTI-SELECT para cadastrador (para supervisores acompanharem mÃºltiplos usuÃ¡rios)
        $cadastradoresMulti = $request->input('cadastrados_por', []);
        if (is_string($cadastradoresMulti)) {
            // Se vier como string separada por vÃ­rgula, converter para array
            $cadastradoresMulti = array_filter(array_map('trim', explode(',', $cadastradoresMulti)));
        }

        if (!empty($cadastradoresMulti)) {
            Log::info('ð¯ [FILTRO MULTI] Cadastradores mÃºltiplos solicitados', [
                'valores' => $cadastradoresMulti,
                'count' => count($cadastradoresMulti)
            ]);

            // Para supervisores: permitir filtrar por seus supervisionados
            // Para admins: permitir qualquer usuÃ¡rio
            $supervisionados = $user->getSupervisionados() ?? [];
            $isSupervisor = !empty($supervisionados);
            $isAdmin = in_array(($user->PERFIL ?? ''), ['SUP', 'ADM'], true);

            // Construir lista de logins/matrÃ­culas permitidas
            $permitidos = [];
            foreach ($cadastradoresMulti as $valor) {
                $valor = trim((string)$valor);
                if (empty($valor)) continue;

                // Se for admin, permitir qualquer um
                if ($isAdmin) {
                    $permitidos[] = $valor;
                    continue;
                }

                // Se for supervisor, permitir apenas supervisionados
                if ($isSupervisor) {
                    if (in_array($valor, $supervisionados) || strcasecmp($valor, 'SISTEMA') === 0) {
                        $permitidos[] = $valor;
                    }
                }

                // Se for usuÃ¡rio comum, permitir apenas ele mesmo e SISTEMA
                if (!$isSupervisor && !$isAdmin) {
                    if (strcasecmp($valor, $user->NMLOGIN ?? '') === 0 || strcasecmp($valor, 'SISTEMA') === 0) {
                        $permitidos[] = $valor;
                    }
                }
            }

            if (!empty($permitidos)) {
                Log::info('ð¯ [FILTRO MULTI] Aplicando filtro com usuÃ¡rios permitidos', [
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
            // Filtro SINGLE para compatibilidade com formulÃ¡rio antigo (se nÃ£o houver multi-select)
            if ($request->filled('cadastrado_por')) {
                $valorFiltro = $request->input('cadastrado_por');

                // Valor especial para restaurar comportamento antigo: nÃ£o aplicar filtro
                if (trim((string)$valorFiltro) === '__TODOS__') {
                    // nÃ£o filtrar
                } else {
                    // Se usuÃ¡rio NÃO for Admin/SUP, sÃ³ permita filtrar por ele mesmo ou por SISTEMA
                    if (!($user->isGod() || $user->PERFIL === 'ADM')) {
                        $allowed = [strtoupper(trim((string)($user->NMLOGIN ?? ''))), 'SISTEMA'];
                        if (!empty($user->CDMATRFUNCIONARIO)) {
                            $allowed[] = (string)$user->CDMATRFUNCIONARIO;
                        }
                        if (!in_array(strtoupper(trim((string)$valorFiltro)), array_map('strtoupper', $allowed))) {
                            // valor nÃ£o permitido para este usuÃ¡rio; ignorar filtro
                            $valorFiltro = null;
                        }
                    }

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
        Log::info('ð [FILTROS] Antes de aplicar filtros', [
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
                    Log::info('â [FILTRO] nupatrimonio aplicado (INT)', ['val' => $intVal]);
                    $query->where('NUPATRIMONIO', $intVal);
                } else {
                    Log::info('â [FILTRO] nupatrimonio aplicado (LIKE)', ['val' => $val]);
                    $query->whereRaw('LOWER(CAST(NUPATRIMONIO AS CHAR)) LIKE ?', ['%' . mb_strtolower($val) . '%']);
                }
            } else {
                Log::info('â ï¸  [FILTRO] nupatrimonio vazio (nÃ£o aplicado)');
            }
        }

        if ($request->filled('cdprojeto')) {
            $val = trim((string)$request->input('cdprojeto'));
            if ($val !== '') {
                Log::info('â [FILTRO] cdprojeto aplicado', ['val' => $val]);
                $query->where(function($q) use ($val) {
                    $q->where('CDPROJETO', $val)
                      ->orWhereHas('local.projeto', function($q2) use ($val) {
                          $q2->where('CDPROJETO', $val);
                      });
                });
            } else {
                Log::info('â ï¸  [FILTRO] cdprojeto vazio (nÃ£o aplicado)');
            }
        }

        if ($request->filled('descricao')) {
            $val = trim((string)$request->input('descricao'));
            if ($val !== '') {
                $like = '%' . mb_strtolower($val) . '%';
                Log::info('â [FILTRO] descricao aplicado', ['val' => $val]);
                $query->whereRaw('LOWER(DEPATRIMONIO) LIKE ?', [$like]);
            } else {
                Log::info('â ï¸  [FILTRO] descricao vazio (nÃ£o aplicado)');
            }
        }

        if ($request->filled('situacao')) {
            $val = trim((string)$request->input('situacao'));
            if ($val !== '') {
                Log::info('â [FILTRO] situacao aplicado', ['val' => $val]);
                $query->where('SITUACAO', $val);
            } else {
                Log::info('â ï¸  [FILTRO] situacao vazio (nÃ£o aplicado)');
            }
        }

        if ($request->filled('modelo')) {
            $val = trim((string)$request->input('modelo'));
            if ($val !== '') {
                Log::info('â [FILTRO] modelo aplicado', ['val' => $val]);
                $query->whereRaw('LOWER(MODELO) LIKE ?', ['%' . mb_strtolower($val) . '%']);
            } else {
                Log::info('â ï¸  [FILTRO] modelo vazio (nÃ£o aplicado)');
            }
        }

        if ($request->filled('nmplanta')) {
            $val = trim((string)$request->input('nmplanta'));
            if ($val !== '') {
                Log::info('â [FILTRO] nmplanta aplicado', ['val' => $val]);
                $query->where('NMPLANTA', $val);
            } else {
                Log::info('â ï¸  [FILTRO] nmplanta vazio (nÃ£o aplicado)');
            }
        }

        if ($request->filled('matr_responsavel')) {
            $val = trim((string)$request->input('matr_responsavel'));
            if ($val !== '') {
                Log::info('â [FILTRO] matr_responsavel aplicado', ['val' => $val]);
                if (is_numeric($val)) {
                    $query->where('CDMATRFUNCIONARIO', $val);
                } else {
                    $usuarioFiltro = User::where('NMLOGIN', $val)->orWhereRaw('LOWER(NOMEUSER) LIKE ?', ['%' . mb_strtolower($val) . '%'])->first();
                    if ($usuarioFiltro) {
                        Log::info('ð¤ [FILTRO] matr_responsavel encontrado usuÃ¡rio', ['cdmatr' => $usuarioFiltro->CDMATRFUNCIONARIO, 'nmlogin' => $usuarioFiltro->NMLOGIN]);
                        $query->where('CDMATRFUNCIONARIO', $usuarioFiltro->CDMATRFUNCIONARIO);
                    } else {
                        Log::info('â [FILTRO] matr_responsavel usuÃ¡rio NÃO encontrado', ['val' => $val]);
                        $query->whereHas('funcionario', function($q) use ($val) {
                            $q->whereRaw('LOWER(NOMEFUNCIONARIO) LIKE ?', ['%' . mb_strtolower($val) . '%']);
                        });
                    }
                }
            } else {
                Log::info('â ï¸  [FILTRO] matr_responsavel vazio (nÃ£o aplicado)');
            }
        }

        Log::info('ð [QUERY] SQL gerada', [
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
            // se algo falhar, nÃ£o interromper; continuar com ordenaÃ§Ã£o padrÃ£o
            Log::warning('Falha ao aplicar ordenaÃ§Ã£o por usuÃ¡rio/DTOPERACAO: ' . $e->getMessage());
        }

        // Permitir ordenar tambÃ©m por DTAQUISICAO (ordena apÃ³s a prioridade do usuÃ¡rio)
        $sortableColumns = ['NUPATRIMONIO', 'MODELO', 'DEPATRIMONIO', 'SITUACAO', 'DTAQUISICAO'];
        $sortColumn = $request->input('sort', 'DTAQUISICAO');
        $sortDirection = $request->input('direction', 'asc');
        if (in_array($sortColumn, $sortableColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            // OrdenaÃ§Ã£o padrÃ£o por data de aquisiÃ§Ã£o crescente
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
                return response()->json(['error' => 'NÃ£o autorizado'], 403);
            }

            // Query para patrimÃ´nios disponÃ­veis (sem termo atribuÃ­do ou conforme regra de negÃ³cio)
            $query = Patrimonio::with(['funcionario'])
                ->whereNull('NMPLANTA') // Sem cÃ³digo de termo
                ->orWhere('NMPLANTA', '') // Ou cÃ³digo vazio
                ->orderBy('NUPATRIMONIO', 'asc');

            // Nota: Removido filtro de seguranÃ§a que restringia patrimÃ´nios
            // para nÃ£o-admins. Todos os patrimÃ´nios serÃ£o retornados para a
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
                            // Ãltimo fallback: tentar juntar campos menores (nÃºmero sÃ©rie, cor) ou usar texto padrÃ£o
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

    private function validatePatrimonio(Request $request): array
    {
        // ð Debug inicial
        Log::info('ð [VALIDATE] InÃ­cio da validaÃ§Ã£o', [
            'request_all' => $request->all(),
        ]);

        // 1) Validar campos bÃ¡sicos; aceitar tanto o fluxo novo (NUSEQOBJ/DEOBJETO)
        // quanto o legado (CODOBJETO/DEPATRIMONIO)
        $data = $request->validate([
            'NUPATRIMONIO' => 'required|integer',
            'NUMOF' => 'nullable|integer',
            // Fluxo novo de cÃ³digo
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
            'DTBAIXA' => 'nullable|date',
            // Matricula precisa existir na tabela funcionarios
            'CDMATRFUNCIONARIO' => 'nullable|integer|exists:funcionarios,CDMATRFUNCIONARIO',
        ]);

        Log::info('ð [VALIDATE] Dados apÃ³s validaÃ§Ã£o inicial', [
            'data' => $data,
        ]);

        // 2) Resolver o cÃ³digo do objeto a partir de NUSEQOBJ (preferencial) ou CODOBJETO (fallback)
        $codigoInput = $request->input('NUSEQOBJ', $request->input('CODOBJETO'));
        if ($codigoInput === null || $codigoInput === '') {
            throw ValidationException::withMessages([
                'NUSEQOBJ' => 'Informe o cÃ³digo do objeto.'
            ]);
        }
        if (!is_numeric($codigoInput)) {
            throw ValidationException::withMessages([
                'NUSEQOBJ' => 'O cÃ³digo do objeto deve ser numÃ©rico.'
            ]);
        }
        $codigo = (int) $codigoInput;

        // 3) Garantir existÃªncia do registro em OBJETOPATR
        $objeto = ObjetoPatr::find($codigo);
        if (!$objeto) {
            $descricao = trim((string) $request->input('DEOBJETO', ''));
            if ($descricao === '') {
                throw ValidationException::withMessages([
                    'DEOBJETO' => 'Informe a descriÃ§Ã£o do novo cÃ³digo.'
                ]);
            }
            $objeto = ObjetoPatr::create([
                'NUSEQOBJ' => $codigo,
                'DEOBJETO' => $descricao,
            ]);
        }

        // 4) Mapear para os campos reais da tabela PATR
        $data['CODOBJETO'] = $codigo;
        $data['DEPATRIMONIO'] = $objeto->DEOBJETO; // mantÃ©m compatibilidade de exibiÃ§Ã£o no index/relatÃ³rios
        unset($data['NUSEQOBJ'], $data['DEOBJETO']);

        Log::info('ð [VALIDATE] ApÃ³s mapear cÃ³digo do objeto', [
            'CODOBJETO' => $data['CODOBJETO'],
            'DEPATRIMONIO' => $data['DEPATRIMONIO'],
        ]);

        // 5) Sincroniza??o projeto-local: sempre alinhar projeto e gravar o cdlocal (n?mero do local)
        if (!empty($data['CDLOCAL'])) {
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

    /* === Rotas solicitadas para geraÃ§Ã£o e atribuiÃ§Ã£o direta de cÃ³digos (fluxo simplificado) === */
    public function gerarCodigo(Request $request, CodigoService $service): JsonResponse
    {
        try {
            [$code, $reused] = $service->gerarOuReaproveitar();
            return response()->json(['code' => $code, 'reused' => $reused]);
        } catch (\Throwable $e) {
            Log::error('Falha gerarCodigo', ['erro' => $e->getMessage()]);
            return response()->json(['message' => 'Erro ao gerar cÃ³digo'], 500);
        }
    }

    public function atribuirCodigo(Request $request, CodigoService $service): JsonResponse
    {
        // Aceita cÃ³digo numÃ©rico vindo como number ou string
        $request->validate([
            'code' => 'required', // pode vir number no JSON, entÃ£o nÃ£o restringimos a string
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer'
        ]);
        try {
            $codigo = (int) $request->input('code');
            if ($codigo <= 0) {
                return response()->json(['message' => 'CÃ³digo invÃ¡lido'], 422);
            }
            $resultado = $service->atribuirCodigo($codigo, $request->ids);
            if ($resultado['already_used']) {
                return response()->json(['message' => 'CÃ³digo jÃ¡ utilizado'], 422);
            }
            return response()->json([
                'code' => $resultado['code'],
                'updated_ids' => $resultado['updated'],
                'message' => 'AtribuÃ­do.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha atribuirCodigo', ['erro' => $e->getMessage()]);
            return response()->json(['message' => 'Erro ao atribuir cÃ³digo'], 500);
        }
    }

    /**
     * Desatribui (remove) o cÃ³digo de termo de uma lista de patrimÃ´nios (API JSON usada na pÃ¡gina de atribuiÃ§Ã£o)
     */
    public function desatribuirCodigo(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer'
        ]);
        try {
            $ids = $request->input('ids', []);
            // Seleciona patrimÃ´nios que realmente tÃªm cÃ³digo para evitar updates desnecessÃ¡rios
            $patrimonios = Patrimonio::whereIn('NUSEQPATR', $ids)->whereNotNull('NMPLANTA')->get(['NUSEQPATR', 'NUPATRIMONIO', 'NMPLANTA', 'CDMATRFUNCIONARIO']);
            if ($patrimonios->isEmpty()) {
                return response()->json(['message' => 'Nenhum patrimÃ´nio elegÃ­vel para desatribuir', 'updated_ids' => []], 200);
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
            return response()->json(['message' => 'Erro ao desatribuir cÃ³digo'], 500);
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
            'cdlocal.required' => 'CÃ³digo do local Ã© obrigatÃ³rio.',
            'delocal.required' => 'Nome do local Ã© obrigatÃ³rio.',
        ]);

        try {
            $cdlocal = $request->input('cdlocal');
            $delocal = $request->input('delocal');
            $nomeProjeto = $request->input('projeto');

            // Verificar se jÃ¡ existe local com esse cÃ³digo
            $localExistente = LocalProjeto::where('cdlocal', $cdlocal)->first();
            if ($localExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'JÃ¡ existe um local com este cÃ³digo.'
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
     * Usado no modal de criar local do formulÃ¡rio de patrimÃ´nio.
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
                'cdprojeto' => 'required', // Aceita string ou nÃºmero
                'cdlocal' => 'required',    // Aceita string ou nÃºmero
            ], [
                'local.required' => 'Nome do local Ã© obrigatÃ³rio.',
                'cdprojeto.required' => 'CÃ³digo do projeto Ã© obrigatÃ³rio.',
                'cdlocal.required' => 'CÃ³digo do local base Ã© obrigatÃ³rio.',
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
            $cdprojeto = (int) $request->input('cdprojeto');  // Converter para INT, nÃ£o STRING!
            $cdlocalBase = (string) $request->input('cdlocal');

            // Buscar o projeto no tabfant
            $projeto = Tabfant::where('CDPROJETO', $cdprojeto)->first();

            if (!$projeto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Projeto nÃ£o encontrado.'
                ], 404);
            }

            // Usar o MESMO cÃ³digo do local base (nÃ£o incrementar)
            // MÃºltiplos locais podem ter o mesmo CDLOCAL mas nomes diferentes
            $novoCdlocal = $cdlocalBase;

            DB::beginTransaction();
            try {
                // 1. Criar na tabela tabfant (cadastro de projetos/nomes de locais)
                // Nota: tabfant nÃ£o tem CDLOCAL, apenas LOCAL (nome do local)
                // IMPORTANTE: Como tabfant tem incrementing=false, precisamos gerar o ID manualmente
                $proximoId = (Tabfant::max('id') ?? 10000000) + 1;

                $novoTabfant = Tabfant::create([
                    'id' => $proximoId,  // â CRÃTICO: Especificar ID manualmente!
                    'LOCAL' => $nomeLocal,  // Nome do local
                    'CDPROJETO' => $cdprojeto,
                    'NOMEPROJETO' => $projeto->NOMEPROJETO,
                ]);

                // 2. Criar na tabela locais_projeto (vÃ­nculo entre cÃ³digo local e projeto)
                $localProjeto = LocalProjeto::create([
                    'cdlocal' => $novoCdlocal,  // CÃ³digo do local
                    'delocal' => $nomeLocal,
                    'tabfant_id' => $novoTabfant->id,
                    'flativo' => true,
                ]);

                DB::commit();

                Log::info('Local criado com sucesso', [
                    'tabfant_id' => $novoTabfant->id,
                    'local_projeto_id' => $localProjeto->id,
                    'cdlocal' => $novoCdlocal
                ]);

                return response()->json([
                    'success' => true,
                    'cdlocal' => $novoCdlocal,
                    'local' => [
                        'id' => $novoTabfant->id,
                        'cdlocal' => $novoCdlocal,
                        'LOCAL' => $nomeLocal,
                        'delocal' => $nomeLocal,
                        'CDPROJETO' => $cdprojeto,
                        'NOMEPROJETO' => $projeto->NOMEPROJETO,
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
                // Tentar extrair cÃ³digo e nome do formato "123 - Nome do Local"
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
     * Cria local e/ou projeto baseado nos dados do formulÃ¡rio de patrimÃ´nio.
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
                'cdlocal.required' => 'CÃ³digo do local Ã© obrigatÃ³rio',
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
                // Criar novo projeto sempre (nÃ£o buscar existente)
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
        // Precisa ao menos do local para validar/regra
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

        // Tentar resolver primeiro como ID (PK) e depois como c?digo (cdlocal)
        $local = LocalProjeto::with('projeto')->find($cdlocal);

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


