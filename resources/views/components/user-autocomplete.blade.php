{{-- 
  Componente de Autocomplete para Usuários/Funcionários
  Permite buscar por matrícula ou nome com dropdown em tempo real
  
  Props:
  - id: ID único do input (required)
  - name: Nome do input para submissão (required)
  - placeholder: Texto de placeholder
  - value: Valor inicial
  - apiEndpoint: URL do endpoint de API
--}}

@props([
    'id' => null,
    'name' => null,
    'placeholder' => 'Pesquisar matrícula ou nome...',
    'value' => '',
    'initialDisplay' => '',
    'lookupOnInit' => false,
    'apiEndpoint' => '/api/funcionarios/pesquisar',
])

<div x-data="userAutocomplete({
    inputId: '{{ $id }}',
    inputName: '{{ $name }}',
    apiEndpoint: '{{ $apiEndpoint }}',
    initialValue: '{{ $value }}',
    initialDisplay: @js($initialDisplay),
    lookupOnInit: @js($lookupOnInit)
})" x-init="init(); const display = @js($initialDisplay); const fallback = @js($value); searchTerm = display || fallback || '';" class="relative w-full">
    
    <!-- Input de busca -->
    <div class="relative">
        <input 
            type="text" 
            id="{{ $id }}-search" 
            x-ref="searchInput"
            x-model="searchTerm"
            @input="onSearch()"
            @focus="if (!selectedValue) { showDropdown = true; if(!searchTerm) filtrarResultados() }"
            @blur="setTimeout(() => showDropdown = false, 250)"
            @keydown.arrow-down.prevent="highlightedIndex = Math.min(highlightedIndex + 1, results.length - 1)"
            @keydown.arrow-up.prevent="highlightedIndex = Math.max(highlightedIndex - 1, 0)"
            @keydown.enter.prevent="selectResult(highlightedIndex)"
            @keydown.escape="showDropdown = false"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            spellcheck="false"
            {{ $attributes->merge([
                'class' => 'w-full h-9 px-3 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition'
            ]) }}
        />
        
        <!-- Ícone de busca ou loading -->
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <template x-if="isLoading">
                <svg class="w-4 h-4 text-gray-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </template>
            <template x-if="!isLoading && selectedValue">
                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </template>
            <template x-if="!isLoading && !selectedValue && searchTerm">
                <button type="button" @click="limpar(); $refs.searchInput.focus()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </template>
        </div>
    </div>
    
    <!-- Input hidden para armazenar o valor selecionado (matrícula) -->
    <input 
        type="hidden" 
        id="{{ $id }}" 
        name="{{ $name }}"
        x-model="selectedValue"
        value="{{ $value }}"
    />
    
    <!-- Dropdown de resultados -->
    <div 
        x-show="showDropdown" 
        x-transition
        @click.stop
        class="absolute z-50 top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg overflow-hidden"
    >
        <!-- Carregando -->
        <template x-if="isLoading && searchTerm.length > 0">
            <div class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400 flex items-center justify-center gap-2">
                <svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Buscando...</span>
            </div>
        </template>

        <!-- Sem resultados -->
        <template x-if="!isLoading && results.length === 0 && searchTerm.length > 0">
            <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                Nenhum funcionário encontrado com "<span x-text="searchTerm"></span>"
            </div>
        </template>

        <!-- Resultados -->
        <div class="max-h-60 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
            <template x-for="(result, index) in results" :key="index">
                <button 
                    type="button"
                    @click="selectResult(index)"
                    @mouseenter="highlightedIndex = index"
                    :class="{
                        'bg-indigo-50 dark:bg-indigo-900/30': highlightedIndex === index,
                        'hover:bg-gray-50 dark:hover:bg-gray-700': highlightedIndex !== index
                    }"
                    class="w-full px-4 py-2.5 text-left text-sm transition text-gray-700 dark:text-gray-200"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 dark:text-gray-100" x-text="result.CDMATRFUNCIONARIO"></div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 truncate" x-text="result.NMFUNCIONARIO"></div>
                        </div>
                        <div x-show="highlightedIndex === index" class="flex-shrink-0 text-indigo-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </button>
            </template>
        </div>
    </div>
</div>
