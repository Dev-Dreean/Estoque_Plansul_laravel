{{-- Este código deve estar no arquivo resources/views/projetos/_form.blade.php --}}

@if ($errors->any())
<div class="mb-4 text-sm text-red-600 dark:text-red-400">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="cdlocal" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Código do Local</label>
        <input id="cdlocal" name="cdlocal" type="number" @if(session('duplicating_mode')) readonly class="opacity-70 cursor-not-allowed mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 rounded-md shadow-sm" @else class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" @endif
            value="{{ old('cdlocal', $local->cdlocal ?? '') }}" required>
        @if(session('duplicating_mode'))
        <p class="text-xs text-gray-500 mt-1">Código bloqueado durante duplicação.</p>
        @endif
    </div>

    <div>
        <label for="delocal" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nome do Local</label>
        <input id="delocal" name="delocal" type="text" @if(session('duplicating_mode')) readonly class="opacity-70 cursor-not-allowed mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 rounded-md shadow-sm" @else class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" @endif
            value="{{ old('delocal', $local->delocal ?? '') }}" required>
        @if(session('duplicating_mode'))
        <p class="text-xs text-gray-500 mt-1">Nome bloqueado durante duplicação.</p>
        @endif
    </div>

    <div class="md:col-span-2">
        <label for="tabfant_id" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Projeto Associado</label>
        <select id="tabfant_id" name="tabfant_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" required>
            <option value="">Selecione um projeto</option>
            {{-- O controller envia a variável $projetos com a lista --}}
            @foreach ($projetos as $projeto_opcao)
            <option value="{{ $projeto_opcao->id }}"
                {{-- Marca o projeto atual como selecionado (útil para o form de edição) --}}
                @if(old('tabfant_id', $local->tabfant_id ?? '') == $projeto_opcao->id) selected @endif
                >
                {{ $projeto_opcao->NOMEPROJETO }} (Cód: {{ $projeto_opcao->CDPROJETO }})
            </option>
            @endforeach
        </select>
        @if(session('duplicating_mode'))
        <p class="text-xs text-blue-500 mt-1">Selecione agora o novo Projeto para associar este local duplicado.</p>
        @endif
    </div>
</div>