@forelse($colaboradores as $colaborador)
    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
        <td class="px-4 py-2 font-mono text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap">
            {{ $colaborador->CDMATRFUNCIONARIO }}
        </td>
        <td class="px-4 py-2 font-medium text-sm text-gray-900 dark:text-gray-100">
            {{ $colaborador->NMFUNCIONARIO }}
        </td>
        <td class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 hidden sm:table-cell">
            {{ $colaborador->CDCARGO ?: '—' }}
        </td>
        <td class="px-4 py-2">
            @if($colaborador->NUSEQUSUARIO)
                <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                    ✔ Com acesso
                </span>
            @else
                <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                    Sem acesso
                </span>
            @endif
        </td>
        <td class="px-4 py-2 text-xs font-mono text-gray-500 dark:text-gray-400">
            {{ $colaborador->NMLOGIN ?? '—' }}
        </td>
        <td class="px-4 py-2 hidden md:table-cell">
            @if($colaborador->synced_at)
                <span class="inline-flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                    {{ \Carbon\Carbon::parse($colaborador->synced_at)->format('d/m/Y') }}
                </span>
            @else
                <span class="text-xs text-gray-400 dark:text-gray-500 italic">Manual</span>
            @endif
        </td>
        <td class="px-4 py-2">
            <div class="flex items-center gap-1">
                @if(!$colaborador->NUSEQUSUARIO)
                    <button type="button"
                        onclick="window.gestaoInstance && window.gestaoInstance.abrirModalPermissoes('{{ $colaborador->CDMATRFUNCIONARIO }}', '{{ addslashes($colaborador->NMFUNCIONARIO) }}', false, null, '')"
                        title="Criar login para {{ $colaborador->NMFUNCIONARIO }}"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-800/40 border border-indigo-200 dark:border-indigo-700 transition">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Criar login
                    </button>
                @else
                    <button type="button"
                        onclick="window.gestaoInstance && window.gestaoInstance.abrirModalPermissoes('{{ $colaborador->CDMATRFUNCIONARIO }}', '{{ addslashes($colaborador->NMFUNCIONARIO) }}', true, {{ $colaborador->NUSEQUSUARIO }}, '{{ $colaborador->NMLOGIN }}')"
                        title="Editar permissões de {{ $colaborador->NMFUNCIONARIO }}"
                        class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-orange-500 dark:text-orange-400 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                    </button>
                    @if(Auth::id() !== $colaborador->NUSEQUSUARIO)
                        <button type="button"
                            onclick="window.gestaoInstance && window.gestaoInstance.abrirModalRemocao({{ $colaborador->NUSEQUSUARIO }}, '{{ addslashes($colaborador->NOMEUSER ?? $colaborador->NMFUNCIONARIO) }}')"
                            title="Remover acesso de {{ $colaborador->NOMEUSER ?? $colaborador->NMFUNCIONARIO }}"
                            class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-red-600 dark:text-red-400 transition">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/></svg>
                        </button>
                    @endif
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
            Nenhum colaborador encontrado.
        </td>
    </tr>
@endforelse

