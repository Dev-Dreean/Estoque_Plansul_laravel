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



            $nextId = (int) (TipoPatr::max('NUSEQTIPOPATR') ?? 0) + 1;







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

        // Validacao base

        $data = $request->validate([

            'NUSEQTIPOPATR' => ['required', 'integer'],

            'DEOBJETO'      => ['required', 'string', 'max:255'],

            'DETIPOPATR'    => ['nullable', 'string', 'max:255'],

        ]);



        $wantsJson = $request->expectsJson() || $request->wantsJson() || $request->ajax();



        // Se o codigo de tipo nao existir, exigimos o nome do tipo (DETIPOPATR)

        $tipo = TipoPatr::find((int) $data['NUSEQTIPOPATR']);

        if (!$tipo && empty($data['DETIPOPATR'])) {

            $message = 'Informe o nome do tipo para criar o cÃ³digo informado.';

            if ($wantsJson) {

                return response()->json([

                    'message' => $message,

                    'errors' => ['DETIPOPATR' => [$message]],

                ], 422);

            }

            return back()

                ->withErrors(['DETIPOPATR' => $message])

                ->withInput();

        }



        $pkColumn = (new ObjetoPatr())->getKeyName();

        $objeto = null;

        $tipoNome = null;



        DB::transaction(function () use ($data, $tipo, $pkColumn, &$objeto, &$tipoNome) {

            // Cria o tipo se nao existir

            $tipoId = (int) $data['NUSEQTIPOPATR'];

            if (!$tipo) {

                $tipo = TipoPatr::create([

                    'NUSEQTIPOPATR' => $tipoId,

                    'DETIPOPATR'    => $data['DETIPOPATR'],

                ]);

            }



            $tipoNome = $tipo->DETIPOPATR ?? $data['DETIPOPATR'];



            // Proximo codigo do objeto

            $nextId = (int) (ObjetoPatr::max($pkColumn) ?? 0) + 1;



            $objeto = ObjetoPatr::create([

                $pkColumn       => $nextId,

                'NUSEQTIPOPATR' => $tipoId,

                'DEOBJETO'      => $data['DEOBJETO'],

            ]);

        });



        if ($wantsJson) {

            return response()->json([

                'success' => true,

                'data' => [

                    'id' => $objeto?->getKey(),

                    'descricao' => $objeto?->DEOBJETO,

                    'tipo' => (int) $data['NUSEQTIPOPATR'],

                    'tipo_nome' => $tipoNome,

                ],

            ]);

        }



        return back()->with('success', 'Bem cadastrado com sucesso.');

    }



    public function destroy(int $nuseqobjeto)

    {

        $bem = ObjetoPatr::findOrFail($nuseqobjeto);

        $bem->delete();



        return back()->with('success', 'Bem excluÃ­do com sucesso.');

    }

}

