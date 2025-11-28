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

    // Autocomplete de códigos de objeto (CODOBJETO)
    public function pesquisarCodigos(Request $request): JsonResponse
    {
        try {
            $termo = trim((string) $request->input('q', ''));

            // Buscar todos os códigos
            $codigos = ObjetoPatr::select(['NUSEQOBJETO as CODOBJETO', 'DEOBJETO as DESCRICAO'])
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
     * Gera o próximo número sequencial de patrimônio
     */
    public function proximoNumeroPatrimonio(): JsonResponse
    {
        try {
            $ultimoNumero = Patrimonio::max('NUPATRIMONIO') ?? 0;
            $proximoNumero = $ultimoNumero + 1;

            Log::info('Próximo número de patrimônio gerado', [
                'ultimo' => $ultimoNumero,
                'proximo' => $proximoNumero
            ]);

            return response()->json([
                'success' => true,
                'numero' => $proximoNumero
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar próximo número de patrimônio: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar número de patrimônio'
            ], 500);
        }
    }

    public function index(Request $request): View
    {
        // Consulta de patrimônios
        $query = $this->getPatrimoniosQuery($request);

        // Paginação
        $perPage = (int) $request->input('per_page', 30);
        if ($perPage < 10) $perPage = 10;
        if ($perPage > 500) $perPage = 500;

        $patrimonios = $query->paginate($perPage)->withQueryString();

        // Busca usuários que têm patrimônios cadastrados (apenas Admin)
        $cadastradores = [];
        /** @var User $currentUser */
        $currentUser = Auth::user();
        if ($currentUser->isGod() || $currentUser->PERFIL === 'ADM') {
            // Buscar TODOS os usuários (USR e ADM), independente de terem patrimônios
            $todosUsuarios = User::whereIn('PERFIL', ['USR', 'ADM'])
                ->orderBy('NOMEUSER')
                ->get(['NUSEQUSUARIO', 'NOMEUSER', 'NMLOGIN', 'CDMATRFUNCIONARIO']);

            $cadastradores = $todosUsuarios->map(function ($user) {
                return (object) [
                    'CDMATRFUNCIONARIO' => $user->NUSEQUSUARIO, // Usar NUSEQUSUARIO como identificador único
                    'NOMEUSER' => $user->NOMEUSER,
                    'NMLOGIN' => $user->NMLOGIN,
                ];
            })->values();
        }

        // Busca locais para modal de relatório
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
            // Definimos ordenação padrão por Data de Aquisição crescente
            'sort' => ['column' => $request->input('sort', 'DTAQUISICAO'), 'direction' => $request->input('direction', 'asc')],
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

        // ✅ VERIFICAR DUPLICATAS: Impedir criar patrimônio com nº que já existe
        $nupatrimonio = (int) $validated['NUPATRIMONIO'];
        $jaExiste = Patrimonio::where('NUPATRIMONIO', $nupatrimonio)->exists();
        if ($jaExiste) {
            throw ValidationException::withMessages([
                'NUPATRIMONIO' => "Já existe um patrimônio com o número $nupatrimonio! Não é permitido criar duplicatas."
            ]);
        }

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
            'NUPATRIMONIO' => $nupatrimonio,
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

        // Carregar relações para exibir dados corretos no formulário
        $patrimonio->load(['local', 'local.projeto', 'funcionario']);

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

        // 🔍 Debug: Log de todos os dados recebidos
        Log::info('🔍 [UPDATE] Dados recebidos do formulário', [
            'request_all' => $request->all(),
            'NUSEQPATR' => $patrimonio->NUSEQPATR,
        ]);

        $validatedData = $this->validatePatrimonio($request);

        // ✅ Log dos dados antes da atualização
        Log::info('Patrimônio UPDATE: Dados antes da atualização', [
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

        // Detectar alterações relevantes
        $oldProjeto = $patrimonio->CDPROJETO;
        $oldSituacao = $patrimonio->SITUACAO;
        $oldLocal = $patrimonio->CDLOCAL;

        // 🔍 Debug: Log antes do update
        Log::info('🔍 [UPDATE] Chamando $patrimonio->update()', [
            'validated_data' => $validatedData,
        ]);

        $patrimonio->update($validatedData);

        // 🔍 Debug: Recarregar do banco para verificar se salvou
        $patrimonio->refresh();

        $newProjeto = $patrimonio->CDPROJETO;
        $newSituacao = $patrimonio->SITUACAO;
        $newLocal = $patrimonio->CDLOCAL;

        // ✅ Log dos dados após a atualização
        Log::info('Patrimônio UPDATE: Dados após a atualização', [
            'NUSEQPATR' => $patrimonio->NUSEQPATR,
            'NUPATRIMONIO_after' => $patrimonio->NUPATRIMONIO,
            'CODOBJETO_after' => $patrimonio->CODOBJETO,
            'DEPATRIMONIO_after' => $patrimonio->DEPATRIMONIO,
            'CDLOCAL_after' => $newLocal,
            'CDPROJETO_after' => $newProjeto,
            'CDMATRFUNCIONARIO_after' => $patrimonio->CDMATRFUNCIONARIO,
            'SITUACAO_after' => $newSituacao,
        ]);

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
        return redirect()->route('patrimonios.index')->with('success', 'Patrimônio atualizado com sucesso!');
    }

    /**
     * Remove o patrimônio do banco de dados.
     */
    public function destroy(Patrimonio $patrimonio)
    {
        $this->authorize('delete', $patrimonio);
        
        // Log da deleção
        \Illuminate\Support\Facades\Log::info('Patrimônio deletado', [
            'NUSEQPATR' => $patrimonio->NUSEQPATR,
            'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO,
            'DEPATRIMONIO' => $patrimonio->DEPATRIMONIO,
            'deletado_por' => Auth::user()->NMLOGIN,
            'user_id' => Auth::id()
        ]);
        
        $patrimonio->delete();
        
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Patrimônio deletado com sucesso!'], 204)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        }
        
        return redirect()->route('patrimonios.index')->with('success', 'Patrimônio deletado com sucesso!');
    }

    /**
     * 🔍 Exibe tela de duplicatas - patrimônios com mesmo número
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
     * 🗑️ Deleta um patrimônio (versão para duplicatas)
     * Usado na tela de removição de duplicatas
     */
    public function deletarDuplicata(Request $request, Patrimonio $patrimonio): RedirectResponse
    {
        $this->authorize('delete', $patrimonio);

        $numero = $patrimonio->NUPATRIMONIO;
        Log::info('Deletando duplicata de patrimônio', [
            'NUSEQPATR' => $patrimonio->NUSEQPATR,
            'NUPATRIMONIO' => $numero,
            'deletado_por' => Auth::user()->NMLOGIN
        ]);

        $patrimonio->delete();

        return redirect()->route('patrimonios.duplicatas')
            ->with('success', "Duplicata nº $numero deletada com sucesso!");
    }

    // --- MÉTODOS DE API PARA O FORMULÁRIO DINÂMICO ---

    public function buscarPorNumero($numero): JsonResponse
    {
        try {
            $patrimonio = Patrimonio::with(['local', 'local.projeto', 'funcionario'])->where('NUPATRIMONIO', $numero)->first();
            
            if (!$patrimonio) {
                return response()->json(null, 404);
            }

            // 🔐 VERIFICAR AUTORIZAÇÃO: O usuário pode ver este patrimônio?
            $user = Auth::user();
            if (!$user) {
                // Não autenticado
                return response()->json(['error' => 'Não autorizado'], 403);
            }

            // Super Admin (SUP) e Admin (ADM) têm acesso total
            if ($user->PERFIL === 'SUP' || $user->PERFIL === 'ADM') {
                return response()->json($patrimonio);
            }

            // Usuários comuns: só podem ver se são responsáveis ou criadores
            $isResp = (string)($user->CDMATRFUNCIONARIO ?? '') === (string)($patrimonio->CDMATRFUNCIONARIO ?? '');
            $usuario = trim((string)($patrimonio->USUARIO ?? ''));
            $nmLogin = trim((string)($user->NMLOGIN ?? ''));
            $nmUser  = trim((string)($user->NOMEUSER ?? ''));
            $isCreator = $usuario !== '' && (
                strcasecmp($usuario, $nmLogin) === 0 ||
                strcasecmp($usuario, $nmUser) === 0
            );

            // Permitir que qualquer usuário veja lançamentos do SISTEMA
            $isSistema = strcasecmp($usuario, 'SISTEMA') === 0;

            if (!$isResp && !$isCreator && !$isSistema) {
                // Usuário não tem permissão
                Log::warning('Tentativa de acesso não autorizado a patrimônio', [
                    'user_id' => $user->id,
                    'patrimonio' => $numero,
                    'patrimonio_responsavel' => $patrimonio->CDMATRFUNCIONARIO,
                    'patrimonio_criador' => $patrimonio->USUARIO,
                ]);
                return response()->json(['error' => 'Não autorizado'], 403);
            }

            return response()->json($patrimonio);
        } catch (\Throwable $e) {
            Log::error('Erro ao buscar patrimônio por número: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Erro ao buscar patrimônio'], 500);
        }
    }

    public function pesquisar(Request $request): JsonResponse
    {
        try {
            $termo = trim((string) $request->input('q', ''));
            $user = Auth::user();

            if (!$user) {
                // Não autenticado
                return response()->json([], 403);
            }

            // Super Admin (SUP) e Admin (ADM) têm acesso a TODOS os patrimônios
            if ($user->PERFIL === 'SUP' || $user->PERFIL === 'ADM') {
                $patrimonios = Patrimonio::select(['NUSEQPATR', 'NUPATRIMONIO', 'DEPATRIMONIO', 'SITUACAO'])
                    ->get()
                    ->toArray();
            } else {
                // Usuários comuns: só podem ver patrimonios que são responsáveis ou criadores
                $patrimonios = Patrimonio::where(function ($query) use ($user) {
                    // Responsável pelo patrimônio
                    $query->where('CDMATRFUNCIONARIO', $user->CDMATRFUNCIONARIO)
                        // OU criador (USUARIO)
                        ->orWhere('USUARIO', $user->NMLOGIN)
                        ->orWhere('USUARIO', $user->NOMEUSER)
                        // OU criado pelo SISTEMA — visível a todos
                        ->orWhere('USUARIO', 'SISTEMA');
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

        // Buscar todos os projetos (excluindo código 0 - "Não se aplica")
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
            $local = LocalProjeto::with('projeto')->find($id);

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
     * 🔍 DEBUG: Listar todos os locais com código específico
     */
    public function debugLocaisPorCodigo(Request $request): JsonResponse
    {
        $codigo = $request->input('codigo', '');

        Log::info('🐛 [DEBUG] Buscando locais com código:', ['codigo' => $codigo]);

        // CORRIGIDO: Buscar na tabela locais_projeto (tem cdlocal)
        $locaisProjeto = LocalProjeto::where('cdlocal', $codigo)
            ->where('flativo', true)
            ->orderBy('delocal')
            ->get();

        Log::info('🐛 [DEBUG] LocalProjeto encontrados:', ['total' => $locaisProjeto->count()]);

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

        Log::info('🐛 [DEBUG] Resultado:', $resultado);

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

        // Nota: Removido filtro por usuário para que todos os patrimônios
        // apareçam na tela de atribuição de códigos (requisito de negócio).

        // Filtro por status - default volta a 'disponivel'
        $status = $request->get('status', 'disponivel');
        Log::info('🔍 Filtro Status: ' . $status);

        if ($status === 'disponivel') {
            // Patrimônios sem código de termo (campo integer => apenas null significa "sem")
            $query->whereNull('NMPLANTA');
        } elseif ($status === 'indisponivel') {
            // Patrimônios com código de termo
            $query->whereNotNull('NMPLANTA');
        }
        // Se status for vazio ou 'todos', não aplica filtro de status

                // Observação: originalmente excluíamos patrimônios sem DEPATRIMONIO,
                // mas a regra atual exige que TODOS os patrimônios cadastrados
                // apareçam na tela de atribuição. Portanto, removemos esse filtro.

        // Aplicar filtros se fornecidos
        if ($request->filled('filtro_numero')) {
            Log::info('🔍 Filtro Número: ' . $request->filtro_numero);
            $query->where('NUPATRIMONIO', 'like', '%' . $request->filtro_numero . '%');
        }

        if ($request->filled('filtro_descricao')) {
            Log::info('🔍 Filtro Descrição: ' . $request->filtro_descricao);
            $query->where('DEPATRIMONIO', 'like', '%' . $request->filtro_descricao . '%');
        }

        if ($request->filled('filtro_modelo')) {
            Log::info('🔍 Filtro Modelo: ' . $request->filtro_modelo);
            $query->where('MODELO', 'like', '%' . $request->filtro_modelo . '%');
        }

        // Filtro por projeto para atribuição/termo
        if ($request->filled('filtro_projeto')) {
            Log::info('🔍 Filtro Projeto: ' . $request->filtro_projeto);
            $query->where('CDPROJETO', $request->filtro_projeto);
        }

        // Filtro por termo (apenas na aba atribuidos)
        if ($request->filled('filtro_termo')) {
            Log::info('🔍 Filtro Termo: ' . $request->filtro_termo);
            $query->where('NMPLANTA', $request->filtro_termo);
        }

        // Filtro por matrícula do responsável (CDMATRFUNCIONARIO)
        if ($request->filled('filtro_matr_responsavel')) {
            Log::info('🔍 Filtro Matrícula Responsável: ' . $request->filtro_matr_responsavel);
            $query->where('CDMATRFUNCIONARIO', $request->filtro_matr_responsavel);
        }

        // Filtro por matrícula do cadastrador (USUARIO)
        if ($request->filled('filtro_matr_cadastrador')) {
            Log::info('🔍 Filtro Matrícula Cadastrador: ' . $request->filtro_matr_cadastrador);
            // Buscar pelo NMLOGIN do usuário que cadastrou
            $query->whereHas('creator', function ($q) use ($request) {
                $q->where('CDMATRFUNCIONARIO', $request->filtro_matr_cadastrador);
            });
        }

        // Ordenação
        $query->orderBy('NMPLANTA', 'asc');
        $query->orderBy('NUPATRIMONIO', 'asc');

        // Paginação configurável
        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 10) $perPage = 10;
        if ($perPage > 100) $perPage = 100;

        $patrimonios = $query->paginate($perPage);

        Log::info('📊 Total de patrimônios após filtro: ' . $patrimonios->total() . ' (Página ' . $patrimonios->currentPage() . ')');
        Log::info('📋 Patrimônios nesta página: ' . count($patrimonios));

        // Preencher descrições ausentes usando a tabela de objetos (consulta em lote)
        $codes = $patrimonios->pluck('CODOBJETO')->filter()->unique()->values()->all();
        if (!empty($codes)) {
            $descMap = \App\Models\ObjetoPatr::whereIn('NUSEQOBJETO', $codes)
                ->pluck('DEOBJETO', 'NUSEQOBJETO')
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
            $p->DEPATRIMONIO = $display ?: 'SEM DESCRIÇÃO';
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

        // Nota: Removido filtro por usuário para que todos os patrimônios
        // apareçam na página de atribuição de códigos (requisito do produto).

        $status = $request->get('status', 'disponivel');
        Log::info('[atribuirCodigos] 🔍 Filtro Status: ' . $status);

        if ($status === 'disponivel') {
            $query->whereNull('NMPLANTA');
        } elseif ($status === 'indisponivel') {
            $query->whereNotNull('NMPLANTA');
        }

        if ($request->filled('filtro_numero')) {
            Log::info('[atribuirCodigos] 🔍 Filtro Número: ' . $request->filtro_numero);
            $query->where('NUPATRIMONIO', 'like', '%' . $request->filtro_numero . '%');
        }
        if ($request->filled('filtro_descricao')) {
            Log::info('[atribuirCodigos] 🔍 Filtro Descrição: ' . $request->filtro_descricao);
            $query->where('DEPATRIMONIO', 'like', '%' . $request->filtro_descricao . '%');
        }
        if ($request->filled('filtro_modelo')) {
            Log::info('[atribuirCodigos] 🔍 Filtro Modelo: ' . $request->filtro_modelo);
            $query->where('MODELO', 'like', '%' . $request->filtro_modelo . '%');
        }
        if ($request->filled('filtro_projeto')) {
            Log::info('[atribuirCodigos] 🔍 Filtro Projeto: ' . $request->filtro_projeto);
            $query->where('CDPROJETO', $request->filtro_projeto);
        }
        if ($request->filled('filtro_termo')) {
            Log::info('[atribuirCodigos] 🔍 Filtro Termo: ' . $request->filtro_termo);
            $query->where('NMPLANTA', $request->filtro_termo);
        }

        $query->orderBy('NMPLANTA', 'asc');
        $query->orderBy('NUPATRIMONIO', 'asc');
        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 10) $perPage = 10;
        if ($perPage > 100) $perPage = 100;
        $patrimonios = $query->paginate($perPage);

        Log::info('[atribuirCodigos] 📊 Total de patrimônios após filtro: ' . $patrimonios->total() . ' (Página ' . $patrimonios->currentPage() . ')');
        Log::info('[atribuirCodigos] 📋 Patrimônios nesta página: ' . count($patrimonios));

        // Preencher descrições ausentes usando a tabela de objetos (consulta em lote)
        $codes = $patrimonios->pluck('CODOBJETO')->filter()->unique()->values()->all();
        if (!empty($codes)) {
            $descMap = \App\Models\ObjetoPatr::whereIn('NUSEQOBJETO', $codes)
                ->pluck('DEOBJETO', 'NUSEQOBJETO')
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
            $p->DEPATRIMONIO = $display ?: 'SEM DESCRIÇÃO';
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
            Log::warning('Erro de validação: campo de patrimônios obrigatório não foi preenchido', [
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

            // Verificar quais patrimônios já estão atribuídos
            $jaAtribuidos = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)
                ->whereNotNull('NMPLANTA')
                ->count();

            // Atualizar apenas os patrimônios disponíveis
            $updated = Patrimonio::whereIn('NUSEQPATR', $patrimoniosIds)
                ->whereNull('NMPLANTA')
                ->update(['NMPLANTA' => $codigoTermo]);

            $message = "Código de termo {$codigoTermo} atribuído a {$updated} patrimônio(s) com sucesso!";

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
        // Verificar autorização de desatribuição
        $this->authorize('desatribuir', Patrimonio::class);

        // Log para verificar se o campo ids (ou patrimonios) está faltando ou vazio
        $fieldName = $request->has('ids') ? 'ids' : 'patrimonios';
        if (!$request->has($fieldName) || empty($request->input($fieldName))) {
            Log::warning('Erro de validação: campo de patrimônios obrigatório não foi preenchido (desatribuição)', [
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
        /** @var User $user */
        $user = Auth::user();
        $query = Patrimonio::with(['funcionario', 'local.projeto', 'creator']);

        // Filtra patrimônios por usuário (exceto Admin e Super Admin)
        if (!$user->isGod() && $user->PERFIL !== 'ADM') {
            $nmLogin = (string) ($user->NMLOGIN ?? '');
            $nmUser  = (string) ($user->NOMEUSER ?? '');
            
            $query->where(function ($q) use ($user, $nmLogin, $nmUser) {
                // Ver seus próprios registros
                $q->where('CDMATRFUNCIONARIO', $user->CDMATRFUNCIONARIO)
                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', [$nmLogin])
                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', [$nmUser])
                    // Também permitir visualização de lançamentos feitos pelo SISTEMA
                    ->orWhereRaw('LOWER(USUARIO) = LOWER(?)', ['SISTEMA']);

                // Se é supervisor, ver também os registros dos supervisionados
                if ($user->isSupervisor()) {
                    $supervisionados = $user->getSupervisionados();
                    if (!empty($supervisionados)) {
                        $q->orWhereIn('USUARIO', $supervisionados);
                    }
                }
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
        // Filtro por matrícula do responsável (CDMATRFUNCIONARIO)
        if ($request->filled('matr_responsavel')) {
            $query->where('CDMATRFUNCIONARIO', $request->matr_responsavel);
        }
        // Filtro por matrícula do cadastrador (USUARIO)
        if ($request->filled('matr_cadastrador')) {
            $query->whereHas('creator', function ($q) use ($request) {
                $q->where('CDMATRFUNCIONARIO', $request->matr_cadastrador);
            });
        }
        // Filtro por cadastrante (apenas Admin e Super Admin)
        if ($request->filled('cadastrado_por') && ($user->isGod() || $user->PERFIL === 'ADM')) {
            if ($request->cadastrado_por === 'SISTEMA') {
                $query->whereNull('CDMATRFUNCIONARIO');
            } else {
                // O valor pode ser NUSEQUSUARIO (ID do usuário) ou CDMATRFUNCIONARIO
                $usuarioFiltro = User::find($request->cadastrado_por);
                
                if ($usuarioFiltro) {
                    // Se o usuário tem CDMATRFUNCIONARIO preenchido, usar esse
                    if ($usuarioFiltro->CDMATRFUNCIONARIO) {
                        $query->where('CDMATRFUNCIONARIO', $usuarioFiltro->CDMATRFUNCIONARIO);
                    } else {
                        // Se não tem CDMATRFUNCIONARIO (pré-cadastrado), buscar por USUARIO (o login)
                        $query->where('USUARIO', $usuarioFiltro->NMLOGIN);
                    }
                } else {
                    // Se não encontrar como usuário, tenta direto como CDMATRFUNCIONARIO (compatibilidade)
                    $query->where('CDMATRFUNCIONARIO', $request->cadastrado_por);
                }
            }
        }

        // Permitir ordenar também por DTAQUISICAO
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
                return response()->json(['error' => 'Não autorizado'], 403);
            }

            // Query para patrimônios disponíveis (sem termo atribuído ou conforme regra de negócio)
            $query = Patrimonio::with(['funcionario'])
                ->whereNull('NMPLANTA') // Sem código de termo
                ->orWhere('NMPLANTA', '') // Ou código vazio
                ->orderBy('NUPATRIMONIO', 'asc');

            // Nota: Removido filtro de segurança que restringia patrimônios
            // para não-admins. Todos os patrimônios serão retornados para a
            // listagem de disponibilidade/atribuição conforme regra de negócio.

            // Paginar manualmente
            $total = $query->count();
            $patrimonios = $query->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            return response()->json([
                'data' => $patrimonios->map(function ($p) use ($patrimonios) {
                        // Preencher descrição a partir de objetopatr se necessário
                        if (empty($p->DEPATRIMONIO) && !empty($p->CODOBJETO)) {
                            $obj = \App\Models\ObjetoPatr::where('NUSEQOBJETO', $p->CODOBJETO)->first();
                            if ($obj) $p->DEPATRIMONIO = $obj->DEOBJETO;
                        }
                        if (empty($p->DEPATRIMONIO)) {
                            $parts = array_filter([$p->MODELO ?? null, $p->MARCA ?? null, $p->NUSERIE ?? null, $p->COR ?? null]);
                            $p->DEPATRIMONIO = $parts ? implode(' - ', $parts) : 'SEM DESCRIÇÃO';
                        }
                        return [
                            'NUSEQPATR' => $p->NUSEQPATR,
                            'NUPATRIMONIO' => $p->NUPATRIMONIO,
                            'DEPATRIMONIO' => $p->DEPATRIMONIO,
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
        // 🔍 Debug inicial
        Log::info('🔍 [VALIDATE] Início da validação', [
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
            'SITUACAO' => 'required|string|in:EM USO,CONSERTO,BAIXA,À DISPOSIÇÃO',
            'DTAQUISICAO' => 'nullable|date',
            'DTBAIXA' => 'required_if:SITUACAO,BAIXA|nullable|date',
            // Matricula precisa existir na tabela funcionarios
            'CDMATRFUNCIONARIO' => 'required|integer|exists:funcionarios,CDMATRFUNCIONARIO',
        ]);

        Log::info('🔍 [VALIDATE] Dados após validação inicial', [
            'data' => $data,
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

        Log::info('🔍 [VALIDATE] Após mapear código do objeto', [
            'CODOBJETO' => $data['CODOBJETO'],
            'DEPATRIMONIO' => $data['DEPATRIMONIO'],
        ]);

        // 5) ✨ SINCRONIZAÇÃO PROJETO-LOCAL: Se CDLOCAL foi informado, sincronizar CDPROJETO
        if (!empty($data['CDLOCAL'])) {
            $localProjeto = LocalProjeto::find($data['CDLOCAL']);
            if ($localProjeto) {
                if ($localProjeto->tabfant_id) {
                    $projeto = Tabfant::find($localProjeto->tabfant_id);
                    if ($projeto) {
                        // Sincronizar o CDPROJETO com o projeto do local
                        $data['CDPROJETO'] = $projeto->CDPROJETO;
                        Log::info('Patrimônio: Sincronizando projeto com local', [
                            'CDLOCAL' => $data['CDLOCAL'],
                            'CDPROJETO_novo' => $projeto->CDPROJETO,
                            'local_nome' => $localProjeto->delocal
                        ]);
                    }
                } else {
                    // Local sem projeto associado - permitir, mas deixar CDPROJETO vazio se necessário
                    if (empty($data['CDPROJETO'])) {
                        Log::warning('Patrimônio: Local sem projeto associado', [
                            'CDLOCAL' => $data['CDLOCAL'],
                            'local_nome' => $localProjeto->delocal
                        ]);
                    }
                }
            } else {
                // Local não encontrado
                throw ValidationException::withMessages([
                    'CDLOCAL' => 'Local não encontrado ou inválido.'
                ]);
            }
        }

        Log::info('🔍 [VALIDATE] Dados finais que serão retornados', [
            'final_data' => $data,
        ]);

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
     * Cria um novo local vinculado a um projeto existente.
     * Usado no modal de criar local do formulário de patrimônio.
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

            // Usar o MESMO código do local base (não incrementar)
            // Múltiplos locais podem ter o mesmo CDLOCAL mas nomes diferentes
            $novoCdlocal = $cdlocalBase;

            DB::beginTransaction();
            try {
                // 1. Criar na tabela tabfant (cadastro de projetos/nomes de locais)
                // Nota: tabfant não tem CDLOCAL, apenas LOCAL (nome do local)
                // IMPORTANTE: Como tabfant tem incrementing=false, precisamos gerar o ID manualmente
                $proximoId = (Tabfant::max('id') ?? 10000000) + 1;

                $novoTabfant = Tabfant::create([
                    'id' => $proximoId,  // ← CRÍTICO: Especificar ID manualmente!
                    'LOCAL' => $nomeLocal,  // Nome do local
                    'CDPROJETO' => $cdprojeto,
                    'NOMEPROJETO' => $projeto->NOMEPROJETO,
                ]);

                // 2. Criar na tabela locais_projeto (vínculo entre código local e projeto)
                $localProjeto = LocalProjeto::create([
                    'cdlocal' => $novoCdlocal,  // Código do local
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
}
