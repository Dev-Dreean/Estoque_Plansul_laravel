@php
  use Carbon\Carbon;
  $filterKeys = ['nupatrimonio','cdprojeto','cdlocal','modelo','marca','descricao','situacao','conferido','matr_responsavel','cadastrado_por','numof','dtaquisicao_de','dtaquisicao_ate','dtcadastro_de','dtcadastro_ate','uf'];
  $badgeColors = [
    'nupatrimonio' => 'bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-700',
    'cdprojeto' => 'bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-blue-200 border-gray-400 dark:border-blue-700',
    'cdlocal' => 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-200 border-emerald-200 dark:border-emerald-700',
    'modelo' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-700',
    'marca' => 'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700',
    'descricao' => 'bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-700',
    'situacao' => 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 border-green-200 dark:border-green-700',
    'conferido' => 'bg-lime-100 dark:bg-lime-900 text-lime-700 dark:text-lime-300 border-lime-200 dark:border-lime-700',
    'matr_responsavel' => 'bg-cyan-100 dark:bg-cyan-900 text-cyan-700 dark:text-cyan-300 border-cyan-200 dark:border-cyan-700',
    'cadastrado_por' => 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700',
    'numof' => 'bg-violet-100 dark:bg-violet-900 text-violet-700 dark:text-violet-300 border-violet-200 dark:border-violet-700',
    'dtaquisicao_de' => 'bg-rose-100 dark:bg-rose-900 text-rose-700 dark:text-rose-200 border-rose-200 dark:border-rose-700',
    'dtaquisicao_ate' => 'bg-rose-100 dark:bg-rose-900 text-rose-700 dark:text-rose-200 border-rose-200 dark:border-rose-700',
    'dtcadastro_de' => 'bg-sky-100 dark:bg-sky-900 text-sky-700 dark:text-sky-200 border-sky-200 dark:border-sky-700',
    'dtcadastro_ate' => 'bg-sky-100 dark:bg-sky-900 text-sky-700 dark:text-sky-200 border-sky-200 dark:border-sky-700',
    'uf' => 'bg-teal-100 dark:bg-teal-900 text-teal-700 dark:text-teal-200 border-teal-200 dark:border-teal-700',
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
  $isBruno = isset($currentUser) ? strcasecmp((string) ($currentUser->NMLOGIN ?? ''), 'bruno') === 0 : (strcasecmp((string) (auth()->user()->NMLOGIN ?? ''), 'bruno') === 0);
  $brunoSkipDefault = (int) ($brunoSkipDefault ?? request('bruno_skip_default', 0));

  $baseParams = collect(request()->except(['cadastrados_por', 'cadastrado_por']))
    ->filter(function($v) { return !(is_null($v) || $v === ''); })
    ->all();
  if ($isBruno && $brunoSkipDefault) {
    $baseParams['bruno_skip_default'] = $brunoSkipDefault;
  }
@endphp

<div
  x-data="{
    open: false,
    focusFirst() {
      this.open = true;
      $nextTick(() => {
        const el = this.$refs.firstFilterInput;
        if (el) {
          el.focus();
          if (typeof el.select === 'function') {
            el.select();
          }
        }
      });
    },
    handleKey(event) {
      if (!event || event.defaultPrevented) return;
      if (event.ctrlKey || event.metaKey || event.altKey) return;
      if (event.key !== 'f' && event.key !== 'F') return;
      const target = event.target;
      const tag = target ? target.tagName : '';
      if (target && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag))) return;
      event.preventDefault();
      this.focusFirst();
    },
    handleEnter(event) {
      if (!event) return;
      const target = event.target;
      const tag = target ? target.tagName : '';
      if (!target || !['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) return;
      event.preventDefault();
      const form = this.$refs.filterForm || document.getElementById('patrimonio-filter-form');
      if (form) {
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit();
        } else {
          form.submit();
        }
      }
    }
  }"
  @click.outside="open = false"
  @keydown.window="handleKey($event)"
  @keydown.enter.prevent="handleEnter($event)"
  class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg mb-3"
  x-id="['filtro-patrimonios']"
  :aria-expanded="open.toString()"
  :aria-controls="$id('filtro-patrimonios')"
>
  <div class="flex justify-between items-center gap-3">
    <div class="flex items-center gap-3">
      <h3 class="font-semibold text-lg">Filtros de Busca</h3>
      <div x-cloak id="patrimonios-tags" class="flex items-center gap-2 ml-3" x-data="{ tags: @js($selecionados) }" @cadastradores-changed.window="tags = $event.detail">
        @foreach($filterKeys as $k)
          @php
            $labelsMap = [
              'nupatrimonio' => 'N. Patr.',
              'cdprojeto' => 'Projeto',
              'cdlocal' => 'Local Físico',
              'modelo' => 'Modelo',
              'marca' => 'Marca',
              'descricao' => 'Descrição',
              'situacao' => 'Situação',
              'conferido' => 'Conferido (todos)',
              'matr_responsavel' => 'Responsável',
              'cadastrado_por' => 'Cadastrado Por',
              'numof' => 'O.C',
              'dtaquisicao_de' => 'Aquisição De',
              'dtaquisicao_ate' => 'Aquisição Até',
              'dtcadastro_de' => 'Cadastro De',
              'dtcadastro_ate' => 'Cadastro Até',
              'uf' => 'UF',
            ];
            $raw = request($k);
            $values = collect(is_array($raw) ? $raw : ($raw !== null ? [$raw] : []))
              ->filter(fn($v) => $v !== '' && !is_null($v))
              ->values();
          @endphp
          @foreach($values as $val)
            @php
              $label = $labelsMap[$k] ?? str_replace('_',' ',ucfirst($k));
              $display = $val;
              if ($k === 'situacao' && $display === 'A DISPOSICAO') {
                $display = 'Disponivel';
              }
              if ($k === 'conferido') {
                $u = strtoupper(trim((string) $display));
                $display = in_array($u, ['S','1','SIM','TRUE','T','Y','YES','ON'], true) ? 'Verificado' : 'N�o verificado';
              }
              if (in_array($k, ['dtaquisicao_de','dtaquisicao_ate','dtcadastro_de','dtcadastro_ate'], true) && $display) {
                try { $display = Carbon::parse($display)->format('d/m/Y'); } catch (\Throwable $e) {}
              }
              $params = request()->except($k);
              if ($values->count() > 1) {
                $remaining = $values->values();
                $remaining->forget($loop->index);
                $params[$k] = $remaining->all();
              }
              if ($isBruno && $brunoSkipDefault) {
                $params['bruno_skip_default'] = $brunoSkipDefault ?: 1;
              }
              $tagUrl = route('patrimonios.index', $params);
            @endphp
            <a href="{{ $tagUrl }}" class="inline-flex items-center text-xs px-2 py-1 rounded-full border hover:opacity-90 {{ $badgeColors[$k] ?? 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-700' }}">
              <span class="truncate max-w-[120px]">{{ Str::limit((string)$display, 24) }}</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6.293 7.293a1 1 0 011.414 0L10 9.586l2.293-2.293a1 1 0 111.414 1.414L11.414 11l2.293 2.293a1 1 0 01-1.414 1.414L10 12.414l-2.293 2.293a1 1 0 01-1.414-1.414L8.586 11 6.293 8.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </a>
          @endforeach
        @endforeach
        <template x-for="user in tags" :key="user.login">
          <a
            :href="(() => { const params = new URLSearchParams(@js($baseParams)); tags.filter(t => t.login !== user.login).forEach(t => params.append('cadastrados_por[]', t.login)); @if($isBruno) params.set('bruno_skip_default', '1'); @endif return '{{ route('patrimonios.index') }}' + (params.toString() ? '?' + params.toString() : ''); })()"
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
    <button type="button" @click="open = !open" :aria-expanded="open.toString()" :aria-controls="$id('filtro-patrimonios')" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-600 dark:border-gray-600 bg-gray-600 dark:bg-gray-800 hover:bg-gray-700 dark:hover:bg-gray-700 text-white transition focus:outline-none focus:ring-2 focus:ring-gray-500">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
      <span class="sr-only">Expandir filtros</span>
    </button>
  </div>
  <div x-cloak x-show="open" x-transition class="mt-4 bg-gray-200 dark:bg-gray-800 rounded-lg p-4" :id="$id('filtro-patrimonios')">
    <form id="patrimonio-filter-form" x-ref="filterForm" method="GET" action="{{ route('patrimonios.index') }}" @submit="open=false">
      @if($isBruno)
        <input type="hidden" name="bruno_skip_default" value="{{ $brunoSkipDefault }}">
      @endif
      <div class="flex flex-wrap gap-3 lg:gap-4 overflow-visible pb-2 w-full mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
        <div class="flex-1 min-w-[100px] max-w-[140px] basis-[110px]">
          <input type="text" name="nupatrimonio" placeholder="N. Patr." value="{{ request('nupatrimonio') }}" x-ref="firstFilterInput" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md" />
        </div>
        <div class="flex-1 min-w-[120px] max-w-[170px] basis-[130px]">
          <div
            x-data="{
              search: '{{ request('cdprojeto') }}',
              value: '{{ request('cdprojeto') }}',
              open: false,
              options: @js($projetosOptions),
              syncValue() {
                const term = (this.search || '').trim();
                if (!term) {
                  this.value = '';
                  return;
                }
                const match = term.match(/^(\d+)/);
                this.value = match ? match[1] : term;
              },
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
                // Avisar o filtro de local para recarregar
                window.dispatchEvent(new CustomEvent('patrimonio-projeto-selecionado', { detail: this.value }));
              },
              handleManual() {
                this.syncValue();
                this.open = false;
                window.dispatchEvent(new CustomEvent('patrimonio-projeto-selecionado', { detail: this.value }));
              },
              init() {
                const current = this.options.find(o => o.code === this.value);
                if (current) this.search = current.label;
                // Se j� vier com projeto selecionado, notificar locais
                if (this.value) {
                  window.dispatchEvent(new CustomEvent('patrimonio-projeto-selecionado', { detail: this.value }));
                }
              }
            }"
            class="relative"
            @click.outside="open=false"
          >
            <input
              type="text"
              x-model="search"
              @input="open=search.length>0; syncValue()"
              @keydown.enter.prevent="handleManual()"
              @blur="syncValue()"
              placeholder="Projeto"
              class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md"
            />
            <input type="hidden" name="cdprojeto" :value="value">
            <div
              x-show="open"
              x-transition
              class="absolute z-40 mt-1 w-full bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg max-h-56 overflow-y-auto"
            >
              <template x-for="opt in filtered()" :key="opt.code">
                <button type="button" class="flex justify-between w-full px-3 py-2 text-sm hover:bg-gray-600 dark:hover:bg-gray-700 text-gray-900 dark:text-gray-200"
                  @click="select(opt)">
                  <span x-text="opt.label"></span>
                </button>
              </template>
              <div x-show="filtered().length === 0" class="px-3 py-2 text-xs text-blue-600 dark:text-gray-400">Nenhum resultado</div>
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
              loading: false,
              async fetchLocais(projCode, preserveValue = false) {
                const code = (projCode || '').toString().trim();
                this.options = [];
                if (!code) {
                  this.value = preserveValue ? this.value : '';
                  this.search = preserveValue ? this.search : '';
                  return;
                }
                this.loading = true;
                try {
                  const resp = await fetch(`/api/locais/buscar?cdprojeto=${encodeURIComponent(code)}`);
                  if (resp.ok) {
                    const data = await resp.json();
                    this.options = (data || []).map(l => ({
                      code: String(l.cdlocal),
                      label: `${l.cdlocal} - ${(l.LOCAL || l.delocal || '').trim()}`.trim(),
                    }));
                    if (preserveValue && this.value) {
                      const current = this.options.find(o => o.code === this.value);
                      if (current) {
                        this.search = current.label;
                      } else {
                        this.value = '';
                        this.search = '';
                      }
                    }
                  } else {
                    this.options = [];
                  }
                } catch (e) {
                  console.error('Erro ao buscar locais do projeto', e);
                  this.options = [];
                } finally {
                  this.loading = false;
                }
              },
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
                // Carregar os locais do projeto selecionado (se houver)
                const initialProj = '{{ request('cdprojeto') }}';
                if (initialProj) {
                  this.fetchLocais(initialProj, true);
                }
                // Ouvir mudan�as no projeto
                window.addEventListener('patrimonio-projeto-selecionado', (e) => {
                  const proj = (e.detail || '').toString();
                  this.value = '';
                  this.search = '';
                  this.fetchLocais(proj, false);
                });

                // Se j� veio com local preenchido e op��es carregadas via servidor, sincronizar o nome
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
              @input="open=search.length>0"
              placeholder="Local Físico"
              class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md"
            />
            <input type="hidden" name="cdlocal" :value="value">
            <div
              x-show="open"
              x-transition
              class="absolute z-40 mt-1 w-full bg-white dark:bg-gray-50 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-56 overflow-y-auto"
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
            <input type="text" x-model="search" @input="open=search.length>0" placeholder="Modelo" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md" />
            <input type="hidden" name="modelo" :value="value">
            <div x-show="open" x-transition class="absolute z-40 mt-1 w-full bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg max-h-56 overflow-y-auto">
              <template x-for="opt in filtered()" :key="opt.code">
                <button type="button" class="flex justify-between w-full px-3 py-2 text-sm hover:bg-gray-600 dark:hover:bg-gray-700 text-gray-900 dark:text-gray-200" @click="select(opt)">
                  <span x-text="opt.label"></span>
                </button>
              </template>
              <div x-show="filtered().length === 0" class="px-3 py-2 text-xs text-blue-600 dark:text-gray-400">Nenhum resultado</div>
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
            <input type="text" x-model="search" @input="open=search.length>0" placeholder="Marca" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md" />
            <input type="hidden" name="marca" :value="value">
            <div x-show="open" x-transition class="absolute z-40 mt-1 w-full bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg max-h-56 overflow-y-auto">
              <template x-for="opt in filtered()" :key="opt.code">
                <button type="button" class="flex justify-between w-full px-3 py-2 text-sm hover:bg-gray-600 dark:hover:bg-gray-700 text-gray-900 dark:text-gray-200" @click="select(opt)">
                  <span x-text="opt.label"></span>
                </button>
              </template>
              <div x-show="filtered().length === 0" class="px-3 py-2 text-xs text-blue-600 dark:text-gray-400">Nenhum resultado</div>
            </div>
          </div>
        </div>
        <div class="flex-1 min-w-[130px] max-w-[170px] basis-[140px]">
          <input type="text" name="DescriÃ§Ã£o" placeholder="Descrição" value="{{ request('descricao') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md" />
        </div>
        <div class="flex-1 min-w-[130px] max-w-[170px] basis-[140px]">
          <div
            x-data="{
              open: false,
              options: ['EM USO','BAIXA','CONSERTO','A DISPOSICAO'],
              selected: @js(collect((array)request('situacao'))->filter()->values()->all()),
              toggle(opt) {
                if (this.selected.includes(opt)) {
                  this.selected = this.selected.filter(v => v !== opt);
                } else {
                  this.selected.push(opt);
                }
              }
            }"
            class="relative"
            @click.outside="open=false"
          >
            <button type="button" @click="open=!open" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md flex items-center justify-between">
              <span x-text="selected.length ? selected.join(', ') : 'Situação'"></span>
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 011.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
            </button>
            <div x-show="open" x-transition class="absolute z-40 mt-1 w-full bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg">
              <template x-for="opt in options" :key="opt">
                <label class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-600 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-gray-200">
                  <input type="checkbox" :value="opt" x-model="selected" class="rounded text-indigo-600 border-gray-300">
                  <span x-text="opt === 'A DISPOSICAO' ? 'DISPONÍVEL' : opt"></span>
                </label>
              </template>
            </div>
            <template x-for="opt in selected" :key="opt">
              <input type="hidden" name="situacao[]" :value="opt">
            </template>
          </div>
        </div>
        <div class="flex-1 min-w-[130px] max-w-[170px] basis-[140px]">
          <select name="conferido" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md">
            <option value="">Conferido (todos)</option>
            <option value="S" @selected(request('conferido') === 'S')>Verificados</option>
            <option value="N" @selected(request('conferido') === 'N')>Não Verificados</option>
          </select>
        </div>
        <div class="flex-1 min-w-[150px] max-w-[210px] basis-[170px]">
          <x-employee-autocomplete 
            id="matr_responsavel_search"
            name="matr_responsavel"
            placeholder="Responsável (matrícula ou nome)"
            value="{{ request('matr_responsavel') }}"
          />
        </div>
        <div class="flex-1 min-w-[100px] max-w-[140px] basis-[110px]">
          <input type="text" name="numof" placeholder="O.C" value="{{ request('numof') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md" />
        </div>
        <div class="flex-1 min-w-[140px] max-w-[170px] basis-[150px]">
          <div
            x-data="{
              open:false,
              options: ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'],
              selected: @js(collect((array)request('uf'))->filter()->values()->all()),
            }"
            class="relative"
            @click.outside="open=false"
          >
            <button type="button" @click="open=!open" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md flex items-center justify-between">
              <span x-text="selected.length ? selected.join(', ') : 'UF'"></span>
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 011.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
            </button>
            <div x-show="open" x-transition class="absolute z-40 mt-1 w-full max-h-48 overflow-y-auto bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-lg">
              <template x-for="opt in options" :key="opt">
                <label class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-600 dark:hover:bg-gray-700 cursor-pointer text-gray-900 dark:text-gray-200">
                  <input type="checkbox" :value="opt" x-model="selected" class="rounded text-indigo-600 border-gray-300">
                  <span x-text="opt"></span>
                </label>
              </template>
            </div>
            <template x-for="opt in selected" :key="opt">
              <input type="hidden" name="uf[]" :value="opt">
            </template>
          </div>
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
            placeholder="Usuário (múltiplos)"
            class="h-10 px-2 sm:px-3 w-full min-w-0 text-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-200 rounded-md"
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

      <div class="flex flex-wrap gap-3 lg:gap-4 overflow-visible pb-2 w-full">
        <div
          class="flex flex-col gap-2 flex-[1.3_1_260px] min-w-[240px] bg-white/70 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 shadow-sm"
          x-data="{
            range: {{ request()->filled('dtaquisicao_ate') ? 'true' : 'false' }},
          }"
        >
          <div class="flex items-center justify-between gap-2">
            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">Aquisição</span>
            <label class="inline-flex items-center gap-1 text-xs text-gray-600 dark:text-gray-300">
              <input type="checkbox" name="filtrar_aquisicao" value="1" x-model="range" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
              <span>Intervalo</span>
            </label>
          </div>
          <div class="flex items-center gap-2">
            <input type="date" name="dtaquisicao_de" value="{{ request('dtaquisicao_de') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-200 rounded-md" aria-label="Data de Aquisição" />
            <template x-if="range">
              <span class="text-xs text-gray-500 dark:text-gray-300">até</span>
            </template>
            <input x-show="range" x-cloak type="date" name="dtaquisicao_ate" value="{{ request('dtaquisicao_ate') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-200 rounded-md" aria-label="Data de Aquisição Até" />
          </div>
        </div>
        <div
          class="flex flex-col gap-2 flex-[1.3_1_260px] min-w-[240px] bg-white/70 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 shadow-sm"
          x-data="{
            range: {{ request()->filled('dtcadastro_ate') ? 'true' : 'false' }},
          }"
        >
          <div class="flex items-center justify-between gap-2">
            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">Cadastro</span>
            <label class="inline-flex items-center gap-1 text-xs text-gray-600 dark:text-gray-300">
              <input type="checkbox" name="filtrar_cadastro" value="1" x-model="range" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
              <span>Intervalo</span>
            </label>
          </div>
          <div class="flex items-center gap-2">
            <input type="date" name="dtcadastro_de" value="{{ request('dtcadastro_de') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-200 rounded-md" aria-label="Data de Cadastro" />
            <template x-if="range">
              <span class="text-xs text-gray-500 dark:text-gray-300">at�</span>
            </template>
            <input x-show="range" x-cloak type="date" name="dtcadastro_ate" value="{{ request('dtcadastro_ate') }}" class="h-10 px-2 sm:px-3 w-full text-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-200 rounded-md" aria-label="Data de Cadastro Até" />
          </div>
        </div>
      </div>
      <div class="flex flex-wrap items-center justify-between mt-4 gap-4">
        <div class="flex items-center gap-3">
          <x-primary-button class="h-10 px-4">
            {{ __('Filtrar') }}
          </x-primary-button>

          <a href="{{ route('patrimonios.index', $isBruno ? ['bruno_skip_default' => 1] : []) }}" data-ajax-clean class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md">
            Limpar
          </a>
        </div>

        <label class="flex items-center gap-2 ml-auto shrink-0">
          <span class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">Itens por pagina</span>
          <select name="per_page" class="h-10 px-2 sm:px-3 w-24 text-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-gray-200 rounded-md">
            @foreach([10,30,50,100,200] as $opt)
              <option value="{{ $opt }}" @selected(request('per_page', 30)==$opt)>{{ $opt }}</option>
            @endforeach
          </select>
        </label>
      </div>
    </form>
  </div>
</div>
