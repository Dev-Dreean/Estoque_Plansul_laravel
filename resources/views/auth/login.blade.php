<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            {{-- MUDANÇA 1: Traduzindo o label "Login" --}}
            <x-input-label for="NMLOGIN" value="Usuário" />
            <x-text-input id="NMLOGIN" class="block mt-1 w-full" type="text" name="NMLOGIN" :value="old('NMLOGIN')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('NMLOGIN')" class="mt-2" />
        </div>

        <div class="mt-4">
            {{-- MUDANÇA 2: Traduzindo o label "Password" --}}
            <x-input-label for="password" value="Senha" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- MUDANÇA 3: Bloco "Remember Me" removido anteriormente --}}
        
        <div class="flex items-center justify-end mt-4">
            {{-- MUDANÇA 4: Link "Esqueceu sua senha?" REMOVIDO --}}
            {{-- @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif --}}

            {{-- MUDANÇA 5: Traduzindo o texto do botão --}}
            <x-primary-button class="ms-3">
                {{ __('Acessar') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>