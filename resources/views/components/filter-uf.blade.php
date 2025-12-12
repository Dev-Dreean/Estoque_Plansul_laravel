{{--
    Componente: Filtro de UF (Estado) para Patrimonios
    
    Uso:
    <x-filter-uf :selected="$filters['uf'] ?? []" />
    
    Props:
    - selected: array de UFs selecionadas (ex: ['RS', 'SP'])
    - multiple: bool (default: true) - permite múltiplas seleções
    - name: string (default: 'uf_filter') - nome do input
--}}

@props([
    'selected' => [],
    'multiple' => true,
    'name' => 'uf_filter',
    'label' => 'Estado (UF)',
])

<div class="mb-4">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        {{ $label }}
    </label>
    
    <select 
        id="{{ $name }}"
        name="{{ $name }}{{ $multiple ? '[]' : '' }}"
        {{ $multiple ? 'multiple' : '' }}
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md
                 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent
                 hover:border-gray-400 dark:hover:border-gray-500 transition"
        {{ $attributes }}
    >
        <option value="">{{ $multiple ? 'Todos os estados' : 'Selecione...' }}</option>
        
        {{-- Lista de UFs brasileiras --}}
        @foreach (['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'] as $uf)
            <option 
                value="{{ $uf }}"
                @if(is_array($selected) && in_array($uf, $selected)) selected @endif
            >
                {{ $uf }}
            </option>
        @endforeach
    </select>
    
    {{-- Classe TailwindCSS para múltiplas seleções --}}
    @if($multiple)
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            💡 Dica: Use Ctrl+Click para selecionar múltiplos estados
        </p>
    @endif
</div>
