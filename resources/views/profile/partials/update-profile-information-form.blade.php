<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Informações do Perfil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Atualize seu e-mail de contato utilizado para notificações do sistema.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- Nome (somente leitura) --}}
        <div>
            <x-input-label for="NOMEUSER" :value="__('Nome')" />
            <x-text-input id="NOMEUSER" name="NOMEUSER" type="text" class="mt-1 block w-full bg-gray-50 dark:bg-gray-900 cursor-not-allowed opacity-75" :value="$user->NOMEUSER" readonly />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Para alterar o nome, entre em contato com o administrador.</p>
        </div>

        {{-- Login (somente leitura) --}}
        <div>
            <x-input-label for="NMLOGIN" :value="__('Login')" />
            <x-text-input id="NMLOGIN" name="NMLOGIN" type="text" class="mt-1 block w-full font-mono bg-gray-50 dark:bg-gray-900 cursor-not-allowed opacity-75" :value="$user->NMLOGIN" readonly />
        </div>

        {{-- E-mail --}}
        <div>
            <x-input-label for="email" :value="__('E-mail')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="email" placeholder="seu@plansul.com.br" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Utilizado para receber notificações do sistema.</p>
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Salvar') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Salvo.') }}</p>
            @endif
        </div>
    </form>
</section>
