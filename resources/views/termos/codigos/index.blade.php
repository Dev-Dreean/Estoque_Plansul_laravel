<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-200 leading-tight">
            Gerenciar Códigos de Termo
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-md border border-green-400/60 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-sm text-green-800 dark:text-green-300">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 rounded-md border border-red-400/60 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-800 dark:text-red-300">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Buscar</label>
                            <input type="text" name="q" value="{{ $q }}" placeholder="Código..."
                                   class="h-10 px-2 sm:px-3 w-48 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500/50" />
                        </div>
                        <x-primary-button type="submit">Filtrar</x-primary-button>
                        <a href="{{ route('termos.codigos.gerenciar') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">Limpar</a>
                        <div class="ml-auto flex flex-col gap-1">
                            <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Novo código</label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="codigo" form="formNovoCodigo" value="{{ old('codigo', $sugestao) }}" class="h-10 px-2 sm:px-3 w-40 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500/50" />
                                <form method="POST" action="{{ route('termos.codigos.salvar') }}" id="formNovoCodigo">
                                    @csrf
                                    <x-primary-button type="submit">Cadastrar</x-primary-button>
                                </form>
                            </div>
                        </div>
                        <div class="ml-auto hidden md:block">
                            <a href="{{ route('patrimonios.atribuir') }}" class="inline-flex items-center text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Voltar para Atribuir</a>
                        </div>
                    </form>

                    <div class="relative overflow-hidden rounded-md ring-1 ring-gray-200 dark:ring-gray-700">
                        <div class="overflow-x-auto max-h-[60vh]">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                                    <tr class="text-left">
                                        <th class="px-4 py-3 text-[11px] font-semibold tracking-wide text-gray-600 dark:text-gray-300 uppercase">Código</th>
                                        <th class="px-4 py-3 text-[11px] font-semibold tracking-wide text-gray-600 dark:text-gray-300 uppercase">Situação</th>
                                        <th class="px-4 py-3 text-[11px] font-semibold tracking-wide text-gray-600 dark:text-gray-300 uppercase">Qtd. Itens</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @forelse($codigos as $c)
                                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition-colors">
                                            <td class="px-4 py-2 font-mono text-sm">{{ $c['codigo'] }}</td>
                                            <td class="px-4 py-2">
                                                @if($c['usado'])
                                                    <span class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-0.5 text-[11px] font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-900/20 dark:text-yellow-300 dark:ring-yellow-400/30">Usado</span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-[11px] font-medium text-green-800 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/20 dark:text-green-300 dark:ring-green-400/30">Livre</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2">{{ $c['qtd'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 text-sm">Nenhum código encontrado.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-4 md:hidden">
                        <a href="{{ route('patrimonios.atribuir') }}" class="inline-flex items-center text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Voltar para Atribuir</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>