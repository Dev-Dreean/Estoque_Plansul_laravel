<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-app leading-tight">
            Histórico de Solicitações de Bens
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="w-full sm:px-6 lg:px-8">
            @include('solicitacoes.partials.subnav')

            @if(($historicoDisponivel ?? true) === false)
                <div class="bg-amber-50 border border-amber-300 text-amber-800 shadow-sm sm:rounded-lg p-4 mb-4">
                    O histórico detalhado de status ainda não está disponível neste banco de dados. A tela continua acessível, mas sem registros até que essa tabela exista no ambiente.
                </div>
            @endif

            <div x-data="{ open: false }" class="bg-panel border border-app p-4 rounded-lg mb-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-semibold text-lg text-app">Filtros de Busca</h3>
                    <button
                        type="button"
                        @click="open = !open"
                        :aria-expanded="open.toString()"
                        aria-controls="filtros-historico-solicitacoes"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-app bg-panel-alt text-app hover:opacity-90 transition focus:outline-none focus:ring-2 focus:ring-indigo-500"
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
                                class="h-10 px-3 w-full text-sm border border-app bg-panel-alt text-app placeholder:text-soft rounded-md"
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
                                class="h-10 px-3 w-full text-sm border border-app bg-panel-alt text-app placeholder:text-soft rounded-md"
                            />
                        </div>

                        <div class="flex-[1.2] min-w-0">
                            <label for="status" class="sr-only">Status</label>
                            <select id="status" name="status" class="h-10 px-3 w-full text-sm border border-app bg-panel-alt text-app rounded-md">
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
                                class="h-10 px-3 w-full text-sm border border-app bg-panel-alt text-app rounded-md"
                            />
                        </div>

                        <div class="flex-1 min-w-0">
                            <label for="data_fim" class="sr-only">Data fim</label>
                            <input
                                type="date"
                                id="data_fim"
                                name="data_fim"
                                value="{{ request('data_fim') }}"
                                class="h-10 px-3 w-full text-sm border border-app bg-panel-alt text-app rounded-md"
                            />
                        </div>

                        <div class="w-[110px] shrink-0">
                            <label for="per_page" class="sr-only">Itens por página</label>
                            <select id="per_page" name="per_page" class="h-10 px-3 w-full text-sm border border-app bg-panel-alt text-app rounded-md">
                                @foreach([10, 30, 50, 100, 200] as $opt)
                                    <option value="{{ $opt }}" @selected((int) request('per_page', 30) === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>

                        <x-primary-button class="h-10 px-4 shrink-0">Filtrar</x-primary-button>
                        <a href="{{ route('solicitacoes-bens.historico') }}" class="h-10 inline-flex items-center px-3 text-sm text-muted hover:text-app rounded-md shrink-0">
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
                        'CONFIRMADO' => 'background-color:#c084fc;color:#000000;border-color:#9333ea;',
                        'RECEBIDO' => 'background-color:#4ade80;color:#000000;border-color:#16a34a;',
                        'NAO_ENVIADO' => 'background-color:#fb923c;color:#000000;border-color:#f97316;',
                        'NAO_RECEBIDO' => 'background-color:#fda4af;color:#000000;border-color:#f43f5e;',
                        'CANCELADO' => 'background-color:#f87171;color:#000000;border-color:#dc2626;',
                        default => 'background-color:#cbd5e1;color:#000000;border-color:#94a3b8;',
                    };
                };
            @endphp

            <div class="space-y-3">
                @forelse($solicitacoes as $solicitacao)
                    @php
                        $historicos = $solicitacao->historicoStatus;
                        $ultimo = $historicos->first();
                        $statusAtualRaw = (string) ($ultimo?->status_novo ?? $solicitacao->status ?? 'PENDENTE');
                        $statusAtual = $normalizeStatus($statusAtualRaw);
                        $badge = $statusBadge($statusAtualRaw);
                        $projetoCodigo = (string) ($solicitacao->projeto->CDPROJETO ?? '-');
                        $projetoNome = (string) ($solicitacao->projeto->NOMEPROJETO ?? '');
                    @endphp
                    <div x-data="{ expanded: false }" class="bg-panel shadow-sm rounded-lg border border-app overflow-hidden">
                        <button type="button" @click="expanded = !expanded" class="w-full text-left px-4 py-3 hover:bg-panel-alt transition">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-bold text-app">Solicitação #{{ $solicitacao->id }}</span>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold border" style="{{ $badge }}">
                                            {{ $statusAtual !== '' ? $statusAtual : 'PENDENTE' }}
                                        </span>
                                    </div>
                                    <div class="mt-1 text-xs text-muted">
                                        <span class="font-semibold">Solicitante:</span> {{ $solicitacao->solicitante_nome ?? '-' }}
                                        <span class="mx-2">|</span>
                                        <span class="font-semibold">Projeto:</span> {{ $projetoCodigo }}@if($projetoNome !== '') - {{ $projetoNome }}@endif
                                        <span class="mx-2">|</span>
                                        <span class="font-semibold">Local:</span> {{ $formatDisplay($solicitacao->local_destino ?? '-') }}
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-xs text-muted">Última movimentação</div>
                                    <div class="text-sm font-semibold text-app">
                                        {{ optional($ultimo?->created_at)->format('d/m/Y H:i') ?: '-' }}
                                    </div>
                                    <div class="mt-1 text-[11px] text-indigo-500">
                                        <span x-text="expanded ? 'Contrair' : 'Expandir'"></span>
                                    </div>
                                </div>
                            </div>
                        </button>

                        <div x-show="expanded" x-transition class="border-t border-app p-4 bg-panel-alt">
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs md:text-sm text-left text-app">
                                    <thead class="uppercase text-[11px] bg-panel text-muted">
                                        <tr>
                                            <th class="px-3 py-2 w-[140px]">Data</th>
                                            <th class="px-3 py-2 w-[180px]">Status</th>
                                            <th class="px-3 py-2">Detalhe</th>
                                            <th class="px-3 py-2 w-[200px]">Usuário</th>
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
                                                $badgeLinha = $statusBadge($statusNovo !== '' ? $statusNovoRaw : 'PENDENTE');
                                                $usuarioNome = trim((string) ($registro->usuario->NOMEUSER ?? $registro->usuario->NMLOGIN ?? '-'));
                                            @endphp
                                            <tr class="border-b border-app">
                                                <td class="px-3 py-2 whitespace-nowrap">
                                                    {{ optional($registro->created_at)->format('d/m/Y H:i') ?: '-' }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        @if($statusAnterior !== '')
                                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold border" style="{{ $badgeAnterior }}">
                                                                {{ $statusAnterior }}
                                                            </span>
                                                            <svg class="w-3 h-3 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                            </svg>
                                                        @endif
                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold border" style="{{ $badgeLinha }}">
                                                            {{ $statusNovo !== '' ? $statusNovo : '-' }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2">
                                                    @if(trim((string) ($registro->motivo ?? '')) !== '')
                                                        {{ $registro->motivo }}
                                                    @else
                                                        Sem detalhe informado
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2">{{ $usuarioNome !== '' ? $usuarioNome : '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-3 py-4 text-center text-muted">
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
