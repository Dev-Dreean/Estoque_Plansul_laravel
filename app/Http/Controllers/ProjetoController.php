<?php

namespace App\Http\Controllers;

use App\Models\Tabfant; // Certifique-se que o seu modelo se chama Tabfant
use Illuminate\Http\Request;

class ProjetoController extends Controller
{
    public function index()
    {
        $projetos = Tabfant::orderBy('NOMEPROJETO')->paginate(15);
        return view('projetos.index', compact('projetos'));
    }

    public function create()
    {
        return view('projetos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'CDPROJETO' => 'required|integer|unique:tabfant,CDPROJETO',
            'NOMEPROJETO' => 'required|string|max:255',
            'LOCAL' => 'required|string|max:255',
        ]);

        Tabfant::create($request->all());

        return redirect()->route('projetos.index')->with('success', 'Projeto cadastrado com sucesso.');
    }

    public function edit(Tabfant $projeto)
    {
        return view('projetos.edit', compact('projeto'));
    }

    public function update(Request $request, Tabfant $projeto)
    {
        $request->validate([
            'CDPROJETO' => 'required|integer|unique:tabfant,CDPROJETO,' . $projeto->id,
            'NOMEPROJETO' => 'required|string|max:255',
            'LOCAL' => 'required|string|max:255',
        ]);

        $projeto->update($request->all());

        return redirect()->route('projetos.index')->with('success', 'Projeto atualizado com sucesso.');
    }

    public function destroy(Tabfant $projeto)
    {
        $projeto->delete();
        return redirect()->route('projetos.index')->with('success', 'Projeto apagado com sucesso.');
    }
}
