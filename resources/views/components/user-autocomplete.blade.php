{{-- 
  Componente de Autocomplete para Usuários/Funcionários
  Permite buscar por matrícula ou nome com dropdown em tempo real
  
  Props:
  - id: ID único do input (required)
  - name: Nome do input para submissão (required)
  - placeholder: Texto de placeholder (default: "Pesquisar...")
  - value: Valor inicial (default: "")
  - apiEndpoint: URL do endpoint de API (default: "/api/funcionarios/pesquisar")
  - displayFormat: Formato de exibição no dropdown (default: "matrícula - nome")
--}}

@props([
    'id' => null,
    'name' => null,
    'placeholder' => 'Pesquisar...',
    'value' => '',
    'apiEndpoint' => '/api/funcionarios/pesquisar',
    'displayFormat' => 'codigo_nome'
])

<div x-data="userAutocomplete({
    inputId: '{{ $id }}',
    inputName: '{{ $name }}',
    apiEndpoint: '{{ $apiEndpoint }}',
    initialValue: '{{ $value }}'
})" class="relative">
    
    <!-- Input de busca -->
    <input 
        type="text" 
        id="{{ $id }}-search" 
        x-ref="searchInput"
        x-model="searchTerm"
        @input="onSearch($event)"
        @focus="showDropdown = true"
        @blur="setTimeout(() => showDropdown = false, 200)"
        @keydown.arrow-down="highlightedIndex = Math.min(highlightedIndex + 1, results.length - 1)"
        @keydown.arrow-up="highlightedIndex = Math.max(highlightedIndex - 1, 0)"
        @keydown.enter="selectResult(highlightedIndex)"
        placeholder="{{ $placeholder }}"
        value="{{ $value }}"
        {{ $attributes->merge([
            'class' => 'h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md'
        ]) }}
        autocomplete="off"
    />
    
    <!-- Input hidden para armazenar o valor selecionado (matrícula) -->
    <input 
        type="hidden" 
        id="{{ $id }}" 
        name="{{ $name }}"
        x-model="selectedValue"
        value="{{ $value }}"
        @change="$dispatch('autocomplete-changed')"
    />
    
    <!-- Dropdown de resultados -->
    <div 
        x-show="showDropdown && results.length > 0" 
        x-transition
        class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-50 max-h-64 overflow-y-auto"
        @click.stop
    >
        <template x-for="(result, index) in results" :key="index">
            <div 
                @click="selectResult(index)"
                :class="{
                    'bg-indigo-500 text-white': highlightedIndex === index,
                    'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700': highlightedIndex !== index
                }"
                class="px-3 py-2 cursor-pointer text-sm"
            >
                <span x-text="formatResult(result)"></span>
            </div>
        </template>
    </div>
    
    <!-- Mensagem de carregamento -->
    <div 
        x-show="isLoading" 
        x-transition
        class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-50 px-3 py-2"
    >
        <span class="text-xs text-gray-500 dark:text-gray-400">Carregando...</span>
    </div>
    
    <!-- Mensagem de nenhum resultado -->
    <div 
        x-show="showDropdown && !isLoading && results.length === 0 && searchTerm.length > 0" 
        x-transition
        class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-50 px-3 py-2"
    >
        <span class="text-xs text-gray-500 dark:text-gray-400">Nenhum resultado encontrado</span>
    </div>
</div>

<script>
function userAutocomplete(config) {
    return {
        searchTerm: '',
        results: [],
        selectedValue: config.initialValue || '',
        showDropdown: false,
        highlightedIndex: -1,
        isLoading: false,
        debounceTimer: null,
        apiEndpoint: config.apiEndpoint,
        
        onSearch(event) {
            // Debounce a busca
            clearTimeout(this.debounceTimer);
            this.highlightedIndex = -1;
            
            if (this.searchTerm.length === 0) {
                this.results = [];
                this.showDropdown = false;
                return;
            }
            
            if (this.searchTerm.length < 1) {
                this.results = [];
                return;
            }
            
            this.isLoading = true;
            this.showDropdown = true;
            
            this.debounceTimer = setTimeout(async () => {
                try {
                    const response = await fetch(
                        `${this.apiEndpoint}?q=${encodeURIComponent(this.searchTerm)}`
                    );
                    
                    if (!response.ok) {
                        throw new Error('Erro na busca');
                    }
                    
                    this.results = await response.json();
                } catch (error) {
                    console.error('Erro ao buscar funcionários:', error);
                    this.results = [];
                } finally {
                    this.isLoading = false;
                }
            }, 300); // Espera 300ms após o usuário parar de digitar
        },
        
        selectResult(index) {
            if (index >= 0 && index < this.results.length) {
                const result = this.results[index];
                // Armazena a matrícula no input hidden
                this.selectedValue = result.CDMATRFUNCIONARIO;
                // Mostra a matrícula e nome no campo visível
                this.searchTerm = `${result.CDMATRFUNCIONARIO} - ${result.NMFUNCIONARIO}`;
                this.results = [];
                this.showDropdown = false;
                this.highlightedIndex = -1;
            }
        },
        
        formatResult(result) {
            return `${result.CDMATRFUNCIONARIO} - ${result.NMFUNCIONARIO}`;
        }
    };
}
</script>
