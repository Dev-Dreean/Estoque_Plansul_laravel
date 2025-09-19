{{-- resources/views/profile/complete.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Completar Cadastro') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    <p class="mb-4">
                        @php($user = Auth::user())
                        @if($user && ($user->must_change_password ?? false))
                        Este é seu primeiro acesso. Defina uma nova senha e informe sua UF para continuar.
                        @else
                        Percebemos que seu cadastro está incompleto. Por favor, informe sua UF para continuar.
                        @endif
                    </p>

                    @if (session('warning'))
                    <div class="mb-4 font-medium text-sm text-yellow-600 dark:text-yellow-400">
                        {{ session('warning') }}
                    </div>
                    @endif

                    <form method="POST" action="{{ route('profile.completion.store') }}" autocomplete="off" x-data="passwordRules()" @submit.prevent="handleSubmit($event)" x-init="initTricks()" data-lpignore="true" data-form-type="other">
                        <!-- Anti-autocomplete dummy fields (alguns navegadores insistem) -->
                        <input type="text" name="_dummy_username" autocomplete="username" class="hidden" tabindex="-1" aria-hidden="true">
                        <input type="password" name="_dummy_password" autocomplete="new-password" class="hidden" tabindex="-1" aria-hidden="true">
                        @csrf

                        <div>
                            <x-input-label for="UF" :value="__('UF (Estado)')" />
                            <x-text-input id="UF" class="block mt-1 w-full" type="text" name="UF" :value="old('UF')" required autofocus maxlength="2" style="text-transform:uppercase" autocomplete="one-time-code" inputmode="text" data-lpignore="true" />
                            <x-input-error :messages="$errors->get('UF')" class="mt-2" />
                        </div>

                        @if($user && ($user->must_change_password ?? false))
                        <div class="mt-4" x-data="{ show: true }">
                            <x-input-label for="password" :value="__('Nova Senha')" />
                            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" x-model="pwd" @input="evaluate()" data-lpignore="true" autocapitalize="none" spellcheck="false" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            <div class="mt-3 space-y-1 text-xs" aria-live="polite">
                                <template x-for="rule in rules" :key="rule.key">
                                    <div class="flex items-center" :class="rule.met ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                                        <svg :class="'h-4 w-4 mr-1 ' + (rule.met ? '' : 'opacity-60')" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" x-show="rule.met" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5" x-show="!rule.met" />
                                        </svg>
                                        <span x-text="rule.label"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="mt-4">
                            <x-input-label for="password_confirmation" :value="__('Confirmar Nova Senha')" />
                            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" x-model="pwdConfirm" @input="evaluate()" data-lpignore="true" autocapitalize="none" spellcheck="false" />
                            <div class="mt-2 text-xs" :class="pwdConfirm.length ? (pwd === pwdConfirm ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400') : 'text-gray-500 dark:text-gray-400'">
                                <span x-text="pwdConfirm.length ? (pwd === pwdConfirm ? 'Confirmação coincide' : 'Confirmação ainda não coincide') : 'Digite a confirmação' "></span>
                            </div>
                        </div>
                        @endif

                        <div class="flex items-center justify-end mt-6">
                            <x-primary-button>
                                {{ __('Salvar e Continuar') }}
                            </x-primary-button>
                        </div>
                    </form>
                    <script>
                        function passwordRules() {
                            return {
                                pwd: '',
                                pwdConfirm: '',
                                rules: [{
                                        key: 'len',
                                        label: 'Mínimo 8 caracteres',
                                        met: false,
                                        test: p => p.length >= 8
                                    },
                                    {
                                        key: 'upper',
                                        label: 'Ao menos 1 letra maiúscula',
                                        met: false,
                                        test: p => /[A-Z]/.test(p)
                                    },
                                    {
                                        key: 'lower',
                                        label: 'Ao menos 1 letra minúscula',
                                        met: false,
                                        test: p => /[a-z]/.test(p)
                                    },
                                    {
                                        key: 'digit',
                                        label: 'Ao menos 1 número',
                                        met: false,
                                        test: p => /\d/.test(p)
                                    },
                                    {
                                        key: 'special',
                                        label: 'Ao menos 1 caractere especial',
                                        met: false,
                                        test: p => /[^A-Za-z0-9]/.test(p)
                                    },
                                ],
                                evaluate() {
                                    this.rules.forEach(r => r.met = r.test(this.pwd));
                                },
                                initTricks() {
                                    // Pequenos delays para tentar desestimular auto prefill
                                    setTimeout(() => {
                                        const f = document.getElementById('password');
                                        if (f) f.setAttribute('readonly', 'readonly');
                                        setTimeout(() => f && f.removeAttribute('readonly'), 250);
                                    }, 10);
                                },
                                handleSubmit(e) {
                                    // Se não existe campo de senha na tela (não precisa trocar), só envia.
                                    const passwordField = document.getElementById('password');
                                    if (!passwordField) {
                                        e.target.submit();
                                        return;
                                    }
                                    this.evaluate();
                                    const allMet = this.rules.every(r => r.met) && this.pwd === this.pwdConfirm;
                                    if (!allMet) {
                                        e.preventDefault();
                                        return;
                                    }
                                    // tudo ok -> enviar manualmente
                                    e.target.submit();
                                }
                            }
                        }
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>