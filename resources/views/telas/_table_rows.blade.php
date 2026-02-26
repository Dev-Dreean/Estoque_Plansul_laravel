@forelse($telasGrid as $tela)
<tr class="border-b dark:border-gray-700 bg-white dark:bg-gray-800">
    <td class="px-4 py-2 font-mono text-gray-900 dark:text-gray-100">{{ $tela['NUSEQTELA'] ?? '-' }}</td>
    <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $tela['DETELA'] ?? '-' }}</td>
    <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $tela['NMSISTEMA'] ?? '-' }}</td>
    <td class="px-4 py-2">
        @if(!empty($tela['rota']))
            <span class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $tela['rota'] }}</span>
        @else
            <span class="text-gray-400">-</span>
        @endif
    </td>
    <td class="px-4 py-2">
        @if(!empty($tela['cadastrada']))
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200">Cadastrada</span>
        @else
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200">Não vinculada</span>
        @endif
    </td>
    <td class="px-4 py-2">
        <div class="flex items-center gap-2">
            @if(empty($tela['cadastrada']))
            <form method="POST" action="{{ route('cadastro-tela.showForm', ['nome' => $tela['DETELA']]) }}">
                @csrf
                <button type="submit" class="px-2 py-1 text-xs rounded bg-indigo-100 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-200 dark:hover:bg-indigo-900/60">
                    Preencher
                </button>
            </form>

            <form method="POST" action="{{ route('cadastro-tela.gerarVincular', ['nome' => $tela['DETELA']]) }}" onsubmit="return confirm('Vincular esta tela ao cadastro?');">
                @csrf
                <button type="submit" class="px-2 py-1 text-xs rounded bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/40 dark:text-green-200 dark:hover:bg-green-900/60">
                    Vincular
                </button>
            </form>
            @else
            <span class="text-xs text-gray-500 dark:text-gray-400">Vinculada</span>
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="6" class="px-4 py-3 text-center text-sm">Nenhuma tela encontrada.</td>
</tr>
@endforelse
