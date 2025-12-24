<?php

namespace App\Http\Controllers;

use App\Models\RegistroRemovido;
use App\Models\Funcionario;
use App\Models\LocalProjeto;
use App\Models\ObjetoPatr;
use App\Models\Tabfant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class RemovidosController extends Controller
{
    private function resolvePayloadRefs(array $payload): array
    {
        $upper = [];
        foreach ($payload as $key => $value) {
            $upper[strtoupper((string) $key)] = $value;
        }

        $resolved = [];

        // CDLOCAL -> LocalProjeto (cdlocal ou id)
        if (array_key_exists('CDLOCAL', $upper) && $upper['CDLOCAL'] !== null && $upper['CDLOCAL'] !== '') {
            $cdLocal = trim((string) $upper['CDLOCAL']);
            $local = null;

            try {
                if (Schema::hasTable('locais_projeto')) {
                    $local = LocalProjeto::with('projeto')->where('cdlocal', $cdLocal)->first();

                    if (!$local && ctype_digit($cdLocal)) {
                        $local = LocalProjeto::with('projeto')->find((int) $cdLocal);
                    }
                }
            } catch (\Throwable $e) {
                $local = null;
            }

            $resolved['CDLOCAL'] = [
                'codigo' => $cdLocal,
                'nome' => $local?->LOCAL ?? $local?->delocal ?? null,
            ];

            // Ajuda: se o payload n€oo tem CDPROJETO, tenta inferir via projeto do local
            if (!array_key_exists('CDPROJETO', $upper) && $local && $local->projeto) {
                $proj = $local->projeto;
                $resolved['CDPROJETO'] = [
                    'codigo' => (string) ($proj->CDPROJETO ?? ''),
                    'nome' => $proj->NMPROJETO ?? $proj->NOMEPROJETO ?? null,
                ];
            }
        }

        // CDPROJETO -> Tabfant (CDPROJETO)
        if (array_key_exists('CDPROJETO', $upper) && $upper['CDPROJETO'] !== null && $upper['CDPROJETO'] !== '') {
            $cdProjeto = trim((string) $upper['CDPROJETO']);
            $projeto = null;

            try {
                if (Schema::hasTable('tabfant')) {
                    $projeto = Tabfant::where('CDPROJETO', $cdProjeto)->first();
                }
            } catch (\Throwable $e) {
                $projeto = null;
            }

            $resolved['CDPROJETO'] = [
                'codigo' => $cdProjeto,
                'nome' => $projeto?->NMPROJETO ?? $projeto?->NOMEPROJETO ?? null,
            ];
        }

        // CDMATRFUNCIONARIO -> Funcionario
        if (array_key_exists('CDMATRFUNCIONARIO', $upper) && $upper['CDMATRFUNCIONARIO'] !== null && $upper['CDMATRFUNCIONARIO'] !== '') {
            $matricula = trim((string) $upper['CDMATRFUNCIONARIO']);
            $func = null;

            try {
                if (Schema::hasTable('funcionarios')) {
                    $func = Funcionario::where('CDMATRFUNCIONARIO', $matricula)->first();
                }
            } catch (\Throwable $e) {
                $func = null;
            }

            $resolved['CDMATRFUNCIONARIO'] = [
                'codigo' => $matricula,
                'nome' => $func?->NMFUNCIONARIO ?? null,
            ];
        }

        // CODOBJETO -> ObjetoPatr
        if (array_key_exists('CODOBJETO', $upper) && $upper['CODOBJETO'] !== null && $upper['CODOBJETO'] !== '') {
            $codObjeto = trim((string) $upper['CODOBJETO']);
            $objeto = null;

            try {
                if ((Schema::hasTable('OBJETOPATR') || Schema::hasTable('objetopatr')) && ctype_digit($codObjeto)) {
                    $objeto = ObjetoPatr::find((int) $codObjeto);
                }
            } catch (\Throwable $e) {
                $objeto = null;
            }

            $resolved['CODOBJETO'] = [
                'codigo' => $codObjeto,
                'nome' => $objeto?->DEOBJETO ?? null,
            ];
        }

        return $resolved;
    }

    public function index(Request $request): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->temAcessoTela(1009)) {
            abort(403, 'Acesso não autorizado');
        }

        $perPage = max(10, min(200, $request->integer('per_page', 50)));

        if (!Schema::hasTable('registros_removidos')) {
            $registros = new LengthAwarePaginator(
                [],
                0,
                $perPage,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('removidos.index', [
                'registros' => $registros,
                'entities' => [],
                'setupMissing' => true,
            ]);
        }

        $query = RegistroRemovido::query();

        if ($request->filled('entity')) {
            $query->where('entity', $request->input('entity'));
        }

        if ($request->filled('deleted_by')) {
            $term = trim((string) $request->input('deleted_by'));
            $query->where('deleted_by', 'like', '%' . $term . '%');
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate('deleted_at', '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('deleted_at', '<=', $request->input('data_fim'));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $query->where(function ($q) use ($term) {
                $q->where('model_label', 'like', '%' . $term . '%')
                    ->orWhere('model_id', 'like', '%' . $term . '%')
                    ->orWhere('deleted_by', 'like', '%' . $term . '%')
                    ->orWhere('payload', 'like', '%' . $term . '%');
            });
        }

        $registros = $query
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->appends($request->query());

        $entities = RegistroRemovido::query()
            ->select('entity')
            ->distinct()
            ->orderBy('entity')
            ->pluck('entity')
            ->toArray();

        Cache::put(
            'removidos_last_seen_' . $user->id,
            now()->toDateTimeString(),
            now()->addDays(30)
        );

        return view('removidos.index', [
            'registros' => $registros,
            'entities' => $entities,
        ]);
    }

    public function show(Request $request, int $removido)
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->temAcessoTela(1009)) {
            abort(403, 'Acesso não autorizado');
        }

        if (!Schema::hasTable('registros_removidos')) {
            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'A tabela de auditoria (registros_removidos) ainda nao existe.',
                ], 409);
            }

            return redirect()
                ->route('removidos.index')
                ->with('error', 'A tabela de auditoria (registros_removidos) ainda nao existe. Rode: php artisan migrate --path=database/migrations/2025_12_15_000000_create_registros_removidos_table.php');
        }

        $registro = RegistroRemovido::findOrFail($removido);
        $label = $registro->model_label ?? ($registro->model_type . ' #' . $registro->model_id);
        $payload = is_array($registro->payload) ? $registro->payload : [];
        $resolved = $this->resolvePayloadRefs($payload);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'id' => $registro->id,
                'label' => $label,
                'entity' => (string) ($registro->entity ?? ''),
                'payload' => $payload,
                'resolved' => $resolved,
            ]);
        }

        return view('removidos.show', [
            'removido' => $registro,
        ]);
    }

    public function restore(int $removido)
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->temAcessoTela(1009)) {
            abort(403, 'Acesso nǜo autorizado');
        }

        if (!Schema::hasTable('registros_removidos')) {
            return redirect()
                ->route('removidos.index')
                ->with('error', 'A tabela de auditoria (registros_removidos) ainda nao existe. Rode: php artisan migrate --path=database/migrations/2025_12_15_000000_create_registros_removidos_table.php');
        }

        $registro = RegistroRemovido::findOrFail($removido);

        $modelType = (string) ($registro->model_type ?? '');
        if ($modelType === '' || !class_exists($modelType) || !is_subclass_of($modelType, EloquentModel::class)) {
            return redirect()
                ->route('removidos.index')
                ->with('error', 'Nǜo foi possǜvel restaurar: model invǜlido.');
        }

        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new $modelType();
        $table = $model->getTable();
        $pk = $model->getKeyName();

        if (!Schema::hasTable($table)) {
            return redirect()
                ->route('removidos.index')
                ->with('error', "Nǜo foi possǜvel restaurar: tabela de origem ({$table}) nǜo encontrada.");
        }

        $payload = is_array($registro->payload) ? $registro->payload : [];
        if (!array_key_exists($pk, $payload) && $registro->model_id !== null) {
            $payload[$pk] = $registro->model_id;
        }

        $columns = Schema::getColumnListing($table);
        $columnsLookup = [];
        foreach ($columns as $col) {
            $columnsLookup[strtolower($col)] = $col;
        }

        $data = [];
        foreach ($payload as $key => $value) {
            $normalized = strtolower((string) $key);
            if (!isset($columnsLookup[$normalized])) {
                continue;
            }
            $data[$columnsLookup[$normalized]] = $value;
        }

        $pkColumn = $columnsLookup[strtolower($pk)] ?? $pk;
        $pkValue = $data[$pkColumn] ?? null;
        if ($pkValue === null || $pkValue === '') {
            return redirect()
                ->route('removidos.index')
                ->with('error', 'Nǜo foi possǜvel restaurar: chave primǜria ausente no payload.');
        }

        if (DB::table($table)->where($pkColumn, $pkValue)->exists()) {
            return redirect()
                ->route('removidos.index')
                ->with('error', "Nǜo foi possǜvel restaurar: o registro jǜ existe na origem (ID {$pkValue}).");
        }

        // Usuǜrios: a senha nǜo ǜ armazenada no payload (por seguranǜa). Ao restaurar, gera senha provisǜria.
        if (is_a($modelType, User::class, true)) {
            $senhaCol = $columnsLookup['senha'] ?? null;
            if ($senhaCol && !array_key_exists($senhaCol, $data)) {
                $randomNumbers = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $senhaProvisoria = 'Plansul@' . $randomNumbers;
                $data[$senhaCol] = Hash::make($senhaProvisoria);

                $mustChangeCol = $columnsLookup['must_change_password'] ?? null;
                if ($mustChangeCol) {
                    $data[$mustChangeCol] = true;
                }
            }
        }

        try {
            DB::transaction(function () use ($table, $data, $registro) {
                DB::table($table)->insert($data);
                $registro->delete();
            });
        } catch (\Throwable $e) {
            return redirect()
                ->route('removidos.index')
                ->with('error', 'Nǜo foi possǜvel restaurar: ' . $e->getMessage());
        }

        return redirect()
            ->route('removidos.index')
            ->with('success', 'Registro restaurado com sucesso.');
    }

    public function destroy(int $removido)
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->temAcessoTela(1009)) {
            abort(403, 'Acesso nǜo autorizado');
        }

        if (!Schema::hasTable('registros_removidos')) {
            return redirect()
                ->route('removidos.index')
                ->with('error', 'A tabela de auditoria (registros_removidos) ainda nao existe. Rode: php artisan migrate --path=database/migrations/2025_12_15_000000_create_registros_removidos_table.php');
        }

        $registro = RegistroRemovido::findOrFail($removido);
        $registro->delete();

        return redirect()
            ->route('removidos.index')
            ->with('success', 'Registro removido definitivamente.');
    }
}
