<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-200 leading-tight">
            Gerenciar Códigos de Termo
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if($errors->any())
            <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 md:p-6">
                <form method="GET" class="mb-4 flex gap-2">
                    <input type="text" name="q" value="{{ $q }}" placeholder="Buscar código..."
                        class="h-10 px-3 border border-gray-300 rounded w-48">
                    <x-primary-button type="submit">Filtrar</x-primary-button>
                    <a href="{{ route('termos.codigos.gerenciar') }}" class="text-sm text-gray-700">Limpar</a>
                </form>

                <div class="flex items-end gap-2 mb-4">
                    <div>
                        <label class="text-sm text-gray-700">Novo código</label>
                        <input type="number" name="CÃ³digo" form="formNovoCodigo" value="{{ old('codigo', $sugestao) }}" class="h-10 px-3 border border-gray-300 rounded w-40" />
                    </div>
                    <form method="POST" action="{{ route('termos.codigos.salvar') }}" id="formNovoCodigo">
                        @csrf
                        <x-primary-button type="submit">Cadastrar</x-primary-button>
                    </form>
                    <div class="ml-auto">
                        <a href="{{ route('patrimonios.atribuir') }}" class="text-sm text-indigo-700 hover:underline">Voltar para Atribuir</a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left">Código</th>
                                <th class="px-4 py-2 text-left">Situação</th>
                                <th class="px-4 py-2 text-left">Qtd. Itens</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($codigos as $c)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-4 py-2 font-mono">{{ $c['codigo'] }}</td>
                                <td class="px-4 py-2">
                                    @if($c['usado'])
                                    <span class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-0.5 text-[11px] font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Usado</span>
                                    @else
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-[11px] font-medium text-green-800 ring-1 ring-inset ring-green-600/20">Livre</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $c['qtd'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-gray-500">Nenhum código encontrado.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>