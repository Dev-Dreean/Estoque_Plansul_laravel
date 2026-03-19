<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-app leading-tight">
            Histórico de Solicitações de Bens
        </h2>
    </x-slot>

    <div class="py-8 bg-white dark:bg-slate-950">
        <div class="w-full sm:px-6 lg:px-8">
            @include('solicitacoes.partials.subnav')

            @if(($historicoDisponivel ?? true) === false)
                <div class="bg-amber-50/90 border border-amber-300 text-amber-900 shadow-sm sm:rounded-xl p-4 mb-4">
                    O histórico detalhado de status ainda não está disponível neste banco de dados. A tela continua acessível, mas sem registros até que essa tabela exista no ambiente.
                </div>
            @endif

            <div x-data="{ open: false }" class="p-4 rounded-xl mb-6 shadow-sm dark:bg-slate-900/90 dark:border-slate-700" style="background-color:#fcfbff;border:1px solid #e9ddff;">
                    <div class="flex justify-between items-center">
                    <h3 class="font-semibold text-lg text-slate-800 dark:text-slate-100">Filtros de Busca</h3>
                    <button
                        type="button"
                        @click="open = !open"
                        :aria-expanded="open.toString()"
                        aria-controls="filtros-historico-solicitacoes"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-md transition focus:outline-none focus:ring-2 dark:border-sky-900 dark:bg-sky-950/50 dark:text-sky-300 dark:hover:bg-sky-900/60"
                        style="border:1px solid #ddd6fe;background-color:#f8f5ff;color:#7c3aed;"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        <span class="sr-only">Expandir filtros</span>
                    </button>
                </div>

                    <div x-show="open" x-transition class="mt-4" style="display: none;">
                    <form method="GET" action="{{ route('solicitacoes-bens.historico') }}" id="filtros-historico-solicitacoes" class="flex flex-nowrap items-end gap-3 w-full">
                        <div class="flex-[2.6] min-w-0">
                            <label for="search" class="sr-only">Busca</label>
                            <input
                                type="text"
                                id="search"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Buscar por motivo, solicitante ou usuário"
                                class="h-10 px-3 w-full text-sm rounded-md shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                                style="border:1px solid #e9ddff;background-color:#ffffff;color:#334155;"
                            />
                        </div>

                        <div class="flex-1 min-w-0">
                            <label for="solicitacao_id" class="sr-only">Número da solicitação</label>
                            <input
                                type="number"
                                id="solicitacao_id"
                                name="solicitacao_id"
                                value="{{ request('solicitacao_id') }}"
                                placeholder="Nº Solicitação"
                                class="h-10 px-3 w-full text-sm rounded-md shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                                style="border:1px solid #e9ddff;background-color:#ffffff;color:#334155;"
                            />
                        </div>

                        <div class="flex-[1.2] min-w-0">
                            <label for="status" class="sr-only">Status</label>
                            <select id="status" name="status" class="h-10 px-3 w-full text-sm rounded-md shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" style="border:1px solid #e9ddff;background-color:#ffffff;color:#334155;">
                                <option value="">Todos os status</option>
                                @foreach(($statusOptions ?? []) as $status)
                                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex-1 min-w-0">
                            <label for="data_inicio" class="sr-only">Data início</label>
                            <input
                                type="date"
                                id="data_inicio"
                                name="data_inicio"
                                value="{{ request('data_inicio') }}"
                                class="h-10 px-3 w-full text-sm rounded-md shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                style="border:1px solid #e9ddff;background-color:#ffffff;color:#334155;"
                            />
                        </div>

                        <div class="flex-1 min-w-0">
                            <label for="data_fim" class="sr-only">Data fim</label>
                            <input
                                type="date"
                                id="data_fim"
                                name="data_fim"
                                value="{{ request('data_fim') }}"
                                class="h-10 px-3 w-full text-sm rounded-md shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                style="border:1px solid #e9ddff;background-color:#ffffff;color:#334155;"
                            />
                        </div>

                        <div class="w-[110px] shrink-0">
                            <label for="per_page" class="sr-only">Itens por página</label>
                            <select id="per_page" name="per_page" class="h-10 px-3 w-full text-sm rounded-md shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" style="border:1px solid #e9ddff;background-color:#ffffff;color:#334155;">
                                @foreach([10, 30, 50, 100, 200] as $opt)
                                    <option value="{{ $opt }}" @selected((int) request('per_page', 30) === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>

                        <x-primary-button class="h-10 px-4 shrink-0 focus:ring-violet-500" style="background-color:#6d28d9;">Filtrar</x-primary-button>
                        <a href="{{ route('solicitacoes-bens.historico') }}" class="h-10 inline-flex items-center px-3 text-sm rounded-md shrink-0 dark:text-slate-400 dark:hover:text-slate-100" style="color:#7c3aed;">
                            Limpar
                        </a>
                    </form>
                </div>
            </div>

            @php
                $formatDisplay = function (?string $valor): string {
                    $valor = trim((string) $valor);
                    if ($valor === '') {
                        return '-';
                    }

                    return mb_convert_case(mb_strtolower($valor, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
                };

                $normalizeStatus = function (?string $status): string {
                    $status = trim((string) $status);
                    if ($status === '') {
                        return '';
                    }

                    $status = mb_strtoupper($status, 'UTF-8');
                    $status = str_replace([' ', '-'], '_', $status);

                    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $status);
                    if ($ascii !== false && $ascii !== '') {
                        $status = $ascii;
                    }

                    $status = preg_replace('/[^A-Z_]/', '', $status) ?? '';
                    $status = preg_replace('/_+/', '_', $status) ?? '';

                    return trim($status, '_');
                };

                $statusBadge = function (string $status) use ($normalizeStatus): string {
                    return match ($normalizeStatus($status)) {
                        'CRIADO' => 'background-color:#cbd5e1;color:#000000;border-color:#94a3b8;',
                        'PENDENTE' => 'background-color:#facc15;color:#000000;border-color:#eab308;',
                        'AGUARDANDO_CONFIRMACAO' => 'background-color:#60a5fa;color:#000000;border-color:#3b82f6;',
                        'LIBERACAO' => 'background-color:#c4b5fd;color:#000000;border-color:#8b5cf6;',
                        'CONFIRMADO' => 'background-color:#c084fc;color:#000000;border-color:#9333ea;',
                        'RECEBIDO' => 'background-color:#4ade80;color:#000000;border-color:#16a34a;',
                        'NAO_ENVIADO' => 'background-color:#fb923c;color:#000000;border-color:#f97316;',
                        'NAO_RECEBIDO' => 'background-color:#fda4af;color:#000000;border-color:#f43f5e;',
                        'CANCELADO' => 'background-color:#f87171;color:#000000;border-color:#dc2626;',
                        default => 'background-color:#cbd5e1;color:#000000;border-color:#94a3b8;',
                    };
                };
                $statusLabel = function (string $status) use ($normalizeStatus): string {
                    return match ($normalizeStatus($status)) {
                        'CRIADO' => 'SOLICITADO',
                        'AGUARDANDO_CONFIRMACAO' => 'AGUARDANDO CONFIRMACAO',
                        'LIBERACAO' => 'LIBERACAO',
                        'CONFIRMADO' => 'ENVIO',
                        'ENVIADO' => 'ENVIADO',
                        'NAO_ENVIADO' => 'CANCELADO',
                        'NAO_RECEBIDO' => 'NAO RECEBIDO',
                        default => $normalizeStatus($status) !== '' ? $normalizeStatus($status) : '-',
                    };
                };
            @endphp

            <div class="space-y-3">
                @forelse($solicitacoes as $solicitacao)
                    @php
                        $historicos = $solicitacao->historicoStatus;
                        $ultimo = $historicos->first();
                        $statusAtualRaw = (string) ($ultimo?->status_novo ?? $solicitacao->status ?? 'PENDENTE');
                        if ($normalizeStatus($statusAtualRaw) === 'CONFIRMADO' && trim((string) ($solicitacao->tracking_code ?? '')) !== '') {
                            $statusAtualRaw = 'ENVIADO';
                        }
                        $statusAtual = $normalizeStatus($statusAtualRaw);
                        $badge = $statusBadge($statusAtualRaw);
                        $projetoCodigo = (string) ($solicitacao->projeto->CDPROJETO ?? '-');
                        $projetoNome = (string) ($solicitacao->projeto->NOMEPROJETO ?? '');
                    @endphp
                    <div x-data="{ expanded: false }" class="overflow-hidden rounded-2xl shadow-sm transition dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700" style="border:1px solid #ddd6fe;background-color:#f8f5ff;" @mouseenter="$el.style.backgroundColor='#f1eaff'" @mouseleave="$el.style.backgroundColor='#f8f5ff'">
                        <button type="button" @click="expanded = !expanded" class="w-full text-left px-4 py-3 transition" style="background-color:#f8f5ff;">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-bold text-slate-900 dark:text-white">Solicitação #{{ $solicitacao->id }}</span>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold border" style="{{ $badge }}">
                                            {{ $statusLabel($statusAtualRaw !== '' ? $statusAtualRaw : 'PENDENTE') }}
                                        </span>
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        <span class="font-semibold">Solicitante:</span> {{ $solicitacao->solicitante_nome ?? '-' }}
                                        <span class="mx-2">|</span>
                                        <span class="font-semibold">Projeto:</span> {{ $projetoCodigo }}@if($projetoNome !== '') - {{ $projetoNome }}@endif
                                        <span class="mx-2">|</span>
                                        <span class="font-semibold">Local:</span> {{ $formatDisplay($solicitacao->local_destino ?? '-') }}
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-xs text-slate-500 dark:text-slate-400">Última movimentação</div>
                                    <div class="text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ optional($ultimo?->created_at)->format('d/m/Y H:i') ?: '-' }}
                                    </div>
                                    <div class="mt-1 text-[11px] dark:text-sky-400" style="color:#8b5cf6;">
                                        <span x-text="expanded ? 'Contrair' : 'Expandir'"></span>
                                    </div>
                                </div>
                            </div>
                        </button>

                        <div x-show="expanded" x-transition class="p-4 dark:border-slate-700 dark:bg-slate-800/90" style="border-top:1px solid #ddd6fe;background-color:#f3edff;">
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[980px] table-fixed text-xs md:text-sm text-left text-slate-700 dark:text-slate-200">
                                    <thead class="uppercase text-[11px] dark:bg-slate-800 dark:text-slate-300" style="background-color:#ebe3ff;color:#6d28d9;">
                                        <tr>
                                            <th class="px-3 py-2 w-[180px] whitespace-nowrap">Data</th>
                                            <th class="px-3 py-2 w-[280px] whitespace-nowrap">Status</th>
                                            <th class="px-3 py-2 w-[360px] whitespace-nowrap">Detalhe</th>
                                            <th class="px-3 py-2 w-[240px] whitespace-nowrap">Usuário</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($historicos as $registro)
                                            @php
                                                $statusAnteriorRaw = (string) ($registro->status_anterior ?? '');
                                                $statusNovoRaw = (string) ($registro->status_novo ?? '');
                                                $statusAnterior = $normalizeStatus($statusAnteriorRaw);
                                                $statusNovo = $normalizeStatus($statusNovoRaw);
                                                if ($statusAnterior === '' && (string) ($registro->acao ?? '') === 'criado') {
                                                    $statusAnterior = 'CRIADO';
                                                    $statusAnteriorRaw = 'CRIADO';
                                                }
                                                $badgeAnterior = $statusAnterior !== '' ? $statusBadge($statusAnteriorRaw) : '';
                                                $statusNovoExibicao = $statusNovoRaw;
                                                if ($statusNovo === 'CONFIRMADO' && trim((string) ($registro->motivo ?? '')) !== '' && str_starts_with(trim((string) $registro->motivo), 'Rastreio:')) {
                                                    $statusNovoExibicao = 'ENVIADO';
                                                }
                                                $badgeLinha = $statusBadge($statusNovo !== '' ? $statusNovoExibicao : 'PENDENTE');
                                                $usuarioNome = trim((string) ($registro->usuario->NOMEUSER ?? $registro->usuario->NMLOGIN ?? '-'));
                                            @endphp
                                            <tr class="dark:border-slate-700" style="border-bottom:1px solid #ece7f7;background-color:{{ $loop->odd ? '#ffffff' : '#fcfaff' }};">
                                                <td class="px-3 py-2 align-middle whitespace-nowrap">
                                                    {{ optional($registro->created_at)->format('d/m/Y H:i') ?: '-' }}
                                                </td>
                                                <td class="px-3 py-2 align-middle whitespace-nowrap">
                                                    <div class="flex items-center gap-1.5 whitespace-nowrap">
                                                        @if($statusAnterior !== '')
                                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold border" style="{{ $badgeAnterior }}">
                                                                {{ $statusLabel($statusAnteriorRaw) }}
                                                            </span>
                                                            <svg class="w-3 h-3 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                            </svg>
                                                        @endif
                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold border" style="{{ $badgeLinha }}">
                                                            {{ $statusLabel($statusNovoExibicao) }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 align-middle whitespace-nowrap overflow-hidden text-ellipsis" title="{{ trim((string) ($registro->motivo ?? '')) !== '' ? $registro->motivo : 'Sem detalhe informado' }}">
                                                    @if(trim((string) ($registro->motivo ?? '')) !== '')
                                                        <span class="block overflow-hidden text-ellipsis whitespace-nowrap">{{ $registro->motivo }}</span>
                                                    @else
                                                        <span class="block overflow-hidden text-ellipsis whitespace-nowrap">Sem detalhe informado</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 align-middle whitespace-nowrap overflow-hidden text-ellipsis" title="{{ $usuarioNome !== '' ? $usuarioNome : '-' }}">
                                                    <span class="block overflow-hidden text-ellipsis whitespace-nowrap">{{ $usuarioNome !== '' ? $usuarioNome : '-' }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-3 py-4 text-center text-slate-500 dark:text-slate-400">
                                                    Nenhuma movimentação encontrada para esta solicitação.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-panel border border-app rounded-lg px-4 py-6 text-center text-muted">
                        Nenhuma movimentação encontrada.
                    </div>
                @endforelse
            </div>

            <div class="mt-4 flex items-center justify-between">
                <div>
                    @if($solicitacoes->hasPages())
                        <div>{{ $solicitacoes->links() }}</div>
                    @endif
                </div>
                <div class="text-sm text-muted">
                    Mostrando {{ $solicitacoes->firstItem() ?? 0 }} até {{ $solicitacoes->lastItem() ?? 0 }} de {{ $solicitacoes->total() }} solicitações
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
