{{-- Caminho: resources/views/projetos/_table_partial.blade.php --}}
{{-- NENHUMA MUDANÇA NECESSÁRIA AQUI, O CÓDIGO ABAIXO JÁ ESTÁ CORRETO --}}

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

                {{-- Esta linha busca o nome do projeto. Com o Model corrigido, ela vai funcionar. --}}
                <td class="px-4 py-2">{{ $local->projeto->NOMEPROJETO ?? '—' }}</td>

                <td class="px-4 py-2" @click.stop>
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('projetos.duplicate', $local) }}" title="Duplicar para novo local" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800" onclick="event.stopPropagation();">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </a>
                        <form action="{{ route('projetos.destroy', $local) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja apagar este local?');" onclick="event.stopPropagation();">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Apagar" class="text-red-600 dark:text-red-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
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