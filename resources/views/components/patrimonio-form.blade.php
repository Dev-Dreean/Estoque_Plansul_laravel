@props(['patrimonio' => null])
<div x-data='patrimonioForm({ patrimonio: @json($patrimonio), old: @json(old()) })' @keydown.enter.prevent="focusNext($event.target)" class="space-y-6">

  {{-- GRUPO 1: N° Patrimônio, N° OC, Campo Vazio --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
          class="block w-full pr-10"
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

  {{-- GRUPO 2: Código e Descrição --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <div class="md:col-span-1">
      <x-input-label for="CODOBJETO" value="Código *" />
      <x-text-input data-index="4" @blur="buscarDescricaoCodigo()" @change="buscarDescricaoCodigo()" x-model="formData.CODOBJETO" id="CODOBJETO" name="CODOBJETO" type="number" class="mt-1 block w-full" required />
    </div>
    <div class="md:col-span-3">
      <x-input-label for="DEPATRIMONIO" value="Descrição do Código" />
      <x-text-input data-index="5" x-model="formData.DEPATRIMONIO" id="DEPATRIMONIO" name="DEPATRIMONIO" type="text" class="mt-1 block w-full bg-gray-100 dark:bg-gray-900" readonly />
    </div>
  </div>

  {{-- GRUPO 3: Observação --}}
  <div>
    <x-input-label for="DEHISTORICO" value="Observação" />
    <textarea data-index="6" x-model="formData.DEHISTORICO" id="DEHISTORICO" name="DEHISTORICO" rows="2" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"></textarea>
  </div>

  {{-- GRUPO 4: Projeto, Local e Cód. Termo --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-2">
      <x-input-label for="CDPROJETO" value="Projeto" />
      <div class="flex items-center space-x-2">
        <x-text-input @blur="buscarProjetoELocais" x-model="formData.CDPROJETO" data-index="7" id="CDPROJETO" name="CDPROJETO" type="number" class="mt-1 block w-1/3" />
        <x-text-input x-model="nomeProjeto" type="text" class="mt-1 block w-2/3 bg-gray-100 dark:bg-gray-900" placeholder="Nome do Projeto" readonly />
      </div>
    </div>
    <div>
      <x-input-label for="NMPLANTA" value="Cód Termo" />
      <x-text-input data-index="8" x-model="formData.NMPLANTA" id="NMPLANTA" name="NMPLANTA" type="number" class="mt-1 block w-full" />
    </div>
    <div class="md:col-span-3">
      <x-input-label for="CDLOCAL" value="Local" />
      <div class="flex gap-2 items-start">
        <div class="flex-1">
          <select data-index="9" x-model="formData.CDLOCAL" id="CDLOCAL" name="CDLOCAL" class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" :disabled="locais.length === 0">
            <option value="">Selecione um local...</option>
            <template x-for="local in locais" :key="local.id">
              <option :value="local.id" x-text="local.LOCAL"></option>
            </template>
          </select>
        </div>
        <button type="button" @click="abrirNovoLocal()" class="mt-1 inline-flex items-center justify-center w-10 h-10 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" title="Cadastrar novo local" aria-label="Cadastrar novo local">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
        </button>
      </div>
      <!-- Mini modal / popover cadastro local -->
      <div x-show="novoLocalOpen" x-transition @keydown.escape.window="fecharNovoLocal" class="relative">
        <div class="absolute z-50 mt-2 w-80 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-xl p-4">
          <div class="flex justify-between items-center mb-2">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Novo Local</h4>
            <button type="button" class="text-gray-400 hover:text-gray-600" @click="fecharNovoLocal">✕</button>
          </div>
          <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Nome do Local *</label>
          <input type="text" x-model="novoLocalNome" @keydown.enter.prevent="salvarNovoLocal" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-sm" placeholder="Ex: Almoxarifado" />
          <p class="text-xs text-red-500 mt-1" x-text="novoLocalErro"></p>
          <div class="mt-3 flex justify-end gap-2">
            <button type="button" @click="fecharNovoLocal" class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Cancelar</button>
            <button type="button" @click="salvarNovoLocal" class="px-3 py-1 text-xs rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50" :disabled="salvandoNovoLocal">
              <span x-show="!salvandoNovoLocal">Salvar</span>
              <span x-show="salvandoNovoLocal">Salvando...</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- GRUPO 5: Marca, Modelo, Situação, Matrícula --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pt-6 border-t border-gray-200 dark:border-gray-700">
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
      <x-input-error class="mt-2" :messages="$errors->get('SITUACAO')" />
    </div>
    <div>
      <x-input-label for="CDMATRFUNCIONARIO" value="Matrícula" />
      <x-text-input id="CDMATRFUNCIONARIO" name="CDMATRFUNCIONARIO" type="text" class="mt-1 block w-full bg-gray-100 dark:bg-gray-900" value="{{ auth()->user()->CDMATRFUNCIONARIO ?? '' }}" readonly />
    </div>
  </div>

  {{-- GRUPO 6: Datas --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div>
      <x-input-label for="DTAQUISICAO" value="Data de Aquisição" />
      <x-text-input data-index="14" x-model="formData.DTAQUISICAO" id="DTAQUISICAO" name="DTAQUISICAO" type="date" class="mt-1 block w-full" />
    </div>
    <div>
      <x-input-label for="DTBAIXA" value="Data de Baixa" />
      <x-text-input data-index="15" x-model="formData.DTBAIXA" id="DTBAIXA" name="DTBAIXA" type="date" class="mt-1 block w-full" />
      <x-input-error class="mt-2" :messages="$errors->get('DTBAIXA')" />
    </div>
  </div>

  {{-- MODAL DE PESQUISA (Não mexe na estrutura, fica no final) --}}
  <div x-show="searchModalOpen" x-cloak @keydown.window.escape="closeSearchModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
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
        NUPATRIMONIO: (config.old?.NUPATRIMONIO ?? config.patrimonio?.NUPATRIMONIO) || '',
        NUMOF: (config.old?.NUMOF ?? config.patrimonio?.NUMOF) || '',
        CODOBJETO: (config.old?.CODOBJETO ?? config.patrimonio?.CODOBJETO) || '',
        DEPATRIMONIO: (config.old?.DEPATRIMONIO ?? config.patrimonio?.DEPATRIMONIO) || '',
        DEHISTORICO: (config.old?.DEHISTORICO ?? config.patrimonio?.DEHISTORICO) || '',
        CDPROJETO: (config.old?.CDPROJETO ?? config.patrimonio?.CDPROJETO) || '',
        CDLOCAL: (config.old?.CDLOCAL ?? config.patrimonio?.CDLOCAL) || '',
        NMPLANTA: (config.old?.NMPLANTA ?? config.patrimonio?.NMPLANTA) || '',
        MARCA: (config.old?.MARCA ?? config.patrimonio?.MARCA) || '',
        MODELO: (config.old?.MODELO ?? config.patrimonio?.MODELO) || '',
        SITUACAO: (config.old?.SITUACAO ?? config.patrimonio?.SITUACAO) || 'EM USO',
        DTAQUISICAO: (config.old?.DTAQUISICAO ?? (config.patrimonio?.DTAQUISICAO ? config.patrimonio.DTAQUISICAO.split(' ')[0] : '')),
        DTBAIXA: (config.old?.DTBAIXA ?? (config.patrimonio?.DTBAIXA ? config.patrimonio.DTBAIXA.split(' ')[0] : '')),
      },
      // == ESTADO DA UI ==
      loading: false,
      searchModalOpen: false,
      searchTerm: '',
      searchResults: [],
      loadingSearch: false,
      nomeProjeto: '',
      locais: [],
      // Novo Local
      novoLocalOpen: false,
      novoLocalNome: '',
      novoLocalErro: '',
      salvandoNovoLocal: false,

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
            Object.keys(this.formData).forEach(key => {
              if (data.hasOwnProperty(key) && data[key] !== null) {
                if (key.startsWith('DT')) this.formData[key] = data[key].split(' ')[0];
                else this.formData[key] = data[key];
              }
            });
            if (this.formData.CDPROJETO) {
              await this.buscarProjetoELocais();
              this.formData.CDLOCAL = data.CDLOCAL;
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
      abrirNovoLocal() {
        if (!this.formData.CDPROJETO) {
          alert('Informe um projeto antes de cadastrar o local.');
          return;
        }
        this.novoLocalOpen = true;
        this.novoLocalNome = '';
        this.novoLocalErro = '';
        this.$nextTick(() => {
          const el = document.querySelector('input[x-model="novoLocalNome"]');
          el?.focus();
        });
      },
      fecharNovoLocal() {
        this.novoLocalOpen = false;
      },
      async salvarNovoLocal() {
        if (!this.novoLocalNome.trim()) {
          this.novoLocalErro = 'Digite o nome do local';
          return;
        }
        this.salvandoNovoLocal = true;
        this.novoLocalErro = '';
        try {
          const resp = await fetch(`/api/locais/${this.formData.CDPROJETO}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
              delocal: this.novoLocalNome
            })
          });
          if (resp.ok) {
            const novo = await resp.json();
            this.locais.push(novo);
            this.formData.CDLOCAL = novo.id;
            this.fecharNovoLocal();
          } else {
            const err = await resp.json().catch(() => ({}));
            this.novoLocalErro = err.error || 'Erro ao salvar.';
          }
        } catch (e) {
          this.novoLocalErro = 'Falha na requisição.';
        } finally {
          this.salvandoNovoLocal = false;
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
      async init() {
        // Se já existe projeto nos dados atuais (inclusive old()), carrega nome do projeto e locais
        if (this.formData.CDPROJETO) {
          const targetCdLocal = this.formData.CDLOCAL;
          await this.buscarProjetoELocais();
          if (targetCdLocal) this.formData.CDLOCAL = targetCdLocal;
        }
        // Se situação for BAIXA e não houver data, sugere hoje (apenas UX; ainda valida no backend)
        this.$watch('formData.SITUACAO', (val) => {
          const dt = document.getElementById('DTBAIXA');
          if (val === 'BAIXA') {
            dt?.setAttribute('required', 'required');
            // Se vazio, preenche com a data de hoje (YYYY-MM-DD)
            if (!this.formData.DTBAIXA) {
              const today = new Date();
              const yyyy = today.getFullYear();
              const mm = String(today.getMonth() + 1).padStart(2, '0');
              const dd = String(today.getDate()).padStart(2, '0');
              this.formData.DTBAIXA = `${yyyy}-${mm}-${dd}`;
            }
          } else {
            dt?.removeAttribute('required');
          }
        });
      }
    }
  }
</script>