{{-- 
  Componente de Multiselect para Funcionários com Filtro Dinâmico
  Permite selecionar múltiplos funcionários e valida a lista em tempo real
  
  @props
  - id: ID único do componente
  - name: Nome do campo hidden para envio do form
  - placeholder: Texto do placeholder
  - value: Valores iniciais (separados por vírgula ou JSON)
--}}

@props([
    'id' => 'employee_multi',
    'name' => 'employees',
    'placeholder' => 'Pesquisar funcionário...',
    'value' => ''
])

<div x-data="employeeMultiselect()" class="relative w-full" @click.outside="if(!$refs.dropdown || !$refs.dropdown.contains($event.target)) dropdownOpen = false">
    <!-- Input de busca -->
    <div class="relative">
        <input 
            type="text" 
            x-ref="searchInput"
            @input="search($event)"
            @focus="$nextTick(() => positionDropdown())"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md"
        />
    </div>
    
    <!-- Hidden input para envio do formulário -->
    <input 
        type="hidden"
        x-ref="hiddenInput"
        id="{{ $id }}"
        name="{{ $name }}"
        :value="selectedEmployees.map(e => e.CDMATRFUNCIONARIO).join(',')"
    />
    
    <!-- Tags de funcionários selecionados -->
    <div class="mt-2 flex flex-wrap gap-2" x-show="selectedEmployees.length > 0">
        <template x-for="emp in selectedEmployees" :key="emp.CDMATRFUNCIONARIO">
            <div class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs">
                <span x-text="`${emp.CDMATRFUNCIONARIO} - ${emp.NMFUNCIONARIO.substring(0, 15)}${emp.NMFUNCIONARIO.length > 15 ? '...' : ''}`"></span>
                <button
                    type="button"
                    @click="removeEmployee(emp.CDMATRFUNCIONARIO); validateSelection()"
                    class="ml-1 hover:text-blue-900 dark:hover:text-blue-100 font-bold"
                    title="Remover"
                >
                    ✕
                </button>
            </div>
        </template>
    </div>

    <!-- Mensagem de loading -->
    <div x-show="loading" class="mt-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Buscando...</span>
    </div>

    <!-- Mensagem de vazio -->
    <div x-show="!loading && dropdownOpen && results.length === 0 && searchTerm.length > 0" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Nenhum resultado encontrado
    </div>
</div>

<!-- PORTAL: Dropdown renderizado no body (fora de qualquer overflow constraint) -->
<template x-if="dropdownOpen && results.length > 0">
    <div 
        x-ref="dropdown"
        x-transition
        class="fixed bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-2xl max-h-64 overflow-y-auto"
        style="z-index: 50000; top: 0; left: 0;"
        @click.stop
    >
        <template x-for="emp in results" :key="emp.CDMATRFUNCIONARIO">
            <div
                @click="selectEmployee(emp); validateSelection()"
                class="px-3 py-2 cursor-pointer text-sm hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700"
                :class="{ 'bg-blue-50 dark:bg-blue-900': isSelected(emp.CDMATRFUNCIONARIO) }"
            >
                <div class="font-semibold text-blue-600 dark:text-blue-400" x-text="emp.CDMATRFUNCIONARIO"></div>
                <div class="text-gray-600 dark:text-gray-400" x-text="emp.NMFUNCIONARIO"></div>
            </div>
        </template>
    </div>
</template>

<script>
function employeeMultiselect() {
    return {
        selectedEmployees: [],
        results: [],
        dropdownOpen: false,
        loading: false,
        searchTerm: '',
        debounceTimer: null,

        async search(event) {
            this.searchTerm = event.target.value.trim();
            clearTimeout(this.debounceTimer);

            if (this.searchTerm.length === 0) {
                this.results = [];
                this.dropdownOpen = false;
                return;
            }

            this.loading = true;
            this.debounceTimer = setTimeout(async () => {
                try {
                    const response = await fetch(`/api/funcionarios/pesquisar?q=${encodeURIComponent(this.searchTerm)}`);
                    if (!response.ok) throw new Error('Erro na busca');
                    
                    let data = await response.json();
                    
                    // Filtrar funcionários já selecionados
                    this.results = Array.isArray(data) 
                        ? data.filter(emp => !this.selectedEmployees.some(s => s.CDMATRFUNCIONARIO === emp.CDMATRFUNCIONARIO))
                        : [];
                    
                    if (this.results.length > 0) {
                        this.dropdownOpen = true;
                        this.$nextTick(() => this.positionDropdown());
                    }
                } catch (error) {
                    console.error('Erro ao buscar funcionários:', error);
                    this.results = [];
                } finally {
                    this.loading = false;
                }
            }, 300);
        },

        selectEmployee(emp) {
            if (!this.isSelected(emp.CDMATRFUNCIONARIO)) {
                this.selectedEmployees.push(emp);
                this.$refs.searchInput.value = '';
                this.searchTerm = '';
                this.results = [];
                this.dropdownOpen = false;
            }
        },

        positionDropdown() {
            // Posicionar o dropdown fixo em relação ao input
            const input = this.$refs.searchInput;
            const dropdown = this.$refs.dropdown;
            
            if (input && dropdown) {
                const rect = input.getBoundingClientRect();
                
                // Posicionar abaixo do input com pequeno gap
                dropdown.style.top = (rect.bottom + window.scrollY + 4) + 'px';
                dropdown.style.left = (rect.left + window.scrollX) + 'px';
                dropdown.style.width = rect.width + 'px';
                
                // Observer para reposicionar quando scroll acontecer
                window.addEventListener('scroll', () => this.positionDropdown(), { once: true });
            }
        },

        removeEmployee(cdmatr) {
            this.selectedEmployees = this.selectedEmployees.filter(
                emp => emp.CDMATRFUNCIONARIO !== cdmatr
            );
            this.$refs.searchInput.focus();
        },

        isSelected(cdmatr) {
            return this.selectedEmployees.some(emp => emp.CDMATRFUNCIONARIO === cdmatr);
        },

        validateSelection() {
            // Validar e atualizar o hidden input
            const hiddenValue = this.selectedEmployees.map(e => e.CDMATRFUNCIONARIO).join(',');
            this.$refs.hiddenInput.value = hiddenValue;

            // Disparar evento para atualizar a página se necessário
            if (this.selectedEmployees.length === 0) {
                console.log('ℹ️ Nenhum funcionário selecionado');
            } else {
                console.log('✅ Funcionários selecionados:', hiddenValue);
            }
        },

        init() {
            // Carregar valores iniciais se fornecidos
            const initialValue = '{{ $value }}';
            if (initialValue) {
                const matrValues = initialValue.split(',').filter(v => v.trim());
                if (matrValues.length > 0) {
                    // Buscar dados dos funcionários já selecionados
                    this.loadInitialEmployees(matrValues);
                }
            }
        },

        async loadInitialEmployees(matrValues) {
            try {
                const promises = matrValues.map(matr =>
                    fetch(`/api/funcionarios/pesquisar?q=${encodeURIComponent(matr)}`)
                        .then(r => r.json())
                        .then(data => Array.isArray(data) ? data[0] : null)
                );
                
                const results = await Promise.all(promises);
                this.selectedEmployees = results.filter(r => r !== null);
                this.$refs.hiddenInput.value = this.selectedEmployees.map(e => e.CDMATRFUNCIONARIO).join(',');
            } catch (error) {
                console.error('Erro ao carregar funcionários iniciais:', error);
            }
        }
    };
}

// Inicializar quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // O Alpine.js já inicializa automaticamente com x-data
});
</script>
