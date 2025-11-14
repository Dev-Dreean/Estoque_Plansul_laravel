{{-- 
  Componente para exibir abas de navegação dentro de "Controle de Patrimônio"
  Design profissional, minimalista e achatado
--}}

@php
  // Define os itens de navegação
  $patrimonioTabs = [
    [
      'label' => 'Patrimônios',
      'route' => 'patrimonios.index',
      'active' => request()->routeIs('patrimonios.index'),
    ],
    [
      'label' => 'Atribuir Cód. Termo',
      'route' => 'patrimonios.atribuir',
      // Marca ativo quando estiver na rota principal ou em qualquer sub-rota (ex: patrimonios.atribuir.codigos)
      'active' => request()->routeIs('patrimonios.atribuir') || request()->routeIs('patrimonios.atribuir.*'),
    ],
    [
      'label' => 'Histórico',
      'route' => 'historico.index',
      'active' => request()->routeIs('historico.*'),
    ],
    [
      'label' => 'Relatório de Bens',
      'route' => 'relatorios.bens.index',
      'active' => request()->routeIs('relatorios.bens.*'),
    ],
  ];
@endphp

<div class="bg-gray-100 dark:bg-gray-800/30 border-b border-gray-300 dark:border-gray-600">
  <div class="w-full sm:px-6 lg:px-8">
    <div class="flex items-center gap-16 overflow-x-auto">
      @foreach($patrimonioTabs as $tab)
        @if($tab['route'] === 'patrimonios.index')
          {{-- Aba Patrimônios com submenu tipo "nuvem" contendo o CTA Cadastrar --}}
          <div class="mx-2" x-data="{open:false, x:0, y:0, toggle(e){ if(window.matchMedia('(hover: none)').matches){ this.open = !this.open; } }, show(e){ this.open = true; this.$nextTick(()=>{ const r = this.$refs.anchor.getBoundingClientRect(); this.x = Math.max(8, r.left + window.scrollX); this.y = Math.max(8, r.bottom + window.scrollY + 6); }); }, hide(){ this.open = false; }}"
               @mouseenter="show" @mouseleave="hide" @focusin="show" @focusout="hide">
              <a href="{{ route('patrimonios.index') }}" x-ref="anchor" @click="if(window.matchMedia('(hover: none)').matches){ $event.preventDefault(); toggle($event); }" class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 {{ $tab['active'] ? 'text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400' : 'text-gray-500 dark:text-gray-300 border-transparent hover:text-gray-700 dark:hover:text-gray-200' }}" tabindex="0">
              {{-- Ícone Patrimônios (herda cor com currentColor) --}}
              <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
              </svg>
              {{ $tab['label'] }}
            </a>

            {{-- Caixa flutuante (nuvem) fixa na viewport; visível quando open=true --}}
            <div x-show="open" x-cloak x-transition.opacity x-transition.duration.150 class="fixed z-50" :style="`left: ${x}px; top: ${y}px;`">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl px-1 py-1 w-36">
                  <div class="flex flex-col gap-1 px-2 py-1">
                    <a href="{{ route('patrimonios.create') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                      <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                      </svg>
                      <span>Cadastrar</span>
                    </a>
                  </div>
              </div>
            </div>
          </div>
        @elseif($tab['route'] === 'patrimonios.atribuir')
          {{-- Aba especial: submenu flutuante tipo "nuvem" posicionado fixed para evitar clipping por overflow --}}
          <div class="mx-2" x-data="{open:false, x:0, y:0, toggle(e){ if(window.matchMedia('(hover: none)').matches){ this.open = !this.open; } }, show(e){ this.open = true; this.$nextTick(()=>{ const r = this.$refs.anchor.getBoundingClientRect(); this.x = Math.max(8, r.left + window.scrollX); this.y = Math.max(8, r.bottom + window.scrollY + 6); }); }, hide(){ this.open = false; }}"
               @mouseenter="show" @mouseleave="hide" @focusin="show" @focusout="hide">
            <a href="{{ route('patrimonios.atribuir.codigos', ['status' => 'disponivel']) }}" x-ref="anchor" @click="if(window.matchMedia('(hover: none)').matches){ $event.preventDefault(); toggle($event); }" class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 {{ $tab['active'] ? 'text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400' : 'text-gray-500 dark:text-gray-300 border-transparent hover:text-gray-700 dark:hover:text-gray-200' }}" tabindex="0">
              {{-- Ícone Atribuir (herda cor com currentColor) --}}
              <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7l10 10M21 11L13 3 3 13l8 8 10-10z"></path>
              </svg>
              {{ $tab['label'] }}
            </a>

            {{-- Caixa flutuante (nuvem) fixa na viewport; visível quando open=true --}}
            <div x-show="open" x-cloak x-transition.opacity x-transition.duration.150 class="fixed z-50" :style="`left: ${x}px; top: ${y}px;`">
              <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl px-2 py-2 w-48">
                <div class="flex flex-col gap-1">
                   <a href="{{ route('patrimonios.atribuir.codigos', ['status' => 'disponivel']) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                     <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                     </svg>
                     <span>Disponíveis</span>
                   </a>
                   <a href="{{ route('patrimonios.atribuir.codigos', ['status' => 'indisponivel']) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                     <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                     </svg>
                     <span>Atribuídos</span>
                   </a>
                </div>
              </div>
            </div>
          </div>
        @elseif($tab['route'] === 'relatorios.bens.index')
          {{-- Aba Relatório de Bens com nuvem (Cadastrar Bem) --}}
          <div class="mx-2" x-data="{open:false, x:0, y:0, toggle(e){ if(window.matchMedia('(hover: none)').matches){ this.open = !this.open; } }, show(e){ this.open = true; this.$nextTick(()=>{ const r = this.$refs.anchor.getBoundingClientRect(); this.x = Math.max(8, r.left); this.y = Math.max(8, r.bottom + 6); }); }, hide(){ this.open = false; }}" @mouseenter="show" @mouseleave="hide" @focusin="show" @focusout="hide">
            <a href="{{ route('relatorios.bens.index') }}" x-ref="anchor" @click="if(window.matchMedia('(hover: none)').matches){ $event.preventDefault(); toggle($event); }" class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 {{ $tab['active'] ? 'text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400' : 'text-gray-500 dark:text-gray-300 border-transparent hover:text-gray-700 dark:hover:text-gray-200' }}" tabindex="0">
              {{-- Ícone Relatório (herda cor com currentColor) --}}
              <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h10M7 16h10M5 6h14v12H5z"></path>
              </svg>
              {{ $tab['label'] }}
            </a>

            <div x-show="open" x-cloak x-transition.opacity x-transition.duration.150 class="fixed z-50" :style="`left: ${x}px; top: ${y}px;`">
              <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl px-2 py-2 w-48">
                <div class="flex flex-col gap-1">
                  <a href="{{ route('relatorios.bens.index', ['open' => 'cadBem']) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-600 dark:text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m4-4H8" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a10 10 0 100 20 10 10 0 000-20z" />
                    </svg>
                    <span>Cadastrar Bem</span>
                  </a>
                </div>
              </div>
            </div>
          </div>
        @else
          <a href="{{ route($tab['route']) }}" class="px-4 py-2 mx-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 {{ $tab['active'] ? 'text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400' : 'text-gray-500 dark:text-gray-300 border-transparent hover:text-gray-700 dark:hover:text-gray-200' }}">
            @if($tab['route'] === 'patrimonios.index')
              <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
              </svg>
            @elseif($tab['route'] === 'patrimonios.create')
              <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16M4 12h16"></path>
              </svg>
            @elseif($tab['route'] === 'historico.index')
              <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3M12 20a8 8 0 100-16 8 8 0 000 16z"></path>
              </svg>
            @elseif($tab['route'] === 'relatorios.bens.index')
              <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10v12H7z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2"></path>
              </svg>
            @else
              {{-- default icon (small circle) --}}
              <svg class="w-3 h-3 mr-2 inline-block" viewBox="0 0 8 8" fill="currentColor" aria-hidden="true"><circle cx="4" cy="4" r="3"/></svg>
            @endif
            {{ $tab['label'] }}
          </a>
        @endif
      @endforeach
    </div>
  </div>
</div>
