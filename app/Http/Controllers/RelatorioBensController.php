<?php

namespace App\Http\Controllers;

use App\Models\ObjetoPatr;
use App\Models\TipoPatr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioBensController extends Controller
{
    public function index(Request $request)
    {
    $query = ObjetoPatr::query()->with('tipo');

        if ($request->filled('descricao')) {
            $query->where('DEOBJETO', 'like', '%' . $request->input('descricao') . '%');
        }

        if ($request->filled('tipo')) {
            $query->whereHas('tipo', function ($q) use ($request) {
                $q->where('DETIPOPATR', 'like', '%' . $request->input('tipo') . '%');
            });
        }

        if ($request->filled('codigo_tipo')) {
            $query->where('NUSEQTIPOPATR', (int) $request->input('codigo_tipo'));
        }

        $perPage = $request->integer('per_page', 30);
        $bens = $query->orderBy('NUSEQTIPOPATR')
            ->orderBy('DEOBJETO')
            ->paginate($perPage)
            ->appends($request->query());

        $tipos = TipoPatr::orderBy('DETIPOPATR')->get(['NUSEQTIPOPATR', 'DETIPOPATR']);

        return view('relatorios.bens.index', compact('bens', 'tipos'));
    }

    // Cadastrar TIPO (TIPOPATR)
    public function storeTipo(Request $request)
    {
        $data = $request->validate([
            'DETIPOPATR' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data) {
            $nextId = (int) (DB::table('TIPOPATR')->max('NUSEQTIPOPATR') ?? 0) + 1;

            TipoPatr::create([
                'NUSEQTIPOPATR' => $nextId,
                'DETIPOPATR'    => $data['DETIPOPATR'],
            ]);
        });

        return back()->with('success', 'Tipo cadastrado com sucesso.');
    }

    // Cadastrar BEM/OBJETO (OBJETOPATR)
    public function store(Request $request)
    {
        // Validação base
        $data = $request->validate([
            'NUSEQTIPOPATR' => ['required', 'integer'],
            'DEOBJETO'      => ['required', 'string', 'max:255'],
            'DETIPOPATR'    => ['nullable', 'string', 'max:255'],
        ]);

        // Se o código de tipo não existir, exigimos o nome do tipo (DETIPOPATR)
        $tipo = TipoPatr::find((int) $data['NUSEQTIPOPATR']);
        if (!$tipo && empty($data['DETIPOPATR'])) {
            return back()
                ->withErrors(['DETIPOPATR' => 'Informe o nome do tipo para criar o código informado.'])
                ->withInput();
        }

    DB::transaction(function () use ($data, $tipo) {
            // Cria o tipo se não existir
            $tipoId = (int) $data['NUSEQTIPOPATR'];
            if (!$tipo) {
                TipoPatr::create([
                    'NUSEQTIPOPATR' => $tipoId,
                    'DETIPOPATR'    => $data['DETIPOPATR'],
                ]);
            }

            // Próximo código do objeto
            $nextId = (int) (ObjetoPatr::max('NUSEQOBJETO') ?? 0) + 1;

            ObjetoPatr::create([
                'NUSEQOBJETO'   => $nextId,
                'NUSEQTIPOPATR' => $tipoId,
                'DEOBJETO'      => $data['DEOBJETO'],
            ]);
        });

        return back()->with('success', 'Bem cadastrado com sucesso.');
    }

    public function destroy(int $nuseqobjeto)
    {
        // Se o primaryKey do modelo ObjetoPatr já é NUSEQOBJETO, o findOrFail funciona direto:
        $bem = ObjetoPatr::findOrFail($nuseqobjeto);
        $bem->delete();

        return back()->with('success', 'Bem excluído com sucesso.');
    }
}
