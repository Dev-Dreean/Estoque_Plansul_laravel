@forelse($locais as $local)
<tr data-local-id="{{ $local['id'] }}" class="border-b dark:border-gray-700 bg-white dark:bg-gray-800">
    <td class="px-4 py-2">
        <input
            type="checkbox"
            class="checkbox-local rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer"
            data-local-id="{{ $local['id'] }}">
    </td>
    <td class="px-4 py-2 font-mono text-gray-900 dark:text-gray-100">{{ $local['cdlocal'] }}</td>
    <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $local['delocal'] }}</td>
    <td class="px-4 py-2">
        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ ($local['tipo_local'] ?? 'PADRAO') === 'ESTOQUE_TI' ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300' : (($local['tipo_local'] ?? 'PADRAO') === 'TI_EM_USO' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200') }}">
            {{ $local['tipo_local_label'] ?? 'Padrão' }}
        </span>
    </td>
    <td class="px-4 py-2">
        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ ($local['fluxo_responsavel'] ?? 'PADRAO') === 'TI' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
            {{ $local['fluxo_responsavel_label'] ?? 'Padrão' }}
        </span>
    </td>
    <td class="px-4 py-2">
        <div class="leading-tight">
            <div class="text-gray-900 dark:text-gray-100">{{ $local['projeto_nome'] ?? '-' }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Cód: {{ $local['projeto_codigo'] ?? '-' }}</div>
        </div>
    </td>
    <td class="px-4 py-2">
        <div class="flex items-center gap-2">
            <a href="{{ route('projetos.edit', $local['id']) }}" class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Editar local">
                <x-heroicon-o-pencil-square class="h-5 w-5 text-plansul-orange" />
            </a>
            @if(Auth::check() && Auth::user()->isAdmin())
            <button
                type="button"
                class="delete-btn-local p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-red-600"
                data-local-id="{{ $local['id'] }}"
                data-local-name="{{ $local['delocal'] }}"
                title="Remover local">
                <x-heroicon-o-trash class="h-5 w-5" />
            </button>
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="7" class="px-4 py-3 text-center text-sm">Nenhum local encontrado.</td>
</tr>
@endforelse
