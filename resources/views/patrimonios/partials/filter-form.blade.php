@php
  $filterKeys = ['nupatrimonio','cdprojeto','cdlocal','modelo','marca','descricao','situacao','matr_responsavel','cadastrado_por'];
  $badgeColors = [
    'nupatrimonio' => 'bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700',
    'cdprojeto' => 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 border-blue-200 dark:border-blue-700',
    'cdlocal' => 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-200 border-emerald-200 dark:border-emerald-700',
    'modelo' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-700',
    'marca' => 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700',
    'descricao' => 'bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-700',
    'situacao' => 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 border-green-200 dark:border-green-700',
    'matr_responsavel' => 'bg-cyan-100 dark:bg-cyan-900 text-cyan-700 dark:text-cyan-300 border-cyan-200 dark:border-cyan-700',
    'cadastrado_por' => 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700',
  ];

  $todosCadastradores = $cadastradores->sortBy('NOMEUSER')->values()->map(function($u){
    $partes = array_values(array_filter(explode(' ', $u->NOMEUSER ?? '')));
    $primeiro = $partes[0] ?? ($u->NOMEUSER ?? '');
    $ultimoInicial = isset($partes[1]) ? substr($partes[count($partes)-1], 0, 1) : '';
    $display = trim($primeiro . ($ultimoInicial ? ' ' . $ultimoInicial . '.' : ''));
    return ['login' => $u->NMLOGIN, 'nome' => $display ?: $u->NMLOGIN];
  });
  $projetosOptions = ($projetos ?? collect())->map(fn($p) => [
    'code' => (string) $p->codigo,
    'label' => trim((string) ($p->codigo . ' - ' . $p->descricao)),
  ])->toArray();
  $locaisOptions = ($locais ?? collect())->map(fn($l) => [
    'code' => (string) $l->codigo,
    'label' => trim((string) ($l->codigo . ' - ' . $l->descricao)),
  ])->toArray();
  $modelosOptions = ($modelos ?? collect())->pluck('MODELO')->filter()->unique()->values()->map(fn($m) => ['code' => $m, 'label' => $m])->toArray();
  $marcasOptions = ($marcas ?? collect())->pluck('MARCA')->filter()->unique()->values()->map(fn($m) => ['code' => $m, 'label' => $m])->toArray();
  $selecionadosRequest = collect((array) request()->input('cadastrados_por', []))->filter();
  if ($selecionadosRequest->isEmpty() && request()->filled('cadastrado_por')) {
    $selecionadosRequest = collect([request('cadastrado_por')]);
  }
  $selecionados = $selecionadosRequest->map(function($login) use ($todosCadastradores) {
    $match = $todosCadastradores->first(fn($c) => strcasecmp($c['login'], $login) === 0);
    return $match ?: ['login' => $login, 'nome' => $login];
  })->values();

  $baseParams = collect(request()->except(['cadastrados_por', 'cadastrado_por']))
    ->filter(function($v) { return !(is_null($v) || $v === ''); })
    ->all();
@endphp

<div x-data="{ open: false }" @click.outside="open = false" class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg mb-3" x-id="['filtro-patrimonios']" :aria-expanded="open.toString()" :aria-controls="$id('filtro-patrimonios')">
  <div class="flex justify-between items-center gap-3">
    <div class="flex items-center gap-3">
      <h3 class="font-semibold text-lg">Filtros de Busca</h3>
      <div x-cloak id="patrimonios-tags" class="flex items-center gap-2 ml-3" x-data="{ tags: @js($selecionados) }" @cadastradores-changed.window="tags = $event.detail">
        @foreach($filterKeys as $k)
          @if(request()->filled($k))
            @php
              $labelsMap = [
                'nupatrimonio' => 'N. Patr.',
                'cdprojeto' => 'Projeto',
                'cdlocal' => 'Local',
                'modelo' => 'Modelo',
                'marca' => 'Marca',
                'descricao' => 'Descricao',
                'situacao' => 'Situacao',
                'matr_responsavel' => 'Responsavel',
                'cadastrado_por' => 'Cadastrador',
              ];
              $label = $labelsMap[$k] ?? str_replace('_',' ',ucfirst($k));
              $value = request($k);
              if ($k === 'situacao' && $value === 'A DISPOSICAO') {
                $value = 'Disponivel';
              }
            @endphp
            <a href="{{ route('patrimonios.index', request()->except($k)) }}" class="inline-flex items-center text-xs px-2 py-1 rounded-full border hover:opacity-90 {{ $badgeColors[$k] ?? 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700' }}">
              <span class="truncate max-w-[120px]">{{ $label }}: {{ Str::limit((string)$value, 24) }}</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6.293 7.293a1 1 0 011.414 0L10 9.586l2.293-2.293a1 1 0 111.414 1.414L11.414 11l2.293 2.293a1 1 0 01-1.414 1.414L10 12.414l-2.293 2.293a1 1 0 01-1.414-1.414L8.586 11 6.293 8.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </a>
          @endif
        @endforeach
        <template x-for="user in tags" :key="user.login">
          <a
            :href="(() => { const params = new URLSearchParams(@js($baseParams)); tags.filter(t => t.login !== user.login).forEach(t => params.append('cadastrados_por[]', t.login)); return '{{ route('patrimonios.index') }}' + (params.toString() ? '?' + params.toString() : ''); })()"
            data-ajax-tag-remove
            data-tags-count="{{ count($selecionados) }}"
            x-bind:data-tags-count="tags.length"
            class="inline-flex items-center text-xs px-2 py-1 rounded-full border bg-indigo-50 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-100 border-indigo-200 dark:border-indigo-700 shadow-sm"
          >
            <span class="truncate max-w-[120px]" x-text="user.nome"></span>
            <span class="ml-1 text-gray-500">x</span>
          </a>
        </template>
      </div>
    </div>
    <button type="button" @click="open = !open" :aria-expanded="open.toString()" :aria-controls="$id('filtro-patrimonios')" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-500">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
      <span class="sr-only">Expandir filtros</span>
    </button>
  </div>
  <div x-cloak x-show="open" x-transition class="mt-4" :id="$id('filtro-patrimonios')">
    <form id="patrimonio-filter-form" method="GET" action="{{ route('patrimonios.index') }}" @submit="open=false">
      <div class="flex flex-wrap gap-3 lg:gap-4 overflow-visible pb-2 w-full">
        <div class="flex-1 min-w-[100px] max-w-[140px] basis-[110px]">
          <input type="text" name="nupatrimonio" placeholder="N. Patr." value="{{ request('nupatrimonio') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
        </div>
        <div class="flex-1 min-w-[120px] max-w-[170px] basis-[130px]">
          <div
            x-data="{
              search: '{{ request('cdprojeto') }}',
              value: '{{ request('cdprojeto') }}',
              open: false,
              options: @js($projetosOptions),
              filtered() {
                const term = (this.search || '').toLowerCase();
                if (!term.length) return [];
                const scored = this.options
                  .map(o => {
                    const label = o.label.toLowerCase();
                    const code = (o.code || '').toLowerCase();
                    const exact = label === term || code === term;
                    const starts = label.startsWith(term) || code.startsWith(term);
                    const includes = label.includes(term) || code.includes(term);
                    const score = exact ? 0 : starts ? 1 : includes ? 2 : 3;
                    return { ...o, score };
                  })
                  .filter(o => o.score < 3)
                  .sort((a, b) => a.score - b.score || a.label.localeCompare(b.label));
                return scored.slice(0, 8);
              },
              select(opt) {
                this.value = opt.code;
                this.search = opt.label;
                this.open = false;
              },
              init() {
                const current = this.options.find(o => o.code === this.value);
                if (current) this.search = current.label;
              }
            }"
            class="relative"
            @click.outside="open=false"
          >
            <input
              type="text"
              x-model="search"
              @focus="open=true"
              @input="open=search.length>0"
              placeholder="Projeto"
              class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md"
            />
            <input type="hidden" name="cdprojeto" :value="value">
            <div
              x-show="open"
              x-transition
              class="absolute z-40 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-56 overflow-y-auto"
            >
              <template x-for="opt in filtered()" :key="opt.code">
                <button type="button" class="flex justify-between w-full px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="select(opt)">
                  <span x-text="opt.label"></span>
                </button>
              </template>
              <div x-show="filtered().length === 0" class="px-3 py-2 text-xs text-gray-500">Nenhum resultado</div>
            </div>
          </div>
        </div>
        <div class="flex-1 min-w-[130px] max-w-[170px] basis-[140px]">
          <div
            x-data="{
              search: '{{ request('cdlocal') }}',
              value: '{{ request('cdlocal') }}',
              open: false,
              options: @js($locaisOptions),
              filtered() {
                const term = (this.search || '').toLowerCase();
                if (!term.length) return [];
                const scored = this.options
                  .map(o => {
                    const label = o.label.toLowerCase();
                    const code = (o.code || '').toLowerCase();
                    const exact = label === term || code === term;
                    const starts = label.startsWith(term) || code.startsWith(term);
                    const includes = label.includes(term) || code.includes(term);
                    const score = exact ? 0 : starts ? 1 : includes ? 2 : 3;
                    return { ...o, score };
                  })
                  .filter(o => o.score < 3)
                  .sort((a, b) => a.score - b.score || a.label.localeCompare(b.label));
                return scored.slice(0, 8);
              },
              select(opt) {
                this.value = opt.code;
                this.search = opt.label;
                this.open = false;
              },
              init() {
                const current = this.options.find(o => o.code === this.value);
                if (current) this.search = current.label;
              }
            }"
            class="relative"
            @click.outside="open=false"
          >
            <input
              type="text"
              x-model="search"
              @focus="open=true"
              @input="open=search.length>0"
              placeholder="Local"
              class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md"
            />
            <input type="hidden" name="cdlocal" :value="value">
            <div
              x-show="open"
              x-transition
              class="absolute z-40 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-56 overflow-y-auto"
            >
              <template x-for="opt in filtered()" :key="opt.code">
                <button type="button" class="flex justify-between w-full px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                  @click="select(opt)">
                  <span x-text="opt.label"></span>
                </button>
              </template>
              <div x-show="filtered().length === 0" class="px-3 py-2 text-xs text-gray-500">Nenhum resultado</div>
            </div>
          </div>
        </div>
        <div class="flex-1 min-w-[120px] max-w-[170px] basis-[130px]">
          <div
            x-data="{
              search: '{{ request('modelo') }}',
              value: '{{ request('modelo') }}',
              open: false,
              options: @js($modelosOptions),
              filtered() {
                const term = (this.search || '').toLowerCase();
                if (!term.length) return [];
                const scored = this.options
                  .map(o => {
                    const label = o.label.toLowerCase();
                    const exact = label === term;
                    const starts = label.startsWith(term);
                    const includes = label.includes(term);
                    const score = exact ? 0 : starts ? 1 : includes ? 2 : 3;
                    return { ...o, score };
                  })
                  .filter(o => o.score < 3)
                  .sort((a, b) => a.score - b.score || a.label.localeCompare(b.label));
                return scored.slice(0, 8);
              },
              select(opt) { this.value = opt.code; this.search = opt.label; this.open = false; },
              init() { const current = this.options.find(o => o.code === this.value); if (current) this.search = current.label; }
            }"
            class="relative"
            @click.outside="open=false"
          >
            <input type="text" x-model="search" @focus="open=true" @input="open=search.length>0" placeholder="Modelo" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
            <input type="hidden" name="modelo" :value="value">
            <div x-show="open" x-transition class="absolute z-40 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-56 overflow-y-auto">
              <template x-for="opt in filtered()" :key="opt.code">
                <button type="button" class="flex justify-between w-full px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700" @click="select(opt)">
                  <span x-text="opt.label"></span>
                </button>
              </template>
              <div x-show="filtered().length === 0" class="px-3 py-2 text-xs text-gray-500">Nenhum resultado</div>
            </div>
          </div>
        </div>
        <div class="flex-1 min-w-[130px] max-w-[170px] basis-[140px]">
          <div
            x-data="{
              search: '{{ request('marca') }}',
              value: '{{ request('marca') }}',
              open: false,
              options: @js($marcasOptions),
              filtered() {
                const term = (this.search || '').toLowerCase();
                if (!term.length) return [];
                const scored = this.options
                  .map(o => {
                    const label = o.label.toLowerCase();
                    const exact = label === term;
                    const starts = label.startsWith(term);
                    const includes = label.includes(term);
                    const score = exact ? 0 : starts ? 1 : includes ? 2 : 3;
                    return { ...o, score };
                  })
                  .filter(o => o.score < 3)
                  .sort((a, b) => a.score - b.score || a.label.localeCompare(b.label));
                return scored.slice(0, 8);
              },
              select(opt) { this.value = opt.code; this.search = opt.label; this.open = false; },
              init() { const current = this.options.find(o => o.code === this.value); if (current) this.search = current.label; }
            }"
            class="relative"
            @click.outside="open=false"
          >
            <input type="text" x-model="search" @focus="open=true" @input="open=search.length>0" placeholder="Marca" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
            <input type="hidden" name="marca" :value="value">
            <div x-show="open" x-transition class="absolute z-40 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-56 overflow-y-auto">
              <template x-for="opt in filtered()" :key="opt.code">
                <button type="button" class="flex justify-between w-full px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700" @click="select(opt)">
                  <span x-text="opt.label"></span>
                </button>
              </template>
              <div x-show="filtered().length === 0" class="px-3 py-2 text-xs text-gray-500">Nenhum resultado</div>
            </div>
          </div>
        </div>
        <div class="flex-1 min-w-[130px] max-w-[170px] basis-[140px]">
          <input type="text" name="descricao" placeholder="Descricao" value="{{ request('descricao') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md" />
        </div>
        <div class="flex-1 min-w-[130px] max-w-[170px] basis-[140px]">
          <select name="situacao" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md">
            <option value="">Situacao</option>
            <option value="EM USO" @selected(request('situacao') === 'EM USO')>EM USO</option>
            <option value="BAIXA" @selected(request('situacao') === 'BAIXA')>BAIXA</option>
            <option value="CONSERTO" @selected(request('situacao') === 'CONSERTO')>CONSERTO</option>
            <option value="A DISPOSICAO" @selected(request('situacao') === 'A DISPOSICAO')>DISPONIVEL</option>
          </select>
        </div>
        <div class="flex-1 min-w-[150px] max-w-[210px] basis-[170px]">
          <x-employee-autocomplete 
            id="matr_responsavel_search"
            name="matr_responsavel"
            placeholder="Responsavel (matricula ou nome)"
            value="{{ request('matr_responsavel') }}"
          />
        </div>
        <div
          x-data="{
            search: '',
            open: false,
            all: @js($todosCadastradores),
            selected: @js($selecionados),
            filtered() {
              const term = this.search.toLowerCase();
              return this.all
                .filter(u => !this.selected.find(s => s.login === u.login))
                .filter(u => u.nome.toLowerCase().includes(term) || u.login.toLowerCase().includes(term))
                .slice(0, 8);
            },
            add(user) {
              if (!user || this.selected.find(s => s.login === user.login)) return;
              this.selected.push(user);
              this.search = '';
              this.open = false;
              window.dispatchEvent(new CustomEvent('cadastradores-changed', { detail: this.selected }));
            },
            remove(login) {
              this.selected = this.selected.filter(s => s.login !== login);
              window.dispatchEvent(new CustomEvent('cadastradores-changed', { detail: this.selected }));
            },
            selectFirst() {
              const first = this.filtered()[0];
              if (first) this.add(first);
            }
          }"
          class="relative flex-[1.3_1_220px] min-w-[200px]"
          @click.outside="open=false"
          x-init="window.dispatchEvent(new CustomEvent('cadastradores-changed', { detail: selected }))"
        >
          <input
            type="text"
            x-model="search"
            @focus="open=true"
            @input="open=true"
            @keydown.enter.prevent="selectFirst()"
            @keydown.backspace="if(!search && selected.length){ $event.preventDefault(); remove(selected[selected.length-1].login) }"
            placeholder="Usuario (multiplos)"
            class="h-10 px-2 sm:px-3 w-full min-w-0 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md"
          />
          <div
            x-show="open"
            x-transition
            class="absolute z-40 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-56 overflow-y-auto"
          >
            <template x-for="user in filtered()" :key="user.login">
              <button type="button" class="flex justify-between w-full px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                @click="add(user)">
                <span x-text="user.nome"></span>
                <span class="text-xs text-gray-500" x-text="user.login"></span>
              </button>
            </template>
            <div x-show="filtered().length === 0" class="px-3 py-2 text-xs text-gray-500">Nenhum resultado</div>
          </div>
          <template x-for="user in selected" :key="user.login">
            <input type="hidden" name="cadastrados_por[]" :value="user.login">
          </template>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between mt-4 gap-4">
        <div class="flex items-center gap-3">
          <x-primary-button class="h-10 px-4">
            {{ __('Filtrar') }}
          </x-primary-button>

          <a href="{{ route('patrimonios.index') }}" data-ajax-clean class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md">
            Limpar
          </a>
        </div>

        <label class="flex items-center gap-2 ml-auto shrink-0">
          <span class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">Itens por pagina</span>
          <select name="per_page" class="h-10 px-2 sm:px-3 w-24 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md">
            @foreach([10,30,50,100,200] as $opt)
              <option value="{{ $opt }}" @selected(request('per_page', 30)==$opt)>{{ $opt }}</option>
            @endforeach
          </select>
        </label>
      </div>
    </form>
  </div>
</div>
