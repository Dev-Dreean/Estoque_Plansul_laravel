<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <table class="w-full text-base text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th class="px-4 py-3">Cód. Local</th>
                <th class="px-4 py-3">Nome do Local</th>
                <th class="px-4 py-3">Projeto Associado</th>
                <th class="px-4 py-3">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($locais as $local)
            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer"
                x-data="{ editUrl: '{{ route('projetos.edit', $local) }}' }"
                @click="window.location.href = editUrl">
                <td class="px-4 py-2 font-bold">{{ $local->cdlocal }}</td>
                <td class="px-4 py-2">{{ $local->delocal }}</td>
                <td class="px-4 py-2">
                    {{ $local->projeto->NOMEPROJETO ?? '' }}
                </td>
                <td class="px-4 py-2" @click.stop>
                    <div class="flex items-center space-x-4">
                        <form action="{{ route('projetos.destroy', $local) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja apagar este local?');" onclick="event.stopPropagation();">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Apagar" class="text-red-600 dark:text-red-500">
                                <x-heroicon-o-trash class="w-5 h-5" />
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-6 py-4 text-center">Nenhum local encontrado.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">
    {{ $locais->links() }}
</div>