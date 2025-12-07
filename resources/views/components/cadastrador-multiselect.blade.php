{{-- 
  Componente Multi-Select para Cadastradores
  Permite seleção múltipla de cadastradores que têm patrimônios registrados
  Busca por nome com dropdown inteligente
  As tags de selecionados NÃO aparecem aqui - são exibidas no badge "Ativo:" do filtro
  
  @props
  - id: identificador único (padrão: 'filtro_cadastradores')
  - name: nome do campo no formulário (padrão: 'cadastradores')
  - placeholder: texto do placeholder (padrão: 'Cadastrador')
  - selected: array de selecionados ou string separada por vírgula
--}}

@props([
    'id' => 'filtro_cadastradores',
    'name' => 'cadastradores',
    'placeholder' => 'Cadastrador',
    'selected' => []
])

@php
    // Garantir que selected seja sempre um array
    $selectedArray = is_array($selected) ? $selected : ($selected ? explode(',', $selected) : []);
@endphp

<div x-data="{
    open: false,
    searchTerm: '',
    loading: false,
    cadastradores: [],
    selectedItems: {{ json_encode($selectedArray) }},
    
    init() {
        @if(count($selectedArray) > 0)
            this.loadSelectedNames();
            // Emite evento inicial com seleções existentes
            this.$nextTick(() => this.emitChange());
        @endif
        
        // Watch para emitir evento sempre que selectedItems mudar
        this.$watch('selectedItems', () => this.emitChange());
        
        // Listener para quando um cadastrador é removido do badge
        window.addEventListener('cadastradores-removido', (e) => {
            const nome = e.detail.nome;
            this.selectedItems = this.selectedItems.filter(item => item !== nome);
        });
    },
    
    emitChange() {
        this.$dispatch('cadastradores-updated', { items: [...this.selectedItems] });
    },
    
    loadSelectedNames() {
        if (this.selectedItems.length === 0) return;
        fetch(`/api/cadastradores/nomes?ids=${this.selectedItems.join(',')}`)
            .then(r => r.json())
            .then(data => {
                this.cadastradores = data || [];
            });
    },
    
    searchCadastradores() {
        if (this.searchTerm.length === 0) {
            this.open = false;
            return;
        }
        
        this.loading = true;
        this.open = true;
        
        fetch(`/api/cadastradores/pesquisar?q=${encodeURIComponent(this.searchTerm)}`)
            .then(r => r.json())
            .then(data => {
                this.cadastradores = data || [];
                this.loading = false;
            })
            .catch(() => {
                this.loading = false;
                this.cadastradores = [];
            });
    },
    
    selectCadastrador(nome) {
        if (!this.selectedItems.includes(nome)) {
            this.selectedItems.push(nome);
        }
        this.searchTerm = '';
        this.open = false;
    },
    
    removeCadastrador(nome) {
        this.selectedItems = this.selectedItems.filter(item => item !== nome);
    }
}" 
class="relative w-full" 
@click.away="open = false">

    <!-- Input padrão seguindo o mesmo formato dos outros filtros -->
    <div class="relative">
        <input 
            type="text" 
            x-model="searchTerm"
            @input.debounce.300ms="searchCadastradores()"
            @focus="if(searchTerm.length > 0) open = true"
            :placeholder="selectedItems.length > 0 ? selectedItems.length + ' selecionado(s)' : '{{ $placeholder }}'"
            autocomplete="off"
            class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md"
        />
        
        <!-- Spinner de loading -->
        <div x-show="loading" class="absolute right-8 top-1/2 -translate-y-1/2">
            <svg class="animate-spin h-4 w-4 text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>
    
    <!-- Hidden inputs para enviar no form -->
    <template x-for="(nome, index) in selectedItems" :key="index">
        <input 
            type="hidden"
            :name="'{{ $name }}[' + index + ']'"
            :value="nome"
        />
    </template>
    
    <!-- Dropdown de resultados -->
    <div 
        x-show="open && !loading && cadastradores.length > 0"
        x-transition
        class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-[9999] max-h-64 overflow-y-auto"
    >
        <template x-for="cadastrador in cadastradores" :key="cadastrador.nome">
            <div 
                @click="selectCadastrador(cadastrador.nome)"
                class="px-3 py-2 cursor-pointer text-sm text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0"
                :class="{'bg-blue-50 dark:bg-blue-900': selectedItems.includes(cadastrador.nome)}"
            >
                <div class="flex items-center justify-between">
                    <span class="font-medium" x-text="cadastrador.nome"></span>
                    <span class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">(<span x-text="cadastrador.total"></span>)</span>
                        <span x-show="selectedItems.includes(cadastrador.nome)" class="text-green-500">✓</span>
                    </span>
                </div>
            </div>
        </template>
    </div>
    
    <!-- Mensagem quando não há resultados -->
    <div 
        x-show="open && !loading && searchTerm.length > 0 && cadastradores.length === 0"
        x-transition
        class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-[9999] px-3 py-2"
    >
        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum cadastrador encontrado</p>
    </div>
</div>
