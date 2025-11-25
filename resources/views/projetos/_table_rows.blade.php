{{-- Renderiza apenas as linhas <tr> da tabela (sem diretivas Alpine aqui) --}}
{{-- jscs:disable --}}
{{-- jshint ignore:start --}}
@forelse ($locais as $local)
<tr class="border-b dark:border-gray-700 transition hover:bg-gray-50 dark:hover:bg-gray-600"
    data-local-id="{{ $local->id }}"
    data-local-name="{{ $local->delocal }}">

    <td class="px-4 py-2" onclick="event.stopPropagation();">
        <input
            type="checkbox"
            class="checkbox-local rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer"
            value="{{ $local->id }}"
            data-local-id="{{ $local->id }}">
    </td>

    <td class="px-4 py-2 font-bold cursor-pointer" onclick="window.location.href='{{ route('projetos.edit', $local) }}'">
        {{ $local->cdlocal }}
    </td>

    <td class="px-4 py-2 cursor-pointer" onclick="window.location.href='{{ route('projetos.edit', $local) }}'">
        {{ $local->delocal }}
    </td>

    <td class="px-4 py-2 cursor-pointer" onclick="window.location.href='{{ route('projetos.edit', $local) }}'">
        @if($local->projeto)
        <div class="flex flex-col leading-tight gap-0.5">
            <span class="text-xs font-mono text-blue-600 dark:text-blue-400">{{ $local->projeto->CDPROJETO ?? '—' }}</span>
            <span class="text-xs text-gray-600 dark:text-gray-400 truncate" style="max-width: 150px;">{{ $local->projeto->NOMEPROJETO ?? '—' }}</span>
        </div>
        @else
        <span>—</span>
        @endif
    </td>

    <td class="px-4 py-2" onclick="event.stopPropagation();">
        <div class="flex items-center space-x-4">
            <a href="{{ route('projetos.duplicate', $local) }}" title="Duplicar para novo local" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800" onclick="event.stopPropagation();">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
            </a>
            @if(Auth::user()->isSuperAdmin())
            <button 
                type="button"
                class="text-red-600 dark:text-red-500 hover:text-red-700 delete-btn"
                data-local-id="{{ $local->id }}"
                data-local-name="{{ $local->delocal }}"
                title="Apagar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="5" class="px-6 py-4 text-center">Nenhum local encontrado.</td>
</tr>
@endforelse
{{-- jshint ignore:end --}}
