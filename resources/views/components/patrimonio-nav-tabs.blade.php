{{-- 
  Componente para exibir abas de navegação dentro de "Controle de Patrimônio"
  Design profissional, minimalista e achatado
--}}

@php
  $userPerfil = auth()->user()?->PERFIL ?? null;
  $isConsultor = $userPerfil === \App\Models\User::PERFIL_CONSULTOR;

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
      'active' => request()->routeIs('patrimonios.atribuir') || request()->routeIs('patrimonios.atribuir.*'),
    ],
    [
      'label' => 'Relatório de Bens',
      'route' => 'relatorios.bens.index',
      'active' => request()->routeIs('relatorios.bens.*'),
    ],
  ];

  if ($isConsultor) {
    $patrimonioTabs = array_values(array_filter($patrimonioTabs, function ($tab) {
      return $tab['route'] === 'patrimonios.index';
    }));
  }
@endphp

<div class="bg-surface-2 border-b border-app">
  <div class="w-full sm:px-6 lg:px-8">
    <div class="flex items-center gap-6 overflow-x-hidden py-2 min-h-[48px]">
      @foreach($patrimonioTabs as $tab)
        @if($tab['route'] === 'patrimonios.index')
          {{-- Aba Patrimônios com submenu tipo "nuvem" contendo o CTA Cadastrar --}}
          <div class="mx-1" x-data="{open:false, x:0, y:0, toggle(e){ if(window.matchMedia('(hover: none)').matches){ this.open = !this.open; } }, show(e){ this.open = true; this.$nextTick(()=>{ const r = this.$refs.anchor.getBoundingClientRect(); this.x = Math.max(8, r.left + window.scrollX); this.y = Math.max(8, r.bottom + window.scrollY + 6); }); }, hide(){ this.open = false; }}"
               @mouseenter="show" @mouseleave="hide" @focusin="show" @focusout="hide">
              <a href="{{ route('patrimonios.index') }}" x-ref="anchor" @click="if(window.matchMedia('(hover: none)').matches){ $event.preventDefault(); toggle($event); }" class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 {{ $tab['active'] ? 'accent-text accent-border' : 'text-muted border-transparent hover:text-[var(--text)]' }}" tabindex="0">
              {{-- Ícone Patrimônios (herda cor com currentColor) --}}
              <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
              </svg>
              {{ $tab['label'] }}
            </a>

              @if(!$isConsultor)
                {{-- Caixa flutuante (nuvem) fixa na viewport; visível quando open=true --}}
                <div x-show="open" x-cloak x-transition.opacity x-transition.duration.150 class="fixed z-50" :style="`left: ${x}px; top: ${y}px;`">
                  <div class="bg-surface border border-app rounded-2xl shadow-2xl px-1 py-1 w-36">
                    <div class="flex flex-col gap-1 px-2 py-1">
                      @if(request()->routeIs('patrimonios.index'))
                        <a href="#" @click.prevent="window.dispatchEvent(new CustomEvent('patrimonio-modal-create'))" class="flex items-center gap-2 px-3 py-2 text-sm text-app rounded hover:bg-[var(--surface-2)] transition">
                          <svg class="w-4 h-4 accent-text" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                          </svg>
                          <span>Cadastrar</span>
                        </a>
                      @else
                        <a href="{{ route('patrimonios.create') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-app rounded hover:bg-[var(--surface-2)] transition">
                          <svg class="w-4 h-4 accent-text" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                          </svg>
                          <span>Cadastrar</span>
                        </a>
                      @endif
                    </div>
                  </div>
                </div>
              @endif
          </div>
        @elseif($tab['route'] === 'patrimonios.atribuir')
          {{-- Aba especial: submenu com posicionamento dinâmico (fixed na index, relativo em outras páginas) --}}
          <div class="mx-1" x-data="{open:false, x:0, y:0, toggle(e){ if(window.matchMedia('(hover: none)').matches){ this.open = !this.open; } }, show(e){ this.open = true; this.$nextTick(()=>{ const r = this.$refs.anchor.getBoundingClientRect(); this.x = Math.max(8, r.left + window.scrollX); this.y = Math.max(8, r.bottom + window.scrollY + 6); }); }, hide(){ this.open = false; }}"
               @mouseenter="show" @mouseleave="hide" @focusin="show" @focusout="hide">
            <a href="{{ route('patrimonios.atribuir.codigos', ['status' => 'disponivel']) }}" x-ref="anchor" @click="if(window.matchMedia('(hover: none)').matches){ $event.preventDefault(); toggle($event); }" class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 {{ $tab['active'] ? 'text-blue-900 dark:text-blue-400 border-blue-900 dark:border-blue-400' : 'text-muted border-transparent hover:text-blue-900 dark:hover:text-blue-400' }}" tabindex="0">
              {{-- Ícone Atribuir (herda cor com currentColor) --}}
              <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7l10 10M21 11L13 3 3 13l8 8 10-10z"></path>
              </svg>
              {{ $tab['label'] }}
            </a>

            {{-- Submenu com posicionamento inteligente --}}
            <div x-show="open" x-cloak x-transition.opacity x-transition.duration.150 @if(request()->routeIs('patrimonios.index')) class="fixed z-50" :style="`left: ${x}px; top: ${y}px;`" @else class="absolute z-50 mt-0 left-0 top-full" @endif>
              <div class="bg-surface border border-app rounded-2xl shadow-2xl px-2 py-2 w-48">
                <div class="flex flex-col gap-1">
                   <a href="{{ route('patrimonios.atribuir.codigos', ['status' => 'disponivel']) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-app rounded hover:bg-[var(--surface-2)] transition">
                     <svg class="w-4 h-4" style="color: var(--ok)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                     </svg>
                     <span>Disponíveis</span>
                   </a>
                   <a href="{{ route('patrimonios.atribuir.codigos', ['status' => 'indisponivel']) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-app rounded hover:bg-[var(--surface-2)] transition">
                     <svg class="w-4 h-4" style="color: var(--danger)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                     </svg>
                     <span>Atribuídos</span>
                   </a>
                </div>
              </div>
            </div>
          </div>
        @elseif($tab['route'] === 'relatorios.bens.index')
          {{-- Aba Relatório de Bens com submenu inteligente --}}
          <div class="mx-1" x-data="{open:false, x:0, y:0, toggle(e){ if(window.matchMedia('(hover: none)').matches){ this.open = !this.open; } }, show(e){ this.open = true; this.$nextTick(()=>{ const r = this.$refs.anchor.getBoundingClientRect(); this.x = Math.max(8, r.left + window.scrollX); this.y = Math.max(8, r.bottom + window.scrollY + 6); }); }, hide(){ this.open = false; }}" @mouseenter="show" @mouseleave="hide" @focusin="show" @focusout="hide">
            <a href="{{ route('relatorios.bens.index') }}" x-ref="anchor" @click="if(window.matchMedia('(hover: none)').matches){ $event.preventDefault(); toggle($event); }" class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 {{ $tab['active'] ? 'accent-text accent-border' : 'text-muted border-transparent hover:text-[var(--text)]' }}" tabindex="0">
              {{-- Ícone Relatório (herda cor com currentColor) --}}
              <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h10M7 16h10M5 6h14v12H5z"></path>
              </svg>
              {{ $tab['label'] }}
            </a>

            {{-- Submenu com posicionamento inteligente --}}
            <div x-show="open" x-cloak x-transition.opacity x-transition.duration.150 @if(request()->routeIs('patrimonios.index')) class="fixed z-50" :style="`left: ${x}px; top: ${y}px;`" @else class="absolute z-50 mt-0 left-0 top-full" @endif>
              <div class="bg-surface border border-app rounded-2xl shadow-2xl px-2 py-2 w-48">
                <div class="flex flex-col gap-1">
                  <a href="{{ route('relatorios.bens.index', ['open' => 'cadBem']) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-app rounded hover:bg-[var(--surface-2)] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 accent-text" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
          <a href="{{ route($tab['route']) }}" class="px-4 py-2 mx-1 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 {{ $tab['active'] ? 'accent-text accent-border' : 'text-muted border-transparent hover:text-[var(--text)]' }}">
            @if($tab['route'] === 'patrimonios.index')
              <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
              </svg>
            @elseif($tab['route'] === 'patrimonios.create')
              <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16M4 12h16"></path>
              </svg>
            @elseif($tab['route'] === 'relatorios.bens.index')
              <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10v12H7z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2"></path>
              </svg>
            @elseif($tab['route'] === 'removidos.index')
              <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m5 0H4"></path>
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
