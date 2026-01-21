/**
 * Alpine.js Data Functions - Reutilizáveis em toda aplicação
 */

// Autocomplete para usuários/funcionários
window.userAutocomplete = function(config) {
    return {
        searchTerm: '',
        results: [],
        selectedValue: config.initialValue || '',
        showDropdown: false,
        highlightedIndex: -1,
        isLoading: false,
        debounceTimer: null,
        apiEndpoint: config.apiEndpoint,
        
        init() {
            if (this.selectedValue) {
                this.searchTerm = '';
            }
        },
        
        onSearch() {
            clearTimeout(this.debounceTimer);
            this.highlightedIndex = -1;
            
            if (this.searchTerm.length === 0) {
                this.results = [];
                return;
            }
            
            this.isLoading = true;
            this.showDropdown = true;
            
            this.debounceTimer = setTimeout(() => this.filtrarResultados(), 300);
        },
        
        filtrarResultados() {
            if (this.searchTerm.length === 0) {
                this.isLoading = false;
                this.results = [];
                return;
            }
            
            fetch(`${this.apiEndpoint}?q=${encodeURIComponent(this.searchTerm)}`)
                .then(response => {
                    if (!response.ok) throw new Error('Erro na busca');
                    return response.json();
                })
                .then(data => {
                    this.results = data;
                    this.isLoading = false;
                })
                .catch(error => {
                    console.error('Erro ao buscar funcionários:', error);
                    this.results = [];
                    this.isLoading = false;
                });
        },
        
        selectResult(index) {
            if (index >= 0 && index < this.results.length) {
                const result = this.results[index];
                this.selectedValue = result.CDMATRFUNCIONARIO;
                // Mostrar matrícula + nome no input visível
                this.searchTerm = `${result.CDMATRFUNCIONARIO} - ${result.NMFUNCIONARIO}`;
                this.results = [];
                this.showDropdown = false;
                this.highlightedIndex = -1;
            }
        },
        
        limpar() {
            this.searchTerm = '';
            this.selectedValue = '';
            this.results = [];
            this.showDropdown = false;
            this.highlightedIndex = -1;
        }
    };
};
