<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">Confirmação de Usuário Criado</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="mb-4">Usuário criado com sucesso. Copie e repasse as credenciais abaixo para o usuário.</p>

                    <div id="credenciais" class="space-y-2 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">Login:</span>
                            <span class="font-mono" id="loginText">{{ $nmLogin }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">Senha:</span>
                            <span class="font-mono" id="senhaText">{{ $senhaProvisoria }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 flex-nowrap whitespace-nowrap">
                        <!-- Ícone sutil de copiar (sem aparência de botão) -->
                        <button id="copyBtn" type="button" title="Copiar credenciais" class="p-1 rounded-full text-blue-400 hover:text-blue-500 focus:outline-none" onclick="copyCredenciais()" aria-label="Copiar credenciais">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                        </button>

                        <a id="createAnotherBtn" href="{{ route('usuarios.create') }}" style="display:none;" class="px-4 py-2 bg-green-600 text-white rounded-md opacity-0 transition-opacity flex-shrink-0" role="button">Criar outro usuário</a>
                    </div>

                    <p id="copiedMsg" class="text-sm text-green-600 mt-3 hidden">Credenciais copiadas para a área de transferência.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyCredenciais() {
            const login = document.getElementById('loginText')?.innerText?.trim();
            const senha = document.getElementById('senhaText')?.innerText?.trim();
            const text = `Login: ${login}\nSenha: ${senha}`;
            console.log('[confirmacao] copyCredenciais called', {
                login,
                senha,
                text
            });

            const criar = document.getElementById('createAnotherBtn');

            function showCriarThenSair() {
                console.log('[confirmacao] showCriarThenSair - mostrando criar');
                try {
                    if (!criar) {
                        console.log('[confirmacao] criar element not found, aborting');
                        return;
                    }
                    criar.style.display = 'inline-flex';
                    criar.style.opacity = '0';
                    void criar.offsetWidth;
                    criar.style.transition = 'opacity 240ms ease-in-out';
                    criar.style.opacity = '1';
                    criar.focus();
                } catch (err) {
                    console.error('[confirmacao] erro ao mostrar criar', err);
                }
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                console.log('[confirmacao] navigator.clipboard available, attempting writeText');
                navigator.clipboard.writeText(text).then(() => {
                    console.log('[confirmacao] writeText succeeded');
                    document.getElementById('copiedMsg').classList.remove('hidden');
                    try {
                        const container = criar.parentElement || sair.parentElement;
                        if (container) container.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    } catch (err) {
                        console.warn('[confirmacao] warning scrolling to container', err);
                    }
                    showCriarThenSair();
                }).catch(err => {
                    console.error('[confirmacao] writeText failed', err);
                    // fallback to textarea method
                    fallbackCopy();
                });
            } else {
                console.log('[confirmacao] navigator.clipboard not available; using fallback');
                fallbackCopy();
            }

            function fallbackCopy() {
                const ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                try {
                    const ok = document.execCommand('copy');
                    console.log('[confirmacao] execCommand copy result', ok);
                    document.getElementById('copiedMsg').classList.remove('hidden');
                    try {
                        const container = criar.parentElement || sair.parentElement;
                        if (container) container.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    } catch (err) {
                        console.warn('[confirmacao] fallback scroll warn', err);
                    }
                    showCriarThenSair();
                } catch (e) {
                    console.error('[confirmacao] fallback copy failed', e);
                }
                ta.remove();
            }
        }
    </script>
</x-app-layout>