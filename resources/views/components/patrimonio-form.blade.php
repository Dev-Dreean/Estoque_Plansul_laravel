@props(['patrimonio' => null])
<div x-data='patrimonioForm({ patrimonio: @json($patrimonio) })' @keydown.enter.prevent="focusNext($event.target)">

    {{-- Primeira Linha --}}
    <div class="grid grid-cols-3 gap-6">
        <div>
            <x-input-label for="NUPATRIMONIO" value="Nº Patrimônio *" />
            <div class="relative mt-1">
                <x-text-input
                    x-model="formData.NUPATRIMONIO"
                    @blur="buscarPatrimonio"
                    data-index="1"
                    id="NUPATRIMONIO"
                    name="NUPATRIMONIO"
                    type="number"
                    class="block w-full pr-10" {{-- Padding para a lupa --}}
                    required />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <button @click="openSearchModal" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
            <span x-show="loading" class="text-sm text-gray-500">Buscando...</span>
        </div>
        <div>
            <x-input-label for="NUMOF" value="Nº OC" />
            <x-text-input data-index="2" x-model="formData.NUMOF" id="NUMOF" name="NUMOF" type="number" class="mt-1 block w-full" />
        </div>
        <div>
            <x-input-label for="campo_extra" value="-" />
            <x-text-input data-index="3" id="campo_extra" name="campo_extra" type="text" class="mt-1 block w-full" disabled />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
        <div class="md:col-span-1">
            <x-input-label for="CODOBJETO" value="Código *" />
            {{-- ADIÇÃO: @blur para a nova funcionalidade --}}
            <x-text-input data-index="4" @blur="buscarDescricaoCodigo()" @change="buscarDescricaoCodigo()" x-model="formData.CODOBJETO" id="CODOBJETO" name="CODOBJETO" type="number" class="mt-1 block w-full" required />
        </div>
        <div class="md:col-span-3">
            <x-input-label for="DEPATRIMONIO" value="Descrição do Código" />
            {{-- ADIÇÃO: readonly e estilo para campo automático --}}
            <x-text-input data-index="5" x-model="formData.DEPATRIMONIO" id="DEPATRIMONIO" name="DEPATRIMONIO" type="text" class="mt-1 block w-full bg-gray-100 dark:bg-gray-900" readonly />
        </div>
    </div>

    <div class="mt-4">
        <x-input-label for="DEHISTORICO" value="Observação" />
        <textarea data-index="6" x-model="formData.DEHISTORICO" id="DEHISTORICO" name="DEHISTORICO" rows="2" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"></textarea>
    </div>

    {{-- Quarta Linha --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
        <div>
            <x-input-label for="CDPROJETO" value="Projeto" />
            <div class="flex items-center space-x-2">
                <x-text-input @blur="buscarProjetoELocais" x-model="formData.CDPROJETO" data-index="7" id="CDPROJETO" name="CDPROJETO" type="number" class="mt-1 block w-1/3" />
                <x-text-input x-model="nomeProjeto" type="text" class="mt-1 block w-2/3 bg-gray-100 dark:bg-gray-900" placeholder="Nome do Projeto" readonly />
            </div>
        </div>
        <div>
            <x-input-label for="CDLOCAL" value="Local" />
            <select data-index="9" x-model="formData.CDLOCAL" id="CDLOCAL" name="CDLOCAL" class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" :disabled="locais.length === 0">
                <option value="">Selecione um local...</option>
                <template x-for="local in locais" :key="local.id">
                    <option :value="local.id" x-text="local.LOCAL"></option>
                </template>
            </select>
        </div>
        <div>
            <x-input-label for="NMPLANTA" value="Cód Termo" />
            <x-text-input data-index="9" x-model="formData.NMPLANTA" id="NMPLANTA" name="NMPLANTA" type="number" class="mt-1 block w-full" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <div>
            <x-input-label for="MARCA" value="Marca" />
            <x-text-input data-index="11" x-model="formData.MARCA" id="MARCA" name="MARCA" type="text" class="mt-1 block w-full" />
        </div>
        <div>
            <x-input-label for="MODELO" value="Modelo" />
            <x-text-input data-index="12" x-model="formData.MODELO" id="MODELO" name="MODELO" type="text" class="mt-1 block w-full" />
        </div>
        <div>
            <x-input-label for="SITUACAO" value="Situação *" />
            <select data-index="13" x-model="formData.SITUACAO" id="SITUACAO" name="SITUACAO" class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" required>
                <option value="EM USO">EM USO</option>
                <option value="CONSERTO">CONSERTO</option>
                <option value="BAIXA">BAIXA</option>
                <option value="À DISPOSIÇÃO">À DISPOSIÇÃO</option>
            </select>
        </div>
    </div>

    {{-- Datas (última linha do formulário) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
        <div>
            <x-input-label for="DTAQUISICAO" value="Data de Aquisição" />
            <x-text-input data-index="14" x-model="formData.DTAQUISICAO" id="DTAQUISICAO" name="DTAQUISICAO" type="date" class="mt-1 block w-full" />
        </div>
        <div>
            <x-input-label for="DTBAIXA" value="Data de Baixa" />
            <x-text-input data-index="15" x-model="formData.DTBAIXA" id="DTBAIXA" name="DTBAIXA" type="date" class="mt-1 block w-full" />
        </div>
    </div>

    <div x-show="searchModalOpen" @keydown.window.escape="closeSearchModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
        <div @click.away="closeSearchModal" class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Pesquisar Patrimônio</h3>
            <input x-model="searchTerm" @input.debounce.300ms="search" type="text" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" placeholder="Digite o nº ou descrição para buscar...">
            <ul class="mt-4 max-h-60 overflow-y-auto">
                <template x-for="item in searchResults" :key="item.NUSEQPATR">
                    <li @click="selectPatrimonio(item)" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer border-b dark:border-gray-600">
                        <span class="font-bold" x-text="item.NUPATRIMONIO"></span> - <span x-text="item.DEPATRIMONIO"></span>
                    </li>
                </template>
                <template x-if="!loadingSearch && searchResults.length === 0 && searchTerm !== ''">
                    <li class="p-2 text-gray-500">Nenhum resultado encontrado.</li>
                </template>
                <template x-if="loadingSearch">
                    <li class="p-2 text-gray-500">Buscando...</li>
                </template>
            </ul>
            <div class="mt-4 text-right">
                <button @click="closeSearchModal" type="button" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-100">Fechar</button>
            </div>
        </div>
    </div>
</div>
<script>
    function patrimonioForm(config) {
        return {
            // == DADOS DO FORMULÁRIO ==
            formData: {
                NUPATRIMONIO: config.patrimonio?.NUPATRIMONIO || '',
                NUMOF: config.patrimonio?.NUMOF || '',
                CODOBJETO: config.patrimonio?.CODOBJETO || '',
                DEPATRIMONIO: config.patrimonio?.DEPATRIMONIO || '',
                DEHISTORICO: config.patrimonio?.DEHISTORICO || '',
                CDPROJETO: config.patrimonio?.CDPROJETO || '',
                CDLOCAL: config.patrimonio?.CDLOCAL || '',
                NMPLANTA: config.patrimonio?.NMPLANTA || '', // Adicionado para edição
                MARCA: config.patrimonio?.MARCA || '',
                MODELO: config.patrimonio?.MODELO || '',
                SITUACAO: config.patrimonio?.SITUACAO || 'EM USO',
                DTAQUISICAO: config.patrimonio?.DTAQUISICAO ? config.patrimonio.DTAQUISICAO.split(' ')[0] : '',
                DTBAIXA: config.patrimonio?.DTBAIXA ? config.patrimonio.DTBAIXA.split(' ')[0] : '',
            },
            // == ESTADO DA UI ==
            loading: false,
            searchModalOpen: false,
            searchTerm: '',
            searchResults: [],
            loadingSearch: false,
            nomeProjeto: '',
            locais: [],

            // == FUNÇÕES ==

            openSearchModal() {
                this.searchModalOpen = true;
                this.search();
            },
            closeSearchModal() {
                this.searchModalOpen = false;
                this.searchTerm = '';
                this.searchResults = [];
            },

            async search() {
                this.loadingSearch = true;
                try {
                    const response = await fetch(`/api/patrimonios/pesquisar?q=${this.searchTerm}`);
                    if (response.ok) this.searchResults = await response.json();
                } catch (error) {
                    console.error('Erro na pesquisa:', error);
                } finally {
                    this.loadingSearch = false;
                }
            },

            selectPatrimonio(item) {
                this.formData.NUPATRIMONIO = item.NUPATRIMONIO;
                this.buscarPatrimonio();
                this.closeSearchModal();
            },

            async buscarPatrimonio() {
                if (!this.formData.NUPATRIMONIO) return;
                this.loading = true;
                try {
                    const response = await fetch(`/api/patrimonios/buscar/${this.formData.NUPATRIMONIO}`);
                    if (response.ok) {
                        const data = await response.json();
                        const originalCdLocal = this.formData.CDLOCAL; // Guarda o valor original

                        Object.keys(this.formData).forEach(key => {
                            if (data.hasOwnProperty(key) && data[key] !== null) {
                                if (key.startsWith('DT')) this.formData[key] = data[key].split(' ')[0];
                                else this.formData[key] = data[key];
                            }
                        });

                        if (this.formData.CDPROJETO) {
                            await this.buscarProjetoELocais(); // Espera a busca terminar
                            this.formData.CDLOCAL = data.CDLOCAL; // Restaura o valor
                        }
                    } else {
                        const numPatrimonio = this.formData.NUPATRIMONIO;
                        Object.keys(this.formData).forEach(key => {
                            if (key !== 'NUPATRIMONIO') this.formData[key] = ''
                        });
                        this.formData.NUPATRIMONIO = numPatrimonio;
                    }
                } catch (error) {
                    console.error('Erro ao buscar patrimônio:', error);
                } finally {
                    this.loading = false;
                }
            },

            async buscarDescricaoCodigo() {
                this.formData.DEPATRIMONIO = 'Buscando...';
                if (!this.formData.CODOBJETO) {
                    this.formData.DEPATRIMONIO = '';
                    return;
                }
                try {
                    const response = await fetch(`/api/codigos/buscar/${this.formData.CODOBJETO}`);
                    if (response.ok) {
                        const data = await response.json();
                        this.formData.DEPATRIMONIO = data?.descricao ?? '';
                    } else {
                        this.formData.DEPATRIMONIO = 'Código não encontrado.';
                    }
                } catch (error) {
                    this.formData.DEPATRIMONIO = 'Erro na busca.';
                }
            },

            async buscarProjetoELocais() {
                this.nomeProjeto = 'Buscando...';
                this.locais = [];
                // NÃO limpa o CDLOCAL aqui para permitir a restauração no modo de edição
                if (!this.formData.CDPROJETO) {
                    this.nomeProjeto = '';
                    return;
                }

                try {
                    const projResponse = await fetch(`/api/projetos/buscar/${this.formData.CDPROJETO}`);
                    this.nomeProjeto = projResponse.ok ? (await projResponse.json()).NOMEPROJETO : 'Projeto não encontrado';
                } catch (error) {
                    this.nomeProjeto = 'Erro na busca';
                }

                try {
                    const locaisResponse = await fetch(`/api/locais/${this.formData.CDPROJETO}`);
                    if (locaisResponse.ok) this.locais = await locaisResponse.json();
                } catch (error) {
                    console.error('Erro ao buscar locais:', error);
                }
            },

            focusNext(currentElement) {
                const focusable = Array.from(currentElement.closest('form').querySelectorAll('input:not([readonly]):not([disabled]), select:not([readonly]):not([disabled]), textarea:not([readonly]):not([disabled])'));
                const currentIndex = focusable.indexOf(currentElement);
                const nextElement = focusable[currentIndex + 1];
                if (nextElement) {
                    nextElement.focus();
                } else {
                    currentElement.closest('form').querySelector('button[type="submit"]')?.focus();
                }
            },

            // Lógica de inicialização CORRIGIDA
            async init() {
                if (config.patrimonio && config.patrimonio.CDPROJETO) {
                    // Guarda o valor original do local que queremos selecionar
                    const targetCdLocal = config.patrimonio.CDLOCAL;

                    // Busca o nome do projeto e a lista de locais
                    await this.buscarProjetoELocais();

                    // Apenas DEPOIS que a busca terminar, nós definimos o valor do select
                    this.formData.CDLOCAL = targetCdLocal;
                }
            }
        }
    }
</script>