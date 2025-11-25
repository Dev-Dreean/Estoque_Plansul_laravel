{{-- 
  Componente de Autocomplete para Funcionários com Busca por Matrícula ou Nome
--}}

@props([
    'id' => null,
    'name' => null,
    'placeholder' => 'Pesquisar...',
    'value' => ''
])

<div class="relative w-full">
    <input 
        type="text" 
        id="{{ $id }}-input"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md"
        data-autocomplete="true"
        data-autocomplete-id="{{ $id }}"
        data-autocomplete-name="{{ $name }}"
        value=""
    />
    
    <input 
        type="hidden"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
    />
    
    <div id="{{ $id }}-dropdown" class="hidden absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-[9999] max-h-64 overflow-y-auto">
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('[data-autocomplete="true"]');
    
    inputs.forEach(function(inputEl) {
        const autocompleteId = inputEl.dataset.autocompleteId;
        const autocompleteName = inputEl.dataset.autocompleteName;
        const hiddenInput = document.getElementById(autocompleteId);
        const dropdownEl = document.getElementById(autocompleteId + '-dropdown');
        let debounceTimer;
        
        inputEl.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            const searchTerm = this.value.trim();
            
            if (searchTerm.length === 0) {
                dropdownEl.classList.add('hidden');
                return;
            }
            
            debounceTimer = setTimeout(function() {
                fetch(`/api/funcionarios/pesquisar?q=${encodeURIComponent(searchTerm)}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Erro na busca');
                        return response.json();
                    })
                    .then(data => {
                        dropdownEl.innerHTML = '';
                        if (data && Array.isArray(data) && data.length > 0) {
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'px-3 py-2 cursor-pointer text-sm hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700';
                                div.textContent = item.CDMATRFUNCIONARIO + ' - ' + item.NMFUNCIONARIO;
                                div.addEventListener('click', function() {
                                    hiddenInput.value = item.CDMATRFUNCIONARIO;
                                    inputEl.value = item.CDMATRFUNCIONARIO + ' - ' + item.NMFUNCIONARIO;
                                    dropdownEl.classList.add('hidden');
                                });
                                dropdownEl.appendChild(div);
                            });
                            dropdownEl.classList.remove('hidden');
                        } else {
                            const div = document.createElement('div');
                            div.className = 'px-3 py-2 text-sm text-gray-500 dark:text-gray-400';
                            div.textContent = 'Nenhum resultado encontrado';
                            dropdownEl.appendChild(div);
                            dropdownEl.classList.remove('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        dropdownEl.innerHTML = '<div class="px-3 py-2 text-sm text-red-500">Erro ao buscar</div>';
                        dropdownEl.classList.remove('hidden');
                    });
            }, 300);
        });
        
        inputEl.addEventListener('focus', function() {
            if (this.value.trim().length > 0 && !dropdownEl.classList.contains('hidden')) {
                dropdownEl.classList.remove('hidden');
            }
        });
        
        inputEl.addEventListener('blur', function() {
            setTimeout(() => {
                dropdownEl.classList.add('hidden');
            }, 200);
        });
    });
});
</script>
