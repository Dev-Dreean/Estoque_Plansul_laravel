<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Models\Patrimonio;
use App\Models\User;
use App\Models\Objpatr;
use App\Models\Tabfant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PatrimonioController extends Controller
{

    public function buscarCodigoObjeto($codigo)
    {
        try {
            $codigo = trim($codigo);

            // Ajuste os nomes de tabela/colunas no Model Objpatr
            $registro = Objpatr::where('NUSEQOBJ', $codigo)->first();

            if (!$registro) {
                return response()->json(['found' => false, 'message' => 'Código não encontrado.'], 404);
            }

            return response()->json([
                'found'     => true,
                'descricao' => $registro->DEOBJETO,      // <- campo de descrição
                'tipo'      => $registro->NUSEQTIPOPATR, // <- se quiser usar também
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro buscarCodigoObjeto: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['found' => false, 'message' => 'Erro interno.'], 500);
        }
    }

public function index(Request $request): View
{
    // Busca os patrimônios para a tabela principal
    $query = $this->getPatrimoniosQuery($request);
    $patrimonios = $query->paginate(10);

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
                                ->paginate(10, ['*'], 'disponiveisPage');

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
     */
    public function store(Request $request): RedirectResponse
    {
        $validatedData = $this->validatePatrimonio($request);
        $user = Auth::user();

        Patrimonio::create(array_merge($validatedData, [
            'CDMATRFUNCIONARIO' => $user->CDMATRFUNCIONARIO,
            'USUARIO' => $user->NMLOGIN,
            'DTOPERACAO' => now(),
        ]));

        return redirect()->route('patrimonios.index')->with('success', 'Patrimônio cadastrado com sucesso!');
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
        $patrimonio->update($validatedData);
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
        if ($patrimonio) { return response()->json($patrimonio); }
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

    public function buscarProjeto($cdprojeto): JsonResponse
    {
        $projeto = Tabfant::where('CDPROJETO', $cdprojeto)->first(['NOMEPROJETO']);
        return response()->json($projeto);
    }

public function getLocaisPorProjeto($cdprojeto): JsonResponse
{
    $locais = Tabfant::where('CDPROJETO', $cdprojeto)
                     ->select('id', 'LOCAL') // Seleciona o ID e o nome do local
                     ->distinct()
                     ->get();

    return response()->json($locais);
}

    // --- MÉTODOS AUXILIARES ---

    private function getPatrimoniosQuery(Request $request)
    {
        $user = Auth::user();
        $query = Patrimonio::with('usuario');

        if ($user->PERFIL !== 'ADM') {
            $query->where('CDMATRFUNCIONARIO', $user->CDMATRFUNCIONARIO);
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
            $query->where('CDMATRFUNCIONARIO', $request->cadastrado_por);
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

    private function validatePatrimonio(Request $request): array
    {
        return $request->validate([
            'NUPATRIMONIO' => 'required|integer',
            'NUMOF' => 'nullable|integer',
            'CODOBJETO' => 'required|integer',
            'DEPATRIMONIO' => 'required|string|max:350',
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
        ]);
    }
}