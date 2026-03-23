@php
    $statusColors = [
        'PENDENTE' => 'bg-yellow-400 text-black border border-yellow-500',
        'AGUARDANDO_CONFIRMACAO' => 'bg-blue-400 text-black border border-blue-500',
        'LIBERACAO' => 'bg-violet-300 text-black border border-violet-500',
        'CONFIRMADO' => 'bg-sky-200 text-sky-900 border border-sky-500',
        'ENVIADO' => 'bg-cyan-200 text-cyan-900 border border-cyan-500',
        'RECEBIDO' => 'bg-green-400 text-black border border-green-600',
        'NAO_ENVIADO' => 'bg-orange-400 text-black border border-orange-500',
        'NAO_RECEBIDO' => 'bg-rose-300 text-black border border-rose-500',
        'CANCELADO' => 'bg-red-400 text-black border border-red-600',
    ];
    $currentUser = auth()->user();
    $currentUserId = $currentUser?->getAuthIdentifier();
    $currentUserMatricula = trim((string) ($currentUser?->CDMATRFUNCIONARIO ?? ''));
    $isAdminUser = $currentUser?->isAdmin() ?? false;
    $shortPersonName = function (?string $nome): string {
        $nome = trim((string) $nome);
        if ($nome === '') {
            return '-';
        }

        $partes = preg_split('/\s+/', $nome) ?: [];
        $qtd = count($partes);

        if ($qtd <= 2) {
            return mb_convert_case(mb_strtolower($nome, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        if ($qtd <= 4) {
            $resumo = $partes[0] . ' ' . $partes[1] . ' ' . $partes[$qtd - 1];
            return mb_convert_case(mb_strtolower($resumo, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        $resumo = $partes[0] . ' ' . $partes[$qtd - 1];
        return mb_convert_case(mb_strtolower($resumo, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    };
    $formatDisplay = function (?string $valor): string {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return '-';
        }

        return mb_convert_case(mb_strtolower($valor, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    };
    $currentSort = $sort ?? request('sort', 'updated_at');
    $currentDirection = $direction ?? request('direction', 'desc');
    $nextDirection = fn ($col) => ($currentSort === $col && $currentDirection === 'asc') ? 'desc' : 'asc';
    $sortMark = fn ($col) => ($currentSort === $col) ? ($currentDirection === 'asc' ? '^' : 'v') : '-';
@endphp

<div class="relative overflow-x-auto shadow-md sm:rounded-lg" data-solicitacoes-grid>
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="sol-index__table-head">
            <tr>
                <th class="px-4 py-2"><a href="{{ request()->fullUrlWithQuery(['sort' => 'id', 'direction' => $nextDirection('id'), 'page' => 1]) }}" class="sol-index__sort-link">Cód. <span class="text-[10px] text-violet-200">{{ $sortMark('id') }}</span></a></th>
                <th class="px-4 py-2"><a href="{{ request()->fullUrlWithQuery(['sort' => 'itens', 'direction' => $nextDirection('itens'), 'page' => 1]) }}" class="sol-index__sort-link">Itens <span class="text-[10px] text-violet-200">{{ $sortMark('itens') }}</span></a></th>
                <th class="px-4 py-2"><a href="{{ request()->fullUrlWithQuery(['sort' => 'solicitante', 'direction' => $nextDirection('solicitante'), 'page' => 1]) }}" class="sol-index__sort-link">Solicitante <span class="text-[10px] text-violet-200">{{ $sortMark('solicitante') }}</span></a></th>
                <th class="px-4 py-2"><a href="{{ request()->fullUrlWithQuery(['sort' => 'local_destino', 'direction' => $nextDirection('local_destino'), 'page' => 1]) }}" class="sol-index__sort-link">Local destino <span class="text-[10px] text-violet-200">{{ $sortMark('local_destino') }}</span></a></th>
                <th class="px-4 py-2"><a href="{{ request()->fullUrlWithQuery(['sort' => 'uf', 'direction' => $nextDirection('uf'), 'page' => 1]) }}" class="sol-index__sort-link">UF <span class="text-[10px] text-violet-200">{{ $sortMark('uf') }}</span></a></th>
                <th class="px-4 py-2"><a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => $nextDirection('status'), 'page' => 1]) }}" class="sol-index__sort-link">Status <span class="text-[10px] text-violet-200">{{ $sortMark('status') }}</span></a></th>
                <th class="px-4 py-2"><a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => $nextDirection('created_at'), 'page' => 1]) }}" class="sol-index__sort-link">Criado <span class="text-[10px] text-violet-200">{{ $sortMark('created_at') }}</span></a></th>
                <th class="px-4 py-2"><a href="{{ request()->fullUrlWithQuery(['sort' => 'updated_at', 'direction' => $nextDirection('updated_at'), 'page' => 1]) }}" class="sol-index__sort-link">Atualizado <span class="text-[10px] text-violet-200">{{ $sortMark('updated_at') }}</span></a></th>
                <th class="px-4 py-2 text-white">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($solicitacoes as $solicitacao)
                <tr
                    class="sol-index__row {{ $loop->odd ? 'sol-index__row--odd' : 'sol-index__row--even' }}"
                    @click="openShowModal({{ $solicitacao->id }})"
                >
                    <td class="px-4 py-2 font-semibold text-gray-900 dark:text-white">#{{ $solicitacao->id }}</td>
                    <td class="px-4 py-2">{{ $solicitacao->itens_count ?? 0 }}</td>
                    <td class="px-4 py-2">
                        <div class="text-gray-900 dark:text-gray-100">{{ $shortPersonName($solicitacao->solicitante_nome ?? '-') }}</div>
                        <div class="text-xs text-gray-500">{{ $solicitacao->solicitante_matricula ?? '-' }}</div>
                    </td>
                    <td class="px-4 py-2">{{ $formatDisplay($solicitacao->local_destino ?? '-') }}</td>
                    <td class="px-4 py-2">{{ $solicitacao->uf ?? '-' }}</td>
                    <td class="px-4 py-2">
                        @php
                            $statusVisual = $solicitacao->status === 'NAO_ENVIADO'
                                ? 'CANCELADO'
                                : ($solicitacao->status === 'CONFIRMADO' && trim((string) ($solicitacao->tracking_code ?? '')) !== '' ? 'ENVIADO' : $solicitacao->status);
                            $motivoStatus = trim((string) ($solicitacao->justificativa_cancelamento ?? ''));
                            if (mb_strtolower($motivoStatus, 'UTF-8') === 'sem estoque no momento') {
                                $motivoStatus = 'Sem estoque';
                            }
                        @endphp
                        <x-status-badge :status="$statusVisual" :color-map="$statusColors" />
                        @if($solicitacao->status === 'CONFIRMADO' && trim((string) ($solicitacao->tracking_code ?? '')) === '')
                            <div class="mt-0.5 max-w-[140px] truncate text-[11px] leading-3 text-gray-400" title="{{ trim((string) ($solicitacao->tracking_code ?? '')) !== '' ? 'Enviado' : 'Aguardando envio' }}">
                                {{ trim((string) ($solicitacao->tracking_code ?? '')) !== '' ? 'Enviado' : 'Aguardando envio' }}
                            </div>
                        @endif
                        @if(in_array($solicitacao->status, ['NAO_ENVIADO', 'CANCELADO'], true) && $motivoStatus !== '')
                            <div class="sol-index__reason mt-1 max-w-[180px] text-[11px] leading-4 text-slate-500" title="{{ $motivoStatus }}">
                                <span class="font-medium text-slate-400">Motivo:</span> {{ $motivoStatus }}
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-2">{{ optional($solicitacao->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2">
                        <div class="text-gray-900 dark:text-gray-100">{{ optional($solicitacao->updated_at)->format('d/m/Y H:i') }}</div>
                        @php
                            $usuarioUltimaMovimentacao = trim((string) ($solicitacao->ultimoHistoricoStatus?->usuario?->NOMEUSER ?? $solicitacao->ultimoHistoricoStatus?->usuario?->NMLOGIN ?? ''));
                        @endphp
                        @if($usuarioUltimaMovimentacao !== '')
                            <div class="text-xs text-gray-500">por {{ $shortPersonName($usuarioUltimaMovimentacao) }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @php
                            $isOwner = $currentUserId
                                && (string) $solicitacao->solicitante_id === (string) $currentUserId;
                            if (!$isOwner && $currentUserMatricula !== '') {
                                $isOwner = trim((string) ($solicitacao->solicitante_matricula ?? '')) === $currentUserMatricula;
                            }
                            $canConfirm = ($currentUser?->isAdmin() ?? false) || ($currentUser?->temAcessoTela('1019') ?? false);
                            $canForward = (($currentUser?->isAdmin() ?? false) || ($currentUser?->temAcessoTela('1012') ?? false))
                                && $solicitacao->status === 'AGUARDANDO_CONFIRMACAO';
                            $canRelease = (($currentUser?->isAdmin() ?? false) || ($currentUser?->temAcessoTela('1020') ?? false))
                                && $solicitacao->status === 'LIBERACAO';
                            $canSend = (($currentUser?->isAdmin() ?? false) || ($currentUser?->temAcessoTela('1014') ?? false))
                                && $solicitacao->status === 'CONFIRMADO'
                                && trim((string) ($solicitacao->tracking_code ?? '')) === '';
                            $canCancel = (($currentUser?->isAdmin() ?? false) || ($currentUser?->temAcessoTela('1015') ?? false))
                                && !in_array($solicitacao->status, ['CANCELADO', 'RECEBIDO'], true);
                        @endphp
                        <div class="flex items-center gap-2" @click.stop>
                            @if($canConfirm && $solicitacao->status === 'PENDENTE')
                                <button type="button" title="Confirmar" @click="mostrarModalConfirmar({{ $solicitacao->id }})"
                                    class="inline-flex items-center justify-center p-1.5 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                            @endif

                            @if($canForward)
                                <button type="button" title="Encaminhar para liberação" @click="mostrarModalEncaminharLiberacao({{ $solicitacao->id }})"
                                    class="inline-flex items-center justify-center p-1.5 text-violet-600 dark:text-violet-400 hover:bg-violet-100 dark:hover:bg-violet-900/30 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </button>
                            @endif

                            @if($canRelease)
                                <button type="button" title="Liberar pedido" @click="mostrarModalAprovar({{ $solicitacao->id }})"
                                    class="inline-flex items-center justify-center p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            @endif

                            @if($canSend)
                                <button type="button" title="Enviar pedido" @click="mostrarModalEnviar({{ $solicitacao->id }})"
                                    class="inline-flex items-center justify-center p-1.5 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </button>
                            @endif

                            @if($canCancel)
                                <button type="button" title="Cancelar" @click="mostrarModalCancelar({{ $solicitacao->id }})"
                                    class="inline-flex items-center justify-center p-1.5 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @endif

                            @if($isAdminUser || $isOwner)
                                <form method="POST" action="{{ route('solicitacoes-bens.destroy', $solicitacao) }}" onsubmit="return confirm('Remover a solicitação #{{ $solicitacao->id }}?');" class="inline" @click.stop>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center p-1.5 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition" title="Remover">
                                        <x-heroicon-o-trash class="h-4 w-4" />
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-3 text-center text-gray-500">Nenhuma solicitação encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $solicitacoes->links() }}
    </div>
</div>
