{{-- resources/views/usuarios/partials/form.blade.php --}}

@if ($errors->any())
<div class="mb-4">
    <ul class="list-disc list-inside text-sm text-red-600">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div
    x-data="userForm({
        existingId: {{ isset($usuario) ? (int)$usuario->NUSEQUSUARIO : 'null' }},
        nomeOld: @js(old('NOMEUSER', isset($usuario)? $usuario->NOMEUSER : '')),
        loginOld: @js(old('NMLOGIN', isset($usuario)? $usuario->NMLOGIN : '')),
        matriculaOld: @js(old('CDMATRFUNCIONARIO', isset($usuario)? $usuario->CDMATRFUNCIONARIO : '')),
    })"
    class="space-y-4">
    <div>
        <x-input-label for="CDMATRFUNCIONARIO" value="Matrícula *" />
        <x-text-input id="CDMATRFUNCIONARIO" name="CDMATRFUNCIONARIO" type="text" class="mt-1 block w-full" x-model="matricula" required autofocus @blur="onMatriculaBlur" @input="onMatriculaInput" />
        <p class="text-xs text-gray-500 mt-1" x-show="matriculaExiste">Matrícula encontrada. Nome preenchido automaticamente.</p>
    </div>
    <div>
        <x-input-label for="NOMEUSER" value="Nome Completo *" />
        <x-text-input id="NOMEUSER" name="NOMEUSER" type="text" class="mt-1 block w-full" x-model="nome" x-bind:readonly="nomeBloqueado"
            x-bind:class="nomeBloqueado ? 'bg-blue-50 dark:bg-blue-900/20 cursor-not-allowed ring-1 ring-blue-300/50 border-blue-300/60' : ''" required />
    </div>
    <div>
        <x-input-label for="NMLOGIN" value="Login de Acesso *" />
        <x-text-input id="NMLOGIN" name="NMLOGIN" type="text" class="mt-1 block w-full font-mono" x-model="login" required @input="onLoginTyping"
            x-bind:class="[
                                                         login ? (loginDisponivel ? 'ring-1 ring-green-300/60 border-green-300/60' : 'ring-1 ring-red-300/60 border-red-300/60') : '',
                                                         loginAuto ? 'bg-blue-50 dark:bg-blue-900/20' : ''
                                                     ].join(' ')" />
        <p class="text-xs mt-1" :class="loginDisponivel ? 'text-green-600' : 'text-red-600'" x-text="loginHint"></p>
    </div>
    <div>
        <x-input-label for="PERFIL" value="Perfil *" />
        <select id="PERFIL" name="PERFIL" class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" required>
            <option value="USR" @selected(old('PERFIL', $usuario ?? '' )=='USR' )>Usuário Padrão</option>
            <option value="ADM" @selected(old('PERFIL', $usuario ?? '' )=='ADM' )>Administrador</option>
        </select>
    </div>
    <div>
        @if(isset($usuario))
        <x-input-label for="SENHA" value="Senha" />
        <span class="text-xs text-gray-500">Deixe em branco para não alterar</span>
        <x-text-input id="SENHA" name="SENHA" type="password" class="mt-1 block w-full" />
        @else
        <x-input-label value="Senha Provisória" />
        <div class="mt-1 text-sm rounded-md border border-dashed border-gray-400/40 dark:border-gray-600/60 bg-gray-50 dark:bg-gray-800 px-3 py-2">
            Uma senha inicial aleatória será gerada automaticamente (ex.: <span class="font-mono font-semibold">Plansul@123456</span>).<br>
            Você verá a senha após o cadastro e deverá repassá-la ao usuário. O usuário deverá trocá-la no primeiro acesso.
        </div>
        @endif
    </div>
    <script>
        function userForm({
            existingId,
            nomeOld,
            loginOld,
            matriculaOld
        }) {
            return {
                matricula: matriculaOld || '',
                nome: nomeOld || '',
                login: loginOld || '',
                loginAuto: false,
                loginDisponivel: true,
                matriculaExiste: false,
                nomeBloqueado: false,
                get loginHint() {
                    return this.login ? (this.loginDisponivel ? 'Login disponível' : 'Login já em uso') : '';
                },
                onMatriculaInput(e) {
                    const val = (e?.target?.value ?? '').trim();
                    if (val === '') {
                        // Reset total quando a matrícula é apagada
                        this.matriculaExiste = false;
                        this.nomeBloqueado = false;
                        this.nome = '';
                        this.login = '';
                        this.loginAuto = false;
                        this.loginDisponivel = true;
                    }
                },
                async onMatriculaBlur() {
                    const mat = (this.matricula || '').trim();
                    if (!mat) return;
                    try {
                        const url = `{{ route('api.usuarios.porMatricula') }}?matricula=${encodeURIComponent(mat)}`;
                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) throw new Error('Falha busca matrícula');
                        const data = await res.json();
                        this.matriculaExiste = !!data?.exists;
                        if (data?.exists && data?.nome) {
                            // Preenche nome automaticamente (sanitizado) e bloqueia edição
                            this.nome = data.nome;
                            this.nomeBloqueado = true;
                            // Sugere login inteligente se ainda vazio
                            if (!this.login) {
                                this.login = await this.sugerirLogin(this.nome, this.matriculaExiste ? mat : null);
                                this.loginAuto = !!this.login;
                            }
                        } else {
                            // Matrícula não existe: mantém nome digitável
                            this.nomeBloqueado = false;
                            if (!this.login && this.nome) {
                                this.login = await this.sugerirLogin(this.nome, null);
                                this.loginAuto = !!this.login;
                            }
                        }
                        // Valida disponibilidade do login atual
                        if (this.login) this.loginDisponivel = await this.checkLoginDisponivel(this.login, existingId);
                    } catch (e) {
                        console.warn('Lookup matrícula falhou', e);
                    }
                },
                async sugerirLogin(nome, matricula = null) {
                    const url = `{{ route('api.usuarios.sugerirLogin') }}?nome=${encodeURIComponent(nome)}${matricula ? `&matricula=${encodeURIComponent(matricula)}` : ''}`;
                    try {
                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();
                        return data?.login || '';
                    } catch {
                        return '';
                    }
                },
                async checkLoginDisponivel(login, existingId) {
                    const url = `{{ route('api.usuarios.loginDisponivel') }}?login=${encodeURIComponent(login)}${existingId ? `&ignore=${existingId}` : ''}`;
                    try {
                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();
                        return !!data?.available;
                    } catch {
                        return true;
                    }
                },
                $watch: {
                    async nome(val) {
                        if (this.nomeBloqueado) return; // não recalcula quando o nome veio do funcionário
                        // Se usuário alterar o nome e não tiver login manual, sugerimos novamente
                        if (!this.login || this.loginDisponivel) {
                            const clean = (val || '').replace(/[^\p{L}\s]/gu, ' ').replace(/\s+/g, ' ').trim();
                            this.login = await this.sugerirLogin(clean, this.matriculaExiste ? this.matricula : null);
                            this.loginAuto = !!this.login;
                            this.loginDisponivel = await this.checkLoginDisponivel(this.login, existingId);
                        }
                    },
                    async login(val) {
                        // sempre que o login mudar por qualquer razão, revalida disponibilidade
                        this.loginDisponivel = await this.checkLoginDisponivel(val, existingId);
                    }
                },
                onLoginTyping() {
                    // Usuário está digitando manualmente
                    this.loginAuto = false;
                }
            }
        }
    </script>
</div>

<div class="flex items-center justify-end mt-6">
    <a href="{{ route('usuarios.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline mr-4">
        Cancelar
    </a>
    <x-primary-button>
        {{ isset($usuario) ? 'Atualizar Usuário' : 'Criar Usuário' }}
    </x-primary-button>
</div>