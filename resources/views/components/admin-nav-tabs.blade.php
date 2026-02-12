@php
  use App\Helpers\MenuHelper;

  $user = auth()->user();

  $tabs = collect([
    [
      'codigo' => '1002',
      'label' => 'Locais',
      'route' => 'projetos.index',
      'activePattern' => 'projetos.*',
    ],
    [
      'codigo' => '1003',
      'label' => 'Usuários',
      'route' => 'usuarios.index',
      'activePattern' => 'usuarios.*',
    ],
    [
      'codigo' => '1004',
      'label' => 'Telas',
      'route' => 'cadastro-tela.index',
      'activePattern' => 'cadastro-tela.*',
    ],
    [
      'codigo' => '1008',
      'label' => 'Tema',
      'route' => 'settings.theme',
      'activePattern' => 'settings.theme*',
    ],
  ])->filter(function ($tab) use ($user) {
    return $user
      && $user->temAcessoTela($tab['codigo'])
      && MenuHelper::rotaExiste($tab['route']);
  })->values();
@endphp

@if($tabs->isNotEmpty())
<div class="bg-surface-2 border-b border-app">
  <div class="w-full sm:px-6 lg:px-8">
    <div class="flex items-center gap-6 overflow-x-auto py-2 min-h-[48px]">
      @foreach($tabs as $tab)
        <a href="{{ route($tab['route']) }}" class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 {{ request()->routeIs($tab['activePattern']) ? 'accent-text accent-border' : 'text-muted border-transparent hover:text-[var(--text)]' }}">
          <svg class="w-4 h-4 mr-2 inline-block" viewBox="0 0 8 8" fill="currentColor" aria-hidden="true">
            <circle cx="4" cy="4" r="3" />
          </svg>
          {{ $tab['label'] }}
        </a>
      @endforeach
    </div>
  </div>
</div>
@endif
