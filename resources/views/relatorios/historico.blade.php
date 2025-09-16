<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Histórico de Movimentação
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- Filtros -->
                    <form method="GET" class="mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <label class="block text-sm mb-1">Nº Patrimônio</label>
                                    <input type="number" name="nupatr" value="{{ request('nupatr') }}" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm mb-1">Cod. Projeto</label>
                                    <input type="number" name="codproj" value="{{ request('codproj') }}" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm mb-1">Usuário</label>
                                <input type="text" name="usuario" value="{{ request('usuario') }}" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md">
                            </div>
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <label class="block text-sm mb-1">De</label>
                                    <input type="date" name="de" value="{{ request('de') }}" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm mb-1">Até</label>
                                    <input type="date" name="ate" value="{{ request('ate') }}" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md">
                                </div>
                            </div>
                            <div class="flex items-end gap-2">
                                <div>
                                    <label class="block text-sm mb-1">Por página</label>
                                    <select name="per_page" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md">
                                        @foreach([15,30,50,100] as $pp)
                                            <option value="{{ $pp }}" {{ request('per_page',30)==$pp ? 'selected' : '' }}>{{ $pp }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="px-4 py-2 bg-gray-800 text-white rounded-md mt-6">Filtrar</button>
                                <a href="{{ route('relatorios.historico') }}" class="px-4 py-2 bg-white dark:bg-gray-700 border rounded-md mt-6">Limpar</a>
                            </div>
                        </div>
                    </form>

                    <!-- Tabela com scroll responsivo -->
                    <div class="overflow-x-auto">
                        <div class="overflow-y-auto max-h-screen md:max-h-96 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <table class="min-w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2">Nupatr</th>
                                        <th class="px-4 py-2">CodProj</th>
                                        <th class="px-4 py-2">Usuario</th>
                                        <th class="px-4 py-2">DtOperacao</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($historico as $mov)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-4 py-2">{{ $mov->NUPATRIMONIO }}</td>
                                        <td class="px-4 py-2">{{ $mov->CDPROJETO ?? '—' }}</td>
                                        <td class="px-4 py-2">{{ $mov->USUARIO ?? '—' }}</td>
                                        <td class="px-4 py-2">{{ \Carbon\Carbon::parse($mov->DTOPERACAO)->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">Sem registros</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Paginação -->
                    <div class="mt-4">
                        {{ $historico->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
