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
        // Permite pré-preencher código (ex: ao clicar "Incluir Filial")
        $codigo = request('codigo');
        $nome = null;
        if ($codigo) {
            $existente = Tabfant::where('CDPROJETO', $codigo)->first();
            if ($existente) {
                $nome = $existente->NOMEPROJETO;
            }
        }
        return view('projetos.create', compact('codigo', 'nome'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'CDPROJETO' => 'required|integer',
            'NOMEPROJETO' => 'required|string|max:255',
            'LOCAL' => 'required|string|max:255',
        ]);

        $existente = Tabfant::where('CDPROJETO', $request->CDPROJETO)->first();
        if ($existente && strcasecmp(trim($existente->NOMEPROJETO), trim($request->NOMEPROJETO)) !== 0) {
            return back()->withInput()->withErrors(['NOMEPROJETO' => 'Nome inválido para este código. Deve ser exatamente: ' . $existente->NOMEPROJETO]);
        }

        Tabfant::create([
            'CDPROJETO' => $request->CDPROJETO,
            'NOMEPROJETO' => $existente?->NOMEPROJETO ?? trim($request->NOMEPROJETO),
            'LOCAL' => trim($request->LOCAL),
        ]);

        return redirect()->route('projetos.index')->with('success', 'Projeto cadastrado com sucesso.');
    }

    public function edit(Tabfant $projeto)
    {
        return view('projetos.edit', compact('projeto'));
    }

    public function update(Request $request, Tabfant $projeto)
    {
        $request->validate([
            'CDPROJETO' => 'required|integer',
            'NOMEPROJETO' => 'required|string|max:255',
            'LOCAL' => 'required|string|max:255',
        ]);

        $outro = Tabfant::where('CDPROJETO', $request->CDPROJETO)->where('id', '!=', $projeto->id)->first();
        if ($outro && strcasecmp(trim($outro->NOMEPROJETO), trim($request->NOMEPROJETO)) !== 0) {
            return back()->withInput()->withErrors(['NOMEPROJETO' => 'Nome inválido: código pertence a ' . $outro->NOMEPROJETO]);
        }
        $projeto->update([
            'CDPROJETO' => $request->CDPROJETO,
            'NOMEPROJETO' => trim($request->NOMEPROJETO),
            'LOCAL' => trim($request->LOCAL),
        ]);

        return redirect()->route('projetos.index')->with('success', 'Projeto atualizado com sucesso.');
    }

    public function destroy(Tabfant $projeto)
    {
        $projeto->delete();
        return redirect()->route('projetos.index')->with('success', 'Projeto apagado com sucesso.');
    }
}
