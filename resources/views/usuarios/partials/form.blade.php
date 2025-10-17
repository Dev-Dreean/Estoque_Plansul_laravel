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
        perfilOld: @js(old('PERFIL', isset($usuario)? $usuario->PERFIL : 'USR')),
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
        <select
            id="PERFIL"
            name="PERFIL"
            x-model="perfil"
            class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
            required>
            <option value="USR" @selected(old('PERFIL', $usuario->PERFIL ?? '' )=='USR' )>Usuário Padrão</option>
            <option value="ADM" @selected(old('PERFIL', $usuario->PERFIL ?? '' )=='ADM' )>Administrador</option>
            <option value="SUP" @selected(old('PERFIL', $usuario->PERFIL ?? '' )=='SUP' )>Super Administrador</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">
            <span x-show="perfil === 'USR'">Usuário comum com acesso limitado às telas selecionadas abaixo.</span>
            <span x-show="perfil === 'ADM'">Administrador com acesso a todas as telas (sem permissão para excluir).</span>
            <span x-show="perfil === 'SUP'">Super Administrador com acesso total e permissão para excluir registros.</span>
        </p>
    </div>

    {{-- Gestão de Acessos (apenas para Usuário Padrão) --}}
    <div x-show="perfil === 'USR'" x-transition class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-800/50">
        <div class="flex items-center justify-between mb-3">
            <x-input-label value="Acesso às Telas" class="!mb-0" />
            <div class="flex gap-2">
                <button type="button" @click="marcarTodas" class="text-xs px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded">
                    Marcar Todas
                </button>
                <button type="button" @click="desmarcarTodas" class="text-xs px-2 py-1 bg-gray-500 hover:bg-gray-600 text-white rounded">
                    Desmarcar Todas
                </button>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-64 overflow-y-auto scrollbar-thin">
            @php
            $telasDisponiveis = \Illuminate\Support\Facades\DB::table('acessotela')
            ->where('FLACESSO', 'S')
            ->orderBy('NMSISTEMA')
            ->orderBy('DETELA')
            ->get();
            $telasUsuario = isset($usuario) ? $usuario->acessos->pluck('NUSEQTELA')->toArray() : [];

            // Telas OBRIGATÓRIAS (sempre marcadas e desabilitadas)
            $telasObrigatorias = [1000, 1001, 1005, 1006, 1007];
            @endphp
            @foreach($telasDisponiveis as $tela)
            @php
            $ehObrigatoria = in_array($tela->NUSEQTELA, $telasObrigatorias);
            $estaMarcada = $ehObrigatoria || in_array($tela->NUSEQTELA, old('telas', $telasUsuario));
            @endphp
            <label class="flex items-start gap-2 p-2 rounded {{ $ehObrigatoria ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-gray-100 dark:hover:bg-gray-700/50' }} cursor-pointer">
                <input
                    type="checkbox"
                    name="telas[]"
                    value="{{ $tela->NUSEQTELA }}"
                    @if($estaMarcada) checked @endif
                    @if($ehObrigatoria) disabled @endif
                    class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 {{ $ehObrigatoria ? 'opacity-50 cursor-not-allowed' : '' }}">
                {{-- Hidden input para garantir que telas obrigatórias sejam enviadas --}}
                @if($ehObrigatoria)
                <input type="hidden" name="telas[]" value="{{ $tela->NUSEQTELA }}">
                @endif
                <div class="flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $tela->DETELA }}
                        @if($ehObrigatoria)
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-semibold">(Obrigatória)</span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $tela->NMSISTEMA }}</div>
                </div>
            </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 mt-2">
            <strong>Telas obrigatórias:</strong> Controle de Patrimônio, Gráficos, Atribuir Termo, Histórico e Relatórios de Bens estão sempre ativas para todos os usuários.
        </p>
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
            matriculaOld,
            perfilOld
        }) {
            return {
                matricula: matriculaOld || '',
                nome: nomeOld || '',
                login: loginOld || '',
                perfil: perfilOld || 'USR',
                loginAuto: false,
                loginDisponivel: true,
                matriculaExiste: false,
                nomeBloqueado: false,
                get loginHint() {
                    return this.login ? (this.loginDisponivel ? 'Login disponível' : 'Login já em uso') : '';
                },
                marcarTodas() {
                    document.querySelectorAll('input[name="telas[]"]').forEach(cb => cb.checked = true);
                },
                desmarcarTodas() {
                    document.querySelectorAll('input[name="telas[]"]').forEach(cb => cb.checked = false);
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