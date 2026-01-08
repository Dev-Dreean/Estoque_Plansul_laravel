<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Solicitacoes de Bens') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
                    <span class="font-semibold">Sucesso:</span> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
                    <span class="font-semibold">Erro:</span> {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
                    <span class="font-semibold">Erro:</span> {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex flex-col gap-4 mb-6">
                        <form method="GET" action="{{ route('solicitacoes-bens.index') }}" class="grid gap-4 md:grid-cols-5 items-end">
                            <div class="md:col-span-2">
                                <x-input-label for="search" value="Buscar" />
                                <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" value="{{ request('search') }}" placeholder="Nome, matricula, setor, local" />
                            </div>
                            <div>
                                <x-input-label for="status" value="Status" />
                                <select id="status" name="status" class="input-base mt-1 block w-full">
                                    <option value="">Todos</option>
                                    @foreach($statusOptions as $status)
                                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="uf" value="UF" />
                                <x-text-input id="uf" name="uf" type="text" maxlength="2" class="mt-1 block w-full uppercase" value="{{ request('uf') }}" placeholder="UF" />
                            </div>
                            <div>
                                <x-input-label for="per_page" value="Itens por pagina" />
                                <select id="per_page" name="per_page" class="input-base mt-1 block w-full">
                                    @foreach([10, 30, 50, 100, 200] as $opt)
                                        <option value="{{ $opt }}" @selected((int) request('per_page', 30) === $opt)>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-5 flex items-center gap-3">
                                <x-primary-button class="h-10 px-4">Filtrar</x-primary-button>
                                <a href="{{ route('solicitacoes-bens.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Limpar</a>
                            </div>
                        </form>

                        <div>
                            <a href="{{ route('solicitacoes-bens.create') }}" class="bg-plansul-blue hover:bg-opacity-90 text-white font-semibold py-2 px-4 rounded inline-flex items-center">
                                <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                                <span>Nova solicitacao</span>
                            </a>
                        </div>
                    </div>

                    @php
                        $statusColors = [
                            'PENDENTE' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                            'SEPARADO' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                            'CONCLUIDO' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                            'CANCELADO' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                        ];
                    @endphp

                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Numero</th>
                                    <th class="px-4 py-3">Solicitante</th>
                                    <th class="px-4 py-3">Setor</th>
                                    <th class="px-4 py-3">Local destino</th>
                                    <th class="px-4 py-3">UF</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Itens</th>
                                    <th class="px-4 py-3">Criado</th>
                                    <th class="px-4 py-3">Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($solicitacoes as $solicitacao)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">#{{ $solicitacao->id }}</td>
                                        <td class="px-4 py-3">
                                            <div class="text-gray-900 dark:text-gray-100">{{ $solicitacao->solicitante_nome ?? '-' }}</div>
                                            <div class="text-xs text-gray-500">{{ $solicitacao->solicitante_matricula ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3">{{ $solicitacao->setor ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $solicitacao->local_destino ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $solicitacao->uf ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <x-status-badge :status="$solicitacao->status" :color-map="$statusColors" />
                                        </td>
                                        <td class="px-4 py-3">{{ $solicitacao->itens_count ?? 0 }}</td>
                                        <td class="px-4 py-3">{{ optional($solicitacao->created_at)->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('solicitacoes-bens.show', $solicitacao) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Ver</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-6 text-center text-gray-500">Nenhuma solicitacao encontrada.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $solicitacoes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
