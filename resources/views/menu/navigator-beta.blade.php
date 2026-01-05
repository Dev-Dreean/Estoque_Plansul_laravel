<x-app-layout>
@php
  $controleItems = [
    ['label' => 'Patrimonio', 'href' => route('patrimonios.index'), 'icon' => 'cube', 'active' => true],
    ['label' => 'Atribuir Cod.', 'href' => route('patrimonios.atribuir'), 'icon' => 'tag'],
  ];

  $atalhos = [
    ['label' => 'Dashboard', 'href' => route('dashboard'), 'icon' => 'chart'],
    ['label' => 'Removidos', 'href' => route('removidos.index'), 'icon' => 'trash'],
    ['label' => 'Histórico', 'href' => route('historico.index'), 'icon' => 'clock'],
    ['label' => 'Locais', 'href' => route('projetos.index'), 'icon' => 'map'],
    ['label' => 'Usuários', 'href' => route('usuarios.index'), 'icon' => 'users'],
    ['label' => 'Telas', 'href' => route('cadastro-tela.index'), 'icon' => 'window'],
    ['label' => 'Tema', 'href' => route('settings.theme'), 'icon' => 'swatch'],
  ];

  $icons = [
    'chevron-left' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>',
    'chevron-right' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>',
    'stack' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7l8-4 8 4-8 4-8-4z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12l8 4 8-4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 17l8 4 8-4"/></svg>',
    'cube' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4 8 4 8-4z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10l8 4 8-4V7"/></svg>',
    'tag' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.59 13.41l-8-8A2 2 0 0011.17 5H5a2 2 0 00-2 2v6.17a2 2 0 00.59 1.42l8 8a2 2 0 002.82 0l6.18-6.18a2 2 0 000-2.82z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01"/></svg>',
    'clock' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9" stroke-width="2"/></svg>',
    'trash' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-1 0l-.5 11a2 2 0 01-2 2H10.5a2 2 0 01-2-2L8 7m4 4v6m4-6v6"/></svg>',
    'chart' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3v18M6 9v12M16 13v8M21 5v16"/></svg>',
    'map' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5 2V6l5-2m0 16l6-2m-6 2V4m6 14l5 2V6l-5-2m0 16V4M9 4l6 2"/></svg>',
    'users' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M23 20v-2a4 4 0 00-3-3.87"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 3.13a4 4 0 010 7.75"/></svg>',
    'window' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="14" rx="2" ry="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16"/></svg>',
    'swatch' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2v-5H5v5a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v4"/></svg>',
  ];

  $hiddenColumns = $hiddenColumns ?? [];
  $showEmpty = $showEmptyColumns ?? false;
  $activeFilters = collect([request('descricao'), request('situacao'), request('cdprojeto'), request('cdlocal')])->filter()->count();
  $totalRegistros = $patrimonios->total() ?? 0;
@endphp

<div x-data="navigatorShell()" class="min-h-[calc(100vh-64px)] bg-gradient-to-br from-slate-950 via-slate-950 to-slate-900 text-slate-100">
  <div class="flex">
    <aside :class="expanded ? 'w-68' : 'w-20'" class="relative flex-shrink-0 transition-all duration-300 bg-slate-950/95 border-r border-slate-900 min-h-[calc(100vh-64px)] shadow-2xl/30">
      <div class="flex items-center justify-between h-14 px-3 border-b border-slate-900">
        <div class="flex items-center space-x-3 overflow-hidden">
          <div class="h-9 w-9 rounded-xl bg-emerald-500/20 text-emerald-200 flex items-center justify-center font-bold text-sm">NB</div>
          <div x-show="expanded" class="leading-tight">
            <p class="text-xs text-emerald-200/80 uppercase tracking-[0.18em]">Beta</p>
            <span class="text-sm font-semibold text-slate-100 whitespace-nowrap">Navigator</span>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="toggleTheme"
            :aria-pressed="isDark.toString()"
            class="h-8 w-8 rounded-full border border-slate-800 bg-slate-900 text-xs font-semibold text-slate-200 hover:border-indigo-500 hover:text-indigo-200 transition"
          >
            <span x-show="!isDark" aria-hidden="true">☾</span>
            <span x-show="isDark" aria-hidden="true">☀</span>
            <span class="sr-only">Alternar tema</span>
          </button>
          <button @click="expanded = !expanded" class="p-2 rounded-md text-slate-400 hover:bg-slate-900">
            <span x-show="expanded" class="block">{!! $icons['chevron-left'] !!}</span>
            <span x-show="!expanded" class="block">{!! $icons['chevron-right'] !!}</span>
          </button>
        </div>
      </div>

      <nav class="p-3 space-y-5">
        <div>
          <button
            @click="open.controle = !open.controle"
            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md text-slate-200 hover:bg-slate-800"
            :class="!expanded ? 'justify-center' : ''"
          >
            <div class="flex items-center gap-2">
              {!! $icons['stack'] !!}
              <span x-show="expanded">Controle</span>
            </div>
            <span x-show="expanded" :class="open.controle ? '' : 'rotate-180'" class="transition-transform">{!! $icons['chevron-left'] !!}</span>
          </button>
          <div x-show="open.controle && expanded" x-transition class="mt-2 space-y-1 pl-2">
            @foreach($controleItems as $item)
              <a href="{{ $item['href'] }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-indigo-900/30 hover:text-indigo-100 transition {{ ($item['active'] ?? false) ? 'bg-indigo-900/40 text-indigo-100 border border-indigo-700/50 shadow-inner' : 'text-slate-200' }}">
                {!! $icons[$item['icon']] !!}
                <span>{{ $item['label'] }}</span>
              </a>
            @endforeach
          </div>
        </div>

        <div class="space-y-1">
          <p x-show="expanded" class="text-xs font-semibold text-slate-500 px-1">Atalhos</p>
          @foreach($atalhos as $item)
            <a href="{{ $item['href'] }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-200 hover:bg-slate-900/80 transition" :class="!expanded ? 'justify-center' : ''">
              {!! $icons[$item['icon']] !!}
              <span x-show="expanded">{{ $item['label'] }}</span>
              <span class="sr-only" x-show="!expanded">{{ $item['label'] }}</span>
            </a>
          @endforeach
        </div>
      </nav>

      <div class="mt-auto p-3 border-t border-slate-900 space-y-3">
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-full bg-slate-900 flex items-center justify-center text-sm font-semibold text-slate-100">
            {{ strtoupper(substr(Auth::user()->NOMEUSER ?? 'U', 0, 1)) }}
          </div>
          <div class="min-w-0" x-show="expanded">
            <p class="text-sm font-semibold text-slate-100 truncate">{{ Auth::user()->NOMEUSER ?? 'Usuario' }}</p>
            <p class="text-xs text-slate-400 truncate">{{ Auth::user()->NMLOGIN ?? 'login' }}</p>
          </div>
        </div>
        <div x-show="expanded">
          <label class="flex items-center justify-between gap-3 px-3 py-2 rounded-lg bg-slate-900/70 border border-slate-800 text-sm text-slate-200 shadow-inner">
            <div class="flex items-center gap-2">
              <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-amber-300">&#9790;</span>
              <div class="leading-tight">
                <p class="font-semibold">Tema</p>
                <p class="text-xs text-slate-400">Dark / White</p>
              </div>
            </div>
            <button
              type="button"
              @click="toggleTheme"
              :aria-pressed="isDark.toString()"
              class="relative inline-flex h-7 w-12 items-center rounded-full border border-slate-700 bg-slate-800 transition"
              :class="isDark ? 'bg-indigo-600 border-indigo-500' : 'bg-slate-300 border-slate-400'"
            >
              <span
                class="inline-block h-5 w-5 rounded-full bg-white shadow transform transition"
                :class="isDark ? 'translate-x-5' : 'translate-x-1'"
              ></span>
            </button>
          </label>
        </div>
      </div>
    </aside>

    <main class="flex-1">
      <div class="max-w-7xl mx-auto px-4 lg:px-8 py-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="space-y-1">
            <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Protótipo</p>
            <div class="flex items-center gap-3">
              <h1 class="text-3xl font-bold text-white">Navigator lateral beta</h1>
              <span class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-200 border border-emerald-600/40">
                {!! $icons['stack'] !!} <span>V2</span>
              </span>
            </div>
            <p class="text-sm text-slate-400">Listagem de patrimônio com navegação fixa, sem o menu antigo.</p>
          </div>
          <div class="flex items-center gap-3">
            <div class="hidden sm:flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-lg px-4 py-2 text-sm text-slate-300 shadow">
              <span class="h-2 w-2 rounded-full bg-amber-400 animate-pulse"></span>
              <span>Modo beta</span>
            </div>
            <div class="rounded-xl bg-slate-900 border border-slate-800 px-4 py-2 text-right shadow-inner shadow-slate-950/60">
              <p class="text-xs text-slate-400">Registros</p>
              <p class="text-lg font-semibold text-white">{{ number_format($totalRegistros, 0, ',', '.') }}</p>
            </div>
          </div>
        </div>

        @if(!empty($hiddenColumns))
          <div class="flex flex-wrap items-center gap-2 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-300 shadow">
            <span class="text-indigo-300 font-semibold inline-flex items-center gap-1">{!! $icons['window'] !!} Colunas ocultas:</span>
            <span class="text-slate-200">{{ implode(', ', $hiddenColumns) }}</span>
            @if(!$showEmpty)
              <a href="{{ request()->fullUrlWithQuery(['show_empty_columns' => 1]) }}" class="text-indigo-300 hover:text-indigo-200 text-xs font-semibold underline">Exibir vazias</a>
            @endif
          </div>
        @endif

        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-2xl">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-lg font-semibold text-white">Filtros</h2>
              <p class="text-xs text-slate-400">Use filtros rápidos para localizar patrimônio.</p>
            </div>
            @if($activeFilters > 0)
              <div class="inline-flex items-center gap-2 text-xs bg-indigo-900/40 text-indigo-100 border border-indigo-800 px-3 py-1 rounded-full">
                <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                {{ $activeFilters }} filtro(s) ativo(s)
              </div>
            @endif
          </div>
          <form method="GET" action="{{ route('navigator.beta') }}" class="space-y-4">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
              <div class="lg:col-span-4">
                <label class="block text-xs text-slate-400 mb-1">Busca rápida</label>
                <div class="relative">
                  <input name="descricao" value="{{ request('descricao') }}" placeholder="Buscar por Nº PAT, nº série, marca..." class="w-full h-11 rounded-lg bg-slate-950/80 border border-slate-800 text-slate-100 placeholder:text-slate-500 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-600" />
                  <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">⌕</span>
                </div>
              </div>
              <div class="lg:col-span-2">
                <label class="block text-xs text-slate-400 mb-1">Situação</label>
                <select name="situacao" class="w-full h-11 rounded-lg bg-slate-950/80 border border-slate-800 text-slate-100 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-600">
                  <option value="">Todas</option>
                  <option value="EM USO" @selected(request('situacao')==='EM USO')>Em Uso</option>
                  <option value="DISPONIVEL" @selected(request('situacao')==='DISPONIVEL')>Disponível</option>
                  <option value="A DISPOSICAO" @selected(request('situacao')==='A DISPOSICAO')>Disponível</option>
                  <option value="CONSERTO" @selected(request('situacao')==='CONSERTO')>Conserto</option>
                  <option value="BAIXA" @selected(request('situacao')==='BAIXA')>Baixa</option>
                </select>
              </div>
              <div class="lg:col-span-2">
                <label class="block text-xs text-slate-400 mb-1">Projeto</label>
                <input name="cdprojeto" value="{{ request('cdprojeto') }}" placeholder="Cód. projeto" class="w-full h-11 rounded-lg bg-slate-950/80 border border-slate-800 text-slate-100 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-600" />
              </div>
              <div class="lg:col-span-2">
                <label class="block text-xs text-slate-400 mb-1">Local</label>
                <input name="cdlocal" value="{{ request('cdlocal') }}" placeholder="Cód. local" class="w-full h-11 rounded-lg bg-slate-950/80 border border-slate-800 text-slate-100 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-600" />
              </div>
              <div class="lg:col-span-2">
                <label class="block text-xs text-slate-400 mb-1">Itens/página</label>
                <select name="per_page" class="w-full h-11 rounded-lg bg-slate-950/80 border border-slate-800 text-slate-100 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-600">
                  @foreach([10,30,50,100] as $opt)
                    <option value="{{ $opt }}" @selected(request('per_page',10)==$opt)>{{ $opt }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-lg shadow-indigo-900/40">
                  <span class="text-base">⌕</span>
                  <span>Filtrar</span>
                </button>
                <a href="{{ route('navigator.beta') }}" class="text-sm text-slate-300 hover:text-white">Limpar</a>
              </div>
              <div class="flex items-center gap-2">
                <a href="{{ route('patrimonios.create') }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-3 py-2 rounded-lg shadow">
                  <span class="text-base">+</span>
                  <span>Adicionar Ativo</span>
                </a>
                <a href="{{ route('relatorios.patrimonios.exportar.excel') }}" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-slate-100 text-sm font-semibold px-3 py-2 rounded-lg border border-slate-700 shadow-inner">
                  <span class="text-base">⇩</span>
                  <span>Gerar Relatório</span>
                </a>
              </div>
            </div>
          </form>
        </div>

        <div class="bg-slate-950/70 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
          @include('patrimonios.partials.patrimonio-table')
          <div class="flex items-center justify-between px-4 py-3 text-xs text-slate-300 border-t border-slate-800">
            <div>
              @php
                $from = ($patrimonios->currentPage() - 1) * $patrimonios->perPage() + 1;
                $to = min($patrimonios->currentPage() * $patrimonios->perPage(), $patrimonios->total());
              @endphp
              Mostrando <span class="text-white font-semibold">{{ $from }}</span> a <span class="text-white font-semibold">{{ $to }}</span> de <span class="text-white font-semibold">{{ number_format($patrimonios->total(),0,',','.') }}</span> resultados
            </div>
            <div>
              {{ $patrimonios->appends(request()->query())->onEachSide(1)->links('pagination::tailwind') }}
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
</x-app-layout>

@push('scripts')
<script>
  function navigatorShell() {
    return {
      expanded: true,
      open: { controle: true },
      isDark: window.themeManager.isDark,
      toggleTheme() {
        window.themeManager.toggleTheme();
        this.isDark = window.themeManager.isDark;
      },
    };
  }
</script>
@endpush



