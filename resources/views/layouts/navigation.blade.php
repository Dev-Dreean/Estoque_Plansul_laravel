<nav x-data="{ open: false }" class="bg-surface border-b border-base">
    <div class="w-full sm:px-6 lg:px-8">
        <div class="flex items-center h-16">
            <div class="flex flex-1 items-center">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-base-color" />
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @if(Auth::user()->temAcessoTela(1000))
                    <x-nav-link :href="route('patrimonios.index')" :active="request()->routeIs('patrimonios.*')">
                        {{ ('Controle de Patrimônio') }}
                    </x-nav-link>
                    @endif

                    @if(Auth::user()->temAcessoTela(1001))
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Gráficos') }}
                    </x-nav-link>
                    @endif

                    @if(Auth::user()->temAcessoTela(1002))
                    <x-nav-link :href="route('projetos.index')" :active="request()->routeIs('projetos.*')">
                        {{ __('Cadastro de Locais') }}
                    </x-nav-link>
                    @endif

                    @if(Auth::user()->temAcessoTela(1003))
                    <x-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')">
                        {{ __('Usuários') }}
                    </x-nav-link>
                    @endif

                    @if(Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                    <x-nav-link :href="route('cadastro-tela.index')" :active="request()->routeIs('cadastro-tela.*')">
                        {{ __('Cadastro de Telas') }}
                    </x-nav-link>
                    @endif
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
            @if(Auth::user()->temAcessoTela(1000))
            <x-responsive-nav-link :href="route('patrimonios.index')" :active="request()->routeIs('patrimonios.*')">
                {{ ('Controle de Patrimônio') }}
            </x-responsive-nav-link>
            @endif

            @if(Auth::user()->temAcessoTela(1001))
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Gráficos') }}
            </x-responsive-nav-link>
            @endif

            @if(Auth::user()->temAcessoTela(1002))
            <x-responsive-nav-link :href="route('projetos.index')" :active="request()->routeIs('projetos.*')">
                {{ __('Cadastro de Locais') }}
            </x-responsive-nav-link>
            @endif

            @if(Auth::user()->temAcessoTela(1003))
            <x-responsive-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')">
                {{ __('Usuários') }}
            </x-responsive-nav-link>
            @endif

            @if(Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
            <x-responsive-nav-link :href="route('cadastro-tela.index')" :active="request()->routeIs('cadastro-tela.*')">
                {{ __('Cadastro de Telas') }}
            </x-responsive-nav-link>
            @endif
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