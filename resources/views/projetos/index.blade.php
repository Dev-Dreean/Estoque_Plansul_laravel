<x-app-layout>
    <div class="py-12">
        <div class="w-full sm:px-6 lg:px-8">
            <div class="flex justify-end mb-4">
                <a href="{{ route('projetos.create') }}" class="bg-plansul-blue hover:bg-opacity-90 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <x-heroicon-o-plus-circle class="w-5 h-5 mr-2" />
                    <span>Cadastrar Novo Projeto</span>
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="w-full text-base text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Cód. Projeto</th>
                                    <th class="px-4 py-3">Nome do Projeto</th>
                                    <th class="px-4 py-3">Filial (Local)</th>
                                    <th class="px-4 py-3">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($projetos as $projeto)
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer"
                                    x-data="{ editUrl: '{{ route('projetos.edit', $projeto) }}' }"
                                    @click="window.location.href = editUrl">
                                    <td class="px-4 py-2 font-bold">{{ $projeto->CDPROJETO }}</td>
                                    <td class="px-4 py-2">{{ $projeto->NOMEPROJETO }}</td>
                                    <td class="px-4 py-2">{{ $projeto->LOCAL }}</td>
                                    <td class="px-4 py-2">
                                        <div class="flex items-center">
                                            <form action="{{ route('projetos.destroy', $projeto) }}" method="POST"
                                                onsubmit="return confirm('Tem certeza que deseja apagar este projeto?');"
                                                @click.stop>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-red-600 dark:text-red-500 hover:underline">Apagar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center">Nenhum projeto encontrado.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $projetos->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>