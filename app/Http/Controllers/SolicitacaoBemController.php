<?php

namespace App\Http\Controllers;

use App\Models\SolicitacaoBem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SolicitacaoBemController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = max(10, min(200, $request->integer('per_page', 30)));
        $query = SolicitacaoBem::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('uf')) {
            $query->where('uf', strtoupper(trim((string) $request->input('uf'))));
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('solicitante_nome', 'like', '%' . $term . '%')
                    ->orWhere('solicitante_matricula', 'like', '%' . $term . '%')
                    ->orWhere('setor', 'like', '%' . $term . '%')
                    ->orWhere('local_destino', 'like', '%' . $term . '%');
            });
        }

        $solicitacoes = $query
            ->withCount('itens')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $statusOptions = SolicitacaoBem::statusOptions();

        return view('solicitacoes.index', compact('solicitacoes', 'statusOptions'));
    }

    public function create(): View
    {
        return view('solicitacoes.create', [
            'user' => Auth::user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'solicitante_nome' => ['required', 'string', 'max:120'],
            'solicitante_matricula' => ['nullable', 'string', 'max:20'],
            'uf' => ['nullable', 'string', 'max:2'],
            'setor' => ['required', 'string', 'max:120'],
            'local_destino' => ['required', 'string', 'max:150'],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.descricao' => ['required', 'string', 'max:200'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1'],
            'itens.*.unidade' => ['nullable', 'string', 'max:20'],
            'itens.*.observacao' => ['nullable', 'string', 'max:500'],
        ];

        $validated = $request->validate($rules);

        $user = Auth::user();
        $uf = strtoupper(trim((string) ($validated['uf'] ?? '')));
        $matricula = trim((string) ($validated['solicitante_matricula'] ?? ''));
        $observacao = trim((string) ($validated['observacao'] ?? ''));

        $solicitacao = null;

        DB::transaction(function () use ($validated, $user, $uf, &$solicitacao) {
            $solicitacao = SolicitacaoBem::create([
                'solicitante_id' => $user?->getAuthIdentifier(),
                'solicitante_nome' => trim((string) $validated['solicitante_nome']),
                'solicitante_matricula' => $matricula !== '' ? $matricula : null,
                'uf' => $uf !== '' ? $uf : null,
                'setor' => trim((string) $validated['setor']),
                'local_destino' => trim((string) $validated['local_destino']),
                'observacao' => $observacao !== '' ? $observacao : null,
                'status' => SolicitacaoBem::STATUS_PENDENTE,
            ]);

            $itens = collect($validated['itens'] ?? [])
                ->map(function ($item) {
                    return [
                        'descricao' => trim((string) ($item['descricao'] ?? '')),
                        'quantidade' => (int) ($item['quantidade'] ?? 1),
                        'unidade' => trim((string) ($item['unidade'] ?? '')),
                        'observacao' => trim((string) ($item['observacao'] ?? '')),
                    ];
                })
                ->all();

            if (!empty($itens)) {
                $solicitacao->itens()->createMany($itens);
            }
        });

        if ($solicitacao) {
            $this->sendConfirmacaoEmail($solicitacao);
        }

        return redirect()
            ->route('solicitacoes-bens.show', $solicitacao)
            ->with('success', 'Solicitacao registrada com sucesso.');
    }

    public function show(SolicitacaoBem $solicitacao): View
    {
        $solicitacao->load('itens');
        $statusOptions = SolicitacaoBem::statusOptions();

        return view('solicitacoes.show', compact('solicitacao', 'statusOptions'));
    }

    public function update(Request $request, SolicitacaoBem $solicitacao): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(SolicitacaoBem::statusOptions())],
            'local_destino' => ['nullable', 'string', 'max:150'],
            'observacao_controle' => ['nullable', 'string', 'max:2000'],
            'matricula_recebedor' => ['nullable', 'string', 'max:20'],
            'nome_recebedor' => ['nullable', 'string', 'max:120'],
        ]);

        $data['local_destino'] = trim((string) ($data['local_destino'] ?? ''));
        if ($data['local_destino'] === '') {
            $data['local_destino'] = null;
        }
        $data['observacao_controle'] = trim((string) ($data['observacao_controle'] ?? ''));
        if ($data['observacao_controle'] === '') {
            $data['observacao_controle'] = null;
        }
        $data['matricula_recebedor'] = trim((string) ($data['matricula_recebedor'] ?? ''));
        if ($data['matricula_recebedor'] === '') {
            $data['matricula_recebedor'] = null;
        }
        $data['nome_recebedor'] = trim((string) ($data['nome_recebedor'] ?? ''));
        if ($data['nome_recebedor'] === '') {
            $data['nome_recebedor'] = null;
        }

        $statusNovo = $data['status'];
        $solicitacao->fill($data);

        if ($statusNovo === SolicitacaoBem::STATUS_SEPARADO && !$solicitacao->separado_em) {
            $solicitacao->separado_em = now();
            $solicitacao->separado_por_id = Auth::id();
        }

        if ($statusNovo === SolicitacaoBem::STATUS_CONCLUIDO && !$solicitacao->concluido_em) {
            $solicitacao->concluido_em = now();
            $solicitacao->concluido_por_id = Auth::id();
            if (!$solicitacao->separado_em) {
                $solicitacao->separado_em = $solicitacao->concluido_em;
                $solicitacao->separado_por_id = Auth::id();
            }
        }

        $solicitacao->save();

        return redirect()
            ->route('solicitacoes-bens.show', $solicitacao)
            ->with('success', 'Solicitacao atualizada com sucesso.');
    }

    private function sendConfirmacaoEmail(SolicitacaoBem $solicitacao): void
    {
        $to = trim((string) config('solicitacoes_bens.email_to'));
        if ($to === '') {
            return;
        }

        $subject = 'Solicitacao de bens recebida #' . $solicitacao->id;
        $body = implode("\n", [
            'Uma nova solicitacao de bens foi registrada.',
            'Numero: ' . $solicitacao->id,
            'Solicitante: ' . ($solicitacao->solicitante_nome ?? '-'),
            'Matricula: ' . ($solicitacao->solicitante_matricula ?? '-'),
            'Setor: ' . ($solicitacao->setor ?? '-'),
            'UF: ' . ($solicitacao->uf ?? '-'),
            'Local destino: ' . ($solicitacao->local_destino ?? '-'),
            'Status: ' . ($solicitacao->status ?? '-'),
        ]);

        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });

            $solicitacao->email_confirmacao_enviado_em = now();
            $solicitacao->save();
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar email de solicitacao de bens', [
                'solicitacao_id' => $solicitacao->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
