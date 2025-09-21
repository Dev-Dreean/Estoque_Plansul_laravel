<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Temas') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="themePage()" data-initial-theme="{{ $active ?? 'light' }}">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="mb-6 rounded-md border border-green-300 bg-green-50 dark:bg-green-900/30 px-4 py-3 text-sm text-green-800 dark:text-green-300">
                {{ session('success') }}
            </div>
            @endif
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Escolha seu tema preferido. Essa preferência é salva por usuário (quando autenticado) e por cookie para visitantes. A troca abaixo aplica um preview imediato; clique em Salvar para persistir.</p>

                    <form method="POST" action="{{ route('settings.theme.update') }}" class="space-y-8" @submit="saving=true">
                        @csrf
                        <input type="hidden" name="theme" :value="selected" value="{{ $active ?? 'light' }}">

                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4" id="theme-cards">
                            @php $map = [
                            'light' => ['Claro','#f6f7f9','#ffffff','#1f2937','#4f46e5'],
                            'dark' => ['Escuro','#0f172a','#1e293b','#f1f5f9','#6366f1'],
                            'brown' => ['Marrom','#2e241c','#3a2d23','#f5ede6','#b86232'],
                            'beige' => ['Bege','#f5efe6','#efe7dd','#2a2320','#b57424'],
                            ]; @endphp
                            @foreach($map as $value => [$label,$bg,$surface,$text,$accent])
                            <label class="relative cursor-pointer group {{ ($active ?? 'light') === $value ? 'ring-2 ring-offset-2 ring-indigo-500 dark:ring-indigo-400 rounded-lg' : '' }}" x-data="{ value: '{{ $value }}' }" :class="{'ring-2 ring-offset-2 ring-indigo-500 dark:ring-indigo-400 rounded-lg': selected===value}">
                                <input type="radio" class="sr-only" name="theme_choice" value="{{ $value }}" @change="selected=value; apply(value)" {{ ($active ?? 'light') === $value ? 'checked' : '' }}>
                                <div class="flex flex-col h-full rounded-lg border border-base bg-surface overflow-hidden">
                                    <div class="h-24 flex items-center justify-center text-sm font-semibold tracking-wide bg-[var(--bg)] text-[var(--text)]" data-theme-preview="{{ $value }}">{{ $label }}</div>
                                    <div class="p-3 space-y-2 text-xs text-soft">
                                        <div class="flex items-center justify-between"><span>BG</span><span>{{ $bg }}</span></div>
                                        <div class="flex items-center justify-between"><span>Surface</span><span>{{ $surface }}</span></div>
                                        <div class="flex items-center justify-between"><span>Texto</span><span>{{ $text }}</span></div>
                                        <div class="flex items-center justify-between"><span>Accent</span><span>{{ $accent }}</span></div>
                                    </div>
                                    <div class="absolute top-2 right-2">
                                        <span x-show="selected===value" class="inline-flex items-center rounded-full bg-indigo-600 text-white text-[10px] px-2 py-0.5">Ativo</span>
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button x-bind:disabled="saving" class="min-w-32" x-text="saving ? 'Salvando...' : 'Salvar'" />
                            <button type="button" @click="resetPreview()" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">Cancelar Preview</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function themePage() {
            const container = document.querySelector('[data-initial-theme]');
            const initial = container?.getAttribute('data-initial-theme') || 'light';
            return {
                selected: initial,
                saving: false,
                apply(v) {
                    document.documentElement.dataset.theme = v;
                },
                resetPreview() {
                    this.selected = initial;
                    document.documentElement.dataset.theme = initial;
                }
            };
        }
    </script>
</x-app-layout>