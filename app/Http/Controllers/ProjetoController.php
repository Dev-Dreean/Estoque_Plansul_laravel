<?php

namespace App\Http\Controllers;

use App\Models\LocalProjeto; // ALTERADO: Usando o Model correto
use App\Models\Tabfant;
use Illuminate\Http\Request;

class ProjetoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) // Adicione Request $request
    {
        $searchTerm = $request->input('search');

        $query = LocalProjeto::with('projeto')->orderBy('delocal');

        // Se houver um termo de busca, adiciona a condição no banco
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('delocal', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('cdlocal', 'LIKE', "%{$searchTerm}%");
            });
        }

        $locais = $query->paginate(15)->withQueryString(); // withQueryString mantém o filtro na paginação

        // Se a requisição for AJAX, retorna apenas a view da tabela
        if ($request->ajax()) {
            return view('projetos._table_partial', ['locais' => $locais])->render();
        }

        return view('projetos.index', ['locais' => $locais]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Para popular um dropdown com os projetos disponíveis
        $projetos = Tabfant::orderBy('NOMEPROJETO')->get();
        return view('projetos.create', ['projetos' => $projetos]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'delocal' => 'required|string|max:255',
            'cdlocal' => 'required|integer',
            'tabfant_id' => 'required|exists:tabfant,id', // Valida se o projeto selecionado existe
        ]);

        LocalProjeto::create([
            'delocal' => $request->delocal,
            'cdlocal' => $request->cdlocal,
            'tabfant_id' => $request->tabfant_id,
            'flativo' => $request->boolean('flativo'),
        ]);

        return redirect()->route('projetos.index')->with('success', 'Local cadastrado com sucesso.');
    }

    /**
     * Display the specified resource.
     * Este método pode ser usado para uma página de detalhes, se necessário.
     */
    public function show(LocalProjeto $local)
    {
        // O Laravel automaticamente encontrará o LocalProjeto pelo ID.
        return view('projetos.show', ['local' => $local]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LocalProjeto $projeto) // O Laravel vai injetar o LocalProjeto aqui
    {
        $projetos = Tabfant::orderBy('NOMEPROJETO')->get();
        return view('projetos.edit', [
            'local' => $projeto, // Renomeado para clareza
            'projetos' => $projetos
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LocalProjeto $projeto)
    {
        $request->validate([
            'delocal' => 'required|string|max:255',
            'cdlocal' => 'required|integer',
            'tabfant_id' => 'required|exists:tabfant,id',
        ]);

        $projeto->update([
            'delocal' => $request->delocal,
            'cdlocal' => $request->cdlocal,
            'tabfant_id' => $request->tabfant_id,
            'flativo' => $request->boolean('flativo'),
        ]);

        return redirect()->route('projetos.index')->with('success', 'Local atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LocalProjeto $projeto)
    {
        $projeto->delete();
        return redirect()->route('projetos.index')->with('success', 'Local apagado com sucesso.');
    }

    /**
     * Abre o formulário de criação já preenchido com dados do projeto existente
     * (nome e código do projeto original) mas limpa os campos específicos do local
     * para permitir adicionar um novo local rapidamente.
     */
    public function duplicate(LocalProjeto $projeto)
    {
        // Prefill mantendo nome / código originais e limpando projeto associado
        $prefill = [
            'delocal' => $projeto->delocal,
            'cdlocal' => $projeto->cdlocal,
            'tabfant_id' => '' // usuário deve escolher novo projeto
        ];
        return redirect()
            ->route('projetos.create')
            ->withInput($prefill)
            ->with('duplicating_from', $projeto->id)
            ->with('duplicating_mode', true);
    }
}
