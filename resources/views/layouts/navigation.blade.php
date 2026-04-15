<nav
    x-data="{ open: false, ...themeToggle(), ...createImportantNotificationsState({ endpoint: '{{ route('api.notificacoes.importantes') }}', items: @js($importantNotificationsPayload['items'] ?? []), totalCount: @js($importantNotificationsPayload['total_count'] ?? 0) }) }"
    x-init="initImportantNotifications()"
    class="bg-surface border-b border-base"
>
    @php
        use App\Helpers\MenuHelper;

        $telasMenu = MenuHelper::getTelasParaMenu();

        $telasNav = collect($telasMenu)
            ->map(function ($tela, $codigo) {
                $routeName = $tela['route'] ?? null;
                $activePattern = $routeName
                    ? (str_contains($routeName, '.index') ? str_replace('.index', '.*', $routeName) : $routeName)
                    : null;

                return [
                    'codigo' => (string) $codigo,
                    'route' => $routeName,
                    'activePattern' => $activePattern,
                    'nome' => $tela['nome'] ?? 'Tela ' . $codigo,
                    'ordem' => $tela['ordem'] ?? 999,
                ];
            })
            ->filter(fn ($tela) => $tela['route'] && MenuHelper::rotaExiste($tela['route']))
            ->sortBy('ordem')
            ->values();

        $adminRoutes = ['projetos.index', 'usuarios.index', 'cadastro-tela.index'];
        $painelAdmLinks = $telasNav
            ->filter(fn ($tela) => in_array($tela['route'], $adminRoutes, true))
            ->values();

        $telasNav = $telasNav
            ->reject(fn ($tela) => in_array($tela['route'], $adminRoutes, true))
            ->values();

        $painelAdmChildren = $painelAdmLinks
            ->map(fn ($tela) => [
                'route' => $tela['route'],
                'nome' => $tela['nome'],
                'activePattern' => $tela['activePattern'],
            ])
            ->values()
            ->all();

        $isPainelAdmActive = collect($painelAdmChildren)
            ->contains(fn ($child) => request()->routeIs($child['activePattern'] ?? $child['route']));

        $showPainelAdm = (auth()->user()?->PERFIL === 'ADM') && !empty($painelAdmChildren);

        $nomeCompleto = Auth::user()->NOMEUSER ?? Auth::user()->name;
        $partes = explode(' ', trim($nomeCompleto));
        $nomeExibicao = $partes[0] . (count($partes) > 1 ? ' ' . end($partes) : '');
    @endphp

    <div class="w-full sm:px-6 lg:px-8">
        <div class="flex items-center h-16">
            <div class="flex flex-1 items-center">
                <div class="shrink-0 flex items-center" style="min-width: 120px; min-height: 36px;">
                    <a href="{{ route('dashboard') }}" class="block">
                        <x-application-logo class="block h-9 w-auto fill-current text-base-color" style="max-height: 36px;" />
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:flex" x-cloak>
                    @foreach($telasNav as $tela)
                        <x-nav-link :href="route($tela['route'])" :active="request()->routeIs($tela['activePattern'])">
                            <span>{{ $tela['nome'] }}</span>
                        </x-nav-link>
                    @endforeach
                </div>

                <div class="hidden sm:flex sm:items-center ml-auto gap-3">
                    @if($showPainelAdm)
                    <div class="relative inline-flex mr-1" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                        <button type="button" @click="open = !open" class="{{ $isPainelAdmActive ? 'inline-flex items-center px-1 pt-1 border-b-2 border-indigo-400 dark:border-indigo-600 text-sm font-medium leading-5 text-gray-900 dark:text-gray-100' : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700' }}">
                            <span>Painel Adm</span>
                            <svg class="ms-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 top-full mt-1 z-50 min-w-[210px] rounded-lg border border-app bg-surface shadow-lg py-1">
                            @foreach($painelAdmChildren as $child)
                                <a href="{{ route($child['route']) }}" class="{{ request()->routeIs($child['activePattern']) ? 'bg-[var(--surface-2)] text-[var(--text)] font-semibold' : 'text-soft hover:bg-[var(--surface-2)] hover:text-[var(--text)]' }} block px-3 py-2 text-sm whitespace-nowrap">
                                    {{ $child['nome'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="relative inline-flex" @click.outside="notificationsOpen = false">
                        <button
                            type="button"
                            @click="toggleImportantNotifications()"
                            class="important-bell-button"
                            :class="{ 'important-bell-button--spotlight': notificationsSpotlightDesktop }"
                            x-ref="importantBellDesktop"
                            :aria-expanded="notificationsOpen.toString()"
                            title="Pendências importantes"
                        >
                            <span class="sr-only">Abrir pendências importantes</span>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.857 17H9.143m9.714 0H20l-1.286-1.286A2 2 0 0118.143 14.3V11a6.143 6.143 0 10-12.286 0v3.3a2 2 0 01-.571 1.414L4 17h1.143m13.714 0a2.857 2.857 0 11-5.714 0m5.714 0H8.857m0 0a2.857 2.857 0 005.714 0" />
                            </svg>
                            <span
                                x-show="importantNotificationsCount > 0"
                                x-cloak
                                class="important-bell-count"
                                x-text="formatImportantNotificationsCount()"
                            ></span>
                        </button>

                        <div x-show="notificationsOpen" x-cloak x-transition.opacity class="important-notifications-dropdown">
                            <div class="important-notifications-panel">
                                <div class="important-notifications-panel__header">
                                    <div>
                                        <div class="important-notifications-panel__title">Pendências importantes</div>
                                        <div class="important-notifications-panel__subtitle" x-text="importantNotificationsCount > 0 ? `Total: ${importantNotificationsCount}` : 'Nenhuma pendência no momento'"></div>
                                    </div>
                                    <button type="button" @click="refreshImportantNotifications()" class="important-notifications-refresh" :disabled="notificationsLoading">
                                        Atualizar
                                    </button>
                                </div>

                                <div class="important-notifications-panel__body">
                                    <template x-if="notificationsLoading && importantNotifications.length === 0">
                                        <div class="important-notifications-empty">Atualizando pendências...</div>
                                    </template>

                                    <template x-if="!notificationsLoading && importantNotifications.length === 0">
                                        <div class="important-notifications-empty">Nenhuma pendência importante para você agora.</div>
                                    </template>

                                    <template x-for="item in importantNotifications" :key="item.item_key">
                                        <button
                                            type="button"
                                            class="important-notification-item"
                                            :class="item.tone_class || importantNotificationToneClass(item)"
                                            @click="handleImportantNotificationSelect(item.url)"
                                        >
                                            <div class="important-notification-item__module" x-text="item.modulo"></div>
                                            <div class="important-notification-item__title" x-text="item.titulo"></div>
                                            <div class="important-notification-item__description" x-text="item.descricao"></div>
                                            <div class="important-notification-item__meta">
                                                <span class="important-notification-item__action" x-text="item.acao_label"></span>
                                                <span x-text="item.occurred_at_label || ''"></span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-base/70 text-sm leading-4 font-medium rounded-full text-soft bg-surface hover:text-base-color focus:outline-none transition ease-in-out duration-150 shadow-sm">
                                <div>{{ $nomeExibicao }}</div>
                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-3 border-b border-base/70">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-medium text-base-color">Tema</div>
                                        <div class="text-xs text-soft" x-text="isDark ? 'Escuro' : 'Claro'"></div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="toggleTheme"
                                        :aria-pressed="isDark.toString()"
                                        :title="isDark ? 'Ativar tema claro' : 'Ativar tema escuro'"
                                        class="inline-flex items-center border border-base focus:outline-none transition ease-in-out duration-200"
                                        :style="isDark
                                            ? 'height:32px;width:56px;padding:4px;border-radius:999px;background:linear-gradient(135deg,#0f172a,#334155);box-shadow:inset 0 1px 0 rgba(255,255,255,.08),0 6px 14px rgba(15,23,42,.18);'
                                            : 'height:32px;width:56px;padding:4px;border-radius:999px;background:linear-gradient(135deg,#f8fafc,#e2e8f0);box-shadow:inset 0 1px 0 rgba(255,255,255,.92),0 6px 14px rgba(148,163,184,.20);'"
                                    >
                                        <span class="sr-only">Alternar entre tema claro e escuro</span>
                                        <span
                                            aria-hidden="true"
                                            class="inline-flex items-center justify-center transition duration-200 ease-in-out"
                                            :style="isDark
                                                ? 'width:24px;height:24px;border-radius:999px;transform:translateX(24px);background:linear-gradient(135deg,#1e293b,#0f172a);color:#e2e8f0;box-shadow:0 6px 14px rgba(15,23,42,.30);'
                                                : 'width:24px;height:24px;border-radius:999px;transform:translateX(0);background:linear-gradient(135deg,#ffffff,#f8fafc);color:#f59e0b;box-shadow:0 6px 14px rgba(148,163,184,.24);'"
                                        >
                                            <svg x-show="!isDark" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                                <circle cx="12" cy="12" r="4" stroke-width="2"></circle>
                                                <path stroke-linecap="round" stroke-width="2" d="M12 2v2.5M12 19.5V22M4.93 4.93l1.77 1.77M17.3 17.3l1.77 1.77M2 12h2.5M19.5 12H22M4.93 19.07l1.77-1.77M17.3 6.7l1.77-1.77"></path>
                                            </svg>
                                            <svg x-show="isDark" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M20.354 15.354A9 9 0 0 1 8.646 3.646a9 9 0 1 0 11.708 11.708Z"></path>
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Sair') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                <div class="-me-2 flex items-center sm:hidden ml-auto gap-2">
                    <button
                        type="button"
                        @click="toggleImportantNotifications()"
                        class="relative inline-flex items-center justify-center p-2 rounded-md text-soft hover:text-base-color hover:bg-surface-alt focus:outline-none focus:bg-surface-alt transition duration-150 ease-in-out"
                        :class="{ 'important-bell-button--spotlight': notificationsSpotlightMobile }"
                        x-ref="importantBellMobile"
                        :aria-expanded="mobileNotificationsOpen.toString()"
                    >
                        <span class="sr-only">Abrir pendências importantes</span>
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.857 17H9.143m9.714 0H20l-1.286-1.286A2 2 0 0118.143 14.3V11a6.143 6.143 0 10-12.286 0v3.3a2 2 0 01-.571 1.414L4 17h1.143m13.714 0a2.857 2.857 0 11-5.714 0m5.714 0H8.857m0 0a2.857 2.857 0 005.714 0" />
                        </svg>
                        <span
                            x-show="importantNotificationsCount > 0"
                            x-cloak
                            class="important-bell-count important-bell-count--mobile"
                            x-text="formatImportantNotificationsCount()"
                        ></span>
                    </button>

                    <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-soft hover:text-base-color hover:bg-surface-alt focus:outline-none focus:bg-surface-alt transition duration-150 ease-in-out">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-surface border-t border-base">
        <div class="pt-2 pb-3 space-y-1">
            @foreach($telasNav as $tela)
                <x-responsive-nav-link :href="route($tela['route'])" :active="request()->routeIs($tela['activePattern'])">
                    <span>{{ $tela['nome'] }}</span>
                </x-responsive-nav-link>
            @endforeach
        </div>

        <div class="pt-4 pb-1 border-t border-base">
            <div class="px-4">
                <div class="font-medium text-base text-base-color">{{ Auth::user()->NOMEUSER ?? Auth::user()->name }}</div>
                <div class="font-medium text-sm text-soft">{{ Auth::user()->NMLOGIN ?? Auth::user()->email }}</div>
                <div class="mt-3 flex items-center justify-between gap-3 rounded-xl border border-base/70 bg-surface px-3 py-2">
                    <div>
                        <div class="text-sm font-medium text-base-color">Tema</div>
                        <div class="text-xs text-soft" x-text="isDark ? 'Escuro' : 'Claro'"></div>
                    </div>
                    <button
                        type="button"
                        @click="toggleTheme"
                        :aria-pressed="isDark.toString()"
                        :title="isDark ? 'Ativar tema claro' : 'Ativar tema escuro'"
                        class="inline-flex items-center border border-base focus:outline-none transition ease-in-out duration-200"
                        :style="isDark
                            ? 'height:32px;width:56px;padding:4px;border-radius:999px;background:linear-gradient(135deg,#0f172a,#334155);box-shadow:inset 0 1px 0 rgba(255,255,255,.08),0 6px 14px rgba(15,23,42,.18);'
                            : 'height:32px;width:56px;padding:4px;border-radius:999px;background:linear-gradient(135deg,#f8fafc,#e2e8f0);box-shadow:inset 0 1px 0 rgba(255,255,255,.92),0 6px 14px rgba(148,163,184,.20);'"
                    >
                        <span class="sr-only">Alternar entre tema claro e escuro</span>
                        <span
                            aria-hidden="true"
                            class="inline-flex items-center justify-center transition duration-200 ease-in-out"
                            :style="isDark
                                ? 'width:24px;height:24px;border-radius:999px;transform:translateX(24px);background:linear-gradient(135deg,#1e293b,#0f172a);color:#e2e8f0;box-shadow:0 6px 14px rgba(15,23,42,.30);'
                                : 'width:24px;height:24px;border-radius:999px;transform:translateX(0);background:linear-gradient(135deg,#ffffff,#f8fafc);color:#f59e0b;box-shadow:0 6px 14px rgba(148,163,184,.24);'"
                        >
                            <svg x-show="!isDark" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <circle cx="12" cy="12" r="4" stroke-width="2"></circle>
                                <path stroke-linecap="round" stroke-width="2" d="M12 2v2.5M12 19.5V22M4.93 4.93l1.77 1.77M17.3 17.3l1.77 1.77M2 12h2.5M19.5 12H22M4.93 19.07l1.77-1.77M17.3 6.7l1.77-1.77"></path>
                            </svg>
                            <svg x-show="isDark" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M20.354 15.354A9 9 0 0 1 8.646 3.646a9 9 0 1 0 11.708 11.708Z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                @if($showPainelAdm)
                <div class="px-4 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Painel Adm
                </div>
                @foreach($painelAdmChildren as $child)
                    <x-responsive-nav-link :href="route($child['route'])" :active="request()->routeIs($child['activePattern'])">
                        {{ $child['nome'] }}
                    </x-responsive-nav-link>
                @endforeach
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Sair') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>

    <div
        x-show="mobileNotificationsOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[70] flex items-end bg-slate-950/55 sm:hidden"
        @click.self="closeImportantNotifications()"
        style="display: none;"
    >
        <div class="important-notifications-mobile">
            <div class="important-notifications-mobile__header">
                <div>
                    <div class="important-notifications-panel__title">Pendências importantes</div>
                    <div class="important-notifications-panel__subtitle" x-text="importantNotificationsCount > 0 ? `Total: ${importantNotificationsCount}` : 'Nenhuma pendência no momento'"></div>
                </div>
                <button type="button" @click="closeImportantNotifications()" class="important-notifications-mobile__close" aria-label="Fechar">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="important-notifications-mobile__body">
                <template x-if="notificationsLoading && importantNotifications.length === 0">
                    <div class="important-notifications-empty">Atualizando pendências...</div>
                </template>

                <template x-if="!notificationsLoading && importantNotifications.length === 0">
                    <div class="important-notifications-empty">Nenhuma pendência importante para você agora.</div>
                </template>

                <template x-for="item in importantNotifications" :key="'mobile-' + item.item_key">
                    <button
                        type="button"
                        class="important-notification-item"
                        :class="item.tone_class || importantNotificationToneClass(item)"
                        @click="handleImportantNotificationSelect(item.url)"
                    >
                        <div class="important-notification-item__module" x-text="item.modulo"></div>
                        <div class="important-notification-item__title" x-text="item.titulo"></div>
                        <div class="important-notification-item__description" x-text="item.descricao"></div>
                        <div class="important-notification-item__meta">
                            <span class="important-notification-item__action" x-text="item.acao_label"></span>
                            <span x-text="item.occurred_at_label || ''"></span>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </div>
</nav>
