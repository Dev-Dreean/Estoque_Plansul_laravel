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
    $currentUserLogin = mb_strtoupper(trim((string) ($currentUser?->NMLOGIN ?? '')), 'UTF-8');
    $isAdminUser = $currentUser?->isAdmin() ?? false;
    $isTiagoFlow = in_array($currentUserMatricula, ['185895'], true) || in_array($currentUserLogin, ['TIAGOP'], true);
    $isBeatrizFlow = in_array($currentUserMatricula, ['182687'], true) || in_array($currentUserLogin, ['BEA.SC'], true);
    $isBrunoFlow = in_array($currentUserMatricula, ['11829'], true) || in_array($currentUserLogin, ['BRUNO'], true);
    $canConfirmFlow = $isAdminUser || (($currentUser?->temAcessoTela((string) \App\Models\User::TELA_SOLICITACOES_TRIAGEM_INICIAL) ?? false) && ($isTiagoFlow || $isBeatrizFlow));
    $canRegisterMeasures = $isAdminUser || (($currentUser?->temAcessoTela((string) \App\Models\User::TELA_SOLICITACOES_ATUALIZAR) ?? false) && $isTiagoFlow);
    $canRegisterQuote = $isAdminUser || (($currentUser?->temAcessoTela((string) \App\Models\User::TELA_SOLICITACOES_ATUALIZAR) ?? false) && $isBeatrizFlow);
    $canReleaseFlow = $isAdminUser || (($currentUser?->temAcessoTela((string) \App\Models\User::TELA_SOLICITACOES_LIBERACAO_ENVIO) ?? false) && $isBrunoFlow);
    $canSendFlow = $isAdminUser || (($currentUser?->temAcessoTela((string) \App\Models\User::TELA_SOLICITACOES_APROVAR) ?? false) && ($isTiagoFlow || $isBeatrizFlow));
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
            </tr>
        </thead>
        <tbody>
            @forelse($solicitacoes as $solicitacao)
                @php
                    $hasLogisticsData = method_exists($solicitacao, 'hasLogisticsData') ? $solicitacao->hasLogisticsData() : false;
                    $hasQuoteData = method_exists($solicitacao, 'hasQuoteData') ? $solicitacao->hasQuoteData() : false;
                    $hasShipmentData = method_exists($solicitacao, 'hasShipmentData') ? $solicitacao->hasShipmentData() : (trim((string) ($solicitacao->tracking_code ?? '')) !== '' || trim((string) ($solicitacao->invoice_number ?? '')) !== '');
                    $awaitingRequesterDecision = method_exists($solicitacao, 'isAwaitingRequesterDecision') ? $solicitacao->isAwaitingRequesterDecision() : false;
                    $readyToShip = method_exists($solicitacao, 'isReadyToShip') ? $solicitacao->isReadyToShip() : false;
                    $statusVisual = $solicitacao->status === 'NAO_ENVIADO'
                        ? 'CANCELADO'
                        : $solicitacao->status;
                    $motivoStatus = trim((string) ($solicitacao->justificativa_cancelamento ?? ''));
                    $statusAuxiliar = match (true) {
                        $solicitacao->status === 'AGUARDANDO_CONFIRMACAO' && !$hasLogisticsData => 'Aguardando medidas e peso',
                        $solicitacao->status === 'AGUARDANDO_CONFIRMACAO' && $hasLogisticsData => 'Aguardando cotações da Beatriz',
                        $solicitacao->status === 'LIBERACAO' => 'Aguardando liberação do Bruno',
                        $solicitacao->status === 'CONFIRMADO' && !$hasShipmentData => 'Aguardando envio',
                        $solicitacao->status === 'CONFIRMADO' && $hasShipmentData => 'Enviado, aguardando recebimento',
                        default => '',
                    };
                    if (mb_strtolower($motivoStatus, 'UTF-8') === 'sem estoque no momento') {
                        $motivoStatus = 'Sem estoque';
                    }

                    $isOwner = $currentUserId
                        && (string) $solicitacao->solicitante_id === (string) $currentUserId;
                    if (!$isOwner && $currentUserMatricula !== '') {
                        $isOwner = trim((string) ($solicitacao->solicitante_matricula ?? '')) === $currentUserMatricula;
                    }

                    $currentUserPendingLabel = null;
                    if ($solicitacao->status === 'PENDENTE' && $canConfirmFlow) {
                        $currentUserPendingLabel = 'Aprovar solicitação';
                    } elseif ($solicitacao->status === 'AGUARDANDO_CONFIRMACAO' && !$hasLogisticsData && $canRegisterMeasures) {
                        $currentUserPendingLabel = 'Registrar medidas e peso';
                    } elseif ($solicitacao->status === 'AGUARDANDO_CONFIRMACAO' && $hasLogisticsData && $canRegisterQuote) {
                        $currentUserPendingLabel = 'Cadastrar cotações';
                    } elseif ($solicitacao->status === 'LIBERACAO' && $canReleaseFlow && $hasQuoteData && $awaitingRequesterDecision) {
                        $currentUserPendingLabel = 'Liberar envio';
                    } elseif ($solicitacao->status === 'CONFIRMADO' && !$hasShipmentData && $canSendFlow && (!$hasQuoteData || !empty($solicitacao->quote_approved_at) || $readyToShip)) {
                        $currentUserPendingLabel = 'Registrar envio';
                    } elseif ($solicitacao->status === 'CONFIRMADO' && $hasShipmentData && ($isAdminUser || $isOwner)) {
                        $currentUserPendingLabel = 'Confirmar recebimento';
                    }
                @endphp
                <tr
                    class="sol-index__row {{ $loop->odd ? 'sol-index__row--odd' : 'sol-index__row--even' }} {{ $currentUserPendingLabel ? 'sol-index__row--attention' : '' }}"
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
                        <x-status-badge :status="$statusVisual" :color-map="$statusColors" />
                        @if($currentUserPendingLabel)
                            <div class="sol-index__pending-flag" data-flow-stage="{{ $solicitacao->status }}">
                                Pendente para você
                            </div>
                            <div class="sol-index__pending-task" title="{{ $currentUserPendingLabel }}">
                                {{ $currentUserPendingLabel }}
                            </div>
                        @endif
                        @if($statusAuxiliar !== '')
                            <div class="mt-0.5 max-w-[180px] truncate text-[11px] leading-3 text-gray-400" title="{{ $statusAuxiliar }}">
                                {{ $statusAuxiliar }}
                            </div>
                        @endif
                        @if(in_array($solicitacao->status, ['NAO_ENVIADO', 'CANCELADO'], true) && $motivoStatus !== '')
                            <div class="sol-index__reason mt-1 max-w-[180px] text-[11px] leading-4 text-slate-500" title="{{ $motivoStatus }}">
                                {{ $motivoStatus }}
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
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-3 text-center text-gray-500">Nenhuma solicitação encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $solicitacoes->links() }}
    </div>
</div>
