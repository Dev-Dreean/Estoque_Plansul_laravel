<nav x-data="{ open: false }" class="bg-surface border-b border-base">
    <div class="w-full sm:px-6 lg:px-8">
        <div class="flex items-center h-16">
            <div class="flex flex-1 items-center">
                <div class="shrink-0 flex items-center" style="min-width: 120px; min-height: 36px;">
                    <a href="{{ route('dashboard') }}" class="block">
                        <x-application-logo class="block h-9 w-auto fill-current text-base-color" style="max-height: 36px;" />
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:flex" x-cloak>
                    @php
                    // Mapa simples: NUSEQTELA => URL
                    // Ordem customizada das telas:
                    // 1. 1000: Controle de Patrimônio
                    // 2. 1008: Gráficos
                    // 3. 1009: Cadastro de Locais
                    // 4. 1002: Cadastro de Usuário
                    // 5. 1006: Cadastro de Telas
                    $telaRotas = [
                        '1000' => route('patrimonios.index'),
                        '1008' => route('dashboard'),
                        '1009' => route('projetos.index'),
                        '1002' => route('usuarios.index'),
                        '1006' => route('cadastro-tela.index'),
                    ];
                    
                    // Rotas para verificação de ativo
                    $rotasAtivas = [
                        '1000' => 'patrimonios.*',
                        '1008' => 'dashboard',
                        '1009' => 'projetos.*',
                        '1002' => 'usuarios.*',
                        '1006' => 'cadastro-tela.*',
                    ];
                    
                    // Ordem customizada das telas (mantém ordem preferida, mas não limita quais aparecem)
                    $telaOrder = ['1000', '1008', '1009', '1002', '1006', '1003', '1004', '1005', '1007'];

                    // Busca todas as telas ativas no BD (não limitamos apenas às da ordem, para incluir telas novas)
                    $todasAsTelas = \Illuminate\Support\Facades\DB::table('acessotela')
                        ->where('FLACESSO', 'S')
                        ->get()
                        ->sortBy(function($tela) use ($telaOrder) {
                            $idx = array_search((string)$tela->NUSEQTELA, $telaOrder);
                            // Telas sem posição definida ficam no final (ordenadas por código)
                            return $idx === false ? (10000 + (int)$tela->NUSEQTELA) : $idx;
                        });
                    @endphp

                    @foreach($todasAsTelas as $tela)
                        @if(Auth::user()->temAcessoTela($tela->NUSEQTELA) && isset($telaRotas[$tela->NUSEQTELA]))
                            <x-nav-link :href="$telaRotas[$tela->NUSEQTELA]" :active="request()->routeIs($rotasAtivas[$tela->NUSEQTELA])">
                                {{ $tela->DETELA }}
                            </x-nav-link>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center ml-auto">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-soft bg-surface hover:text-base-color focus:outline-none transition ease-in-out duration-150">
                            @php
                            $nomeCompleto = Auth::user()->NOMEUSER ?? Auth::user()->name;
                            $partes = explode(' ', trim($nomeCompleto));
                            $nomeExibicao = $partes[0] . (count($partes) > 1 ? ' ' . end($partes) : '');
                            @endphp
                            <div>{{ $nomeExibicao }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @if(Auth::user()->PERFIL === 'ADM')
                        <x-dropdown-link :href="route('settings.theme')">
                            {{ __('Temas') }}
                        </x-dropdown-link>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- botão mobile --}}
            <div class="-me-2 flex items-center sm:hidden ml-auto">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-soft hover:text-base-color hover:bg-surface-alt focus:outline-none focus:bg-surface-alt transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- menu mobile --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-surface border-t border-base">
        <div class="pt-2 pb-3 space-y-1">
            @php
            // Mapa simples: NUSEQTELA => URL
            // Ordem customizada das telas:
            // 1. 1000: Controle de Patrimônio
            // 2. 1008: Gráficos
            // 3. 1009: Cadastro de Locais
            // 4. 1002: Cadastro de Usuário
            // 5. 1006: Cadastro de Telas
            $telaRotas = [
                '1000' => route('patrimonios.index'),
                '1008' => route('dashboard'),
                '1009' => route('projetos.index'),
                '1002' => route('usuarios.index'),
                '1006' => route('cadastro-tela.index'),
            ];
            
            // Rotas para verificação de ativo
            $rotasAtivas = [
                '1000' => 'patrimonios.*',
                '1008' => 'dashboard',
                '1009' => 'projetos.*',
                '1002' => 'usuarios.*',
                '1006' => 'cadastro-tela.*',
            ];
            
            // Ordem customizada das telas
            $telaOrder = ['1000', '1008', '1009', '1002', '1006'];
            
            // Busca todas as telas disponíveis e ordena
            $todasAsTelas = \Illuminate\Support\Facades\DB::table('acessotela')
                ->where('FLACESSO', 'S')
                ->whereIn('NUSEQTELA', $telaOrder)
                ->get()
                ->sortBy(function($tela) use ($telaOrder) {
                    return array_search($tela->NUSEQTELA, $telaOrder);
                });
            @endphp

            @foreach($todasAsTelas as $tela)
                @if(Auth::user()->temAcessoTela($tela->NUSEQTELA) && isset($telaRotas[$tela->NUSEQTELA]))
                    <x-responsive-nav-link :href="$telaRotas[$tela->NUSEQTELA]" :active="request()->routeIs($rotasAtivas[$tela->NUSEQTELA])">
                        {{ $tela->DETELA }}
                    </x-responsive-nav-link>
                @endif
            @endforeach
        </div>

        <div class="pt-4 pb-1 border-t border-base">
            <div class="px-4">
                <div class="font-medium text-base text-base-color">{{ Auth::user()->NOMEUSER ?? Auth::user()->name }}</div>
                <div class="font-medium text-sm text-soft">{{ Auth::user()->NMLOGIN ?? Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                @if(Auth::user()->PERFIL === 'ADM')
                <x-responsive-nav-link :href="route('settings.theme')">
                    {{ __('Temas') }}
                </x-responsive-nav-link>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>