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

@php
    $canGrantTelas = auth()->user()?->isAdmin() ?? false;
    $telaObrigatoria = 1000;
    $telasCombinadasIds = [1000, 1006];
    $telasPrincipaisIds = [1001, 1007];
    $telasSolicitacoesIds = [1010, 1011, 1012, 1013, 1014, 1015];
    $telasDisponiveis = collect($telasDisponiveis ?? []);
    $telaPatrimonio = $telasDisponiveis->firstWhere('NUSEQTELA', $telaObrigatoria);
    $telasPrincipais = $telasDisponiveis->filter(function ($tela) use ($telasPrincipaisIds) {
        return in_array((int) $tela->NUSEQTELA, $telasPrincipaisIds, true);
    })->values();
    $telasSolicitacoes = $telasDisponiveis->filter(function ($tela) use ($telasSolicitacoesIds) {
        return in_array((int) $tela->NUSEQTELA, $telasSolicitacoesIds, true);
    })->values();
    $telasEspeciais = $telasDisponiveis->reject(function ($tela) use ($telasPrincipaisIds, $telasCombinadasIds, $telasSolicitacoesIds) {
        $codigo = (int) $tela->NUSEQTELA;
        return in_array($codigo, $telasPrincipaisIds, true)
            || in_array($codigo, $telasCombinadasIds, true)
            || in_array($codigo, $telasSolicitacoesIds, true);
    })->values();

    $acessosAtuais = $acessosAtuais ?? [];
    $telasSelecionadas = old('telas', $acessosAtuais);
    if (!is_array($telasSelecionadas)) {
        $telasSelecionadas = [];
    }
    $telasSelecionadas = array_map('intval', $telasSelecionadas);
    if (!in_array($telaObrigatoria, $telasSelecionadas, true)) {
        $telasSelecionadas[] = $telaObrigatoria;
    }
    if (in_array($telaObrigatoria, $telasSelecionadas, true)
        && !in_array(1006, $telasSelecionadas, true)) {
        $telasSelecionadas[] = 1006;
    }
@endphp

<div
    x-data="userForm({
        existingId: {{ isset($usuario) ? (int)$usuario->NUSEQUSUARIO : 'null' }},
        nomeOld: @js(old('NOMEUSER', isset($usuario)? $usuario->NOMEUSER : '')),
        loginOld: @js(old('NMLOGIN', isset($usuario)? $usuario->NMLOGIN : '')),
        matriculaOld: @js(old('CDMATRFUNCIONARIO', isset($usuario)? $usuario->CDMATRFUNCIONARIO : '')),
        perfilOld: @js(old('PERFIL', isset($usuario)? $usuario->PERFIL : 'USR')),
        needsIdentityUpdateOld: @js(old('needs_identity_update', isset($usuario)? (bool) $usuario->needs_identity_update : false)),
    })"
    class="space-y-4">
    <div>
        <x-input-label for="CDMATRFUNCIONARIO" value="Matrícula (opcional)" />
        <x-text-input id="CDMATRFUNCIONARIO" name="CDMATRFUNCIONARIO" type="text" class="mt-1 block w-full" x-model="matricula" autofocus @blur="onMatriculaBlur" @input="onMatriculaInput" />
        <p class="text-xs text-gray-500 mt-1" x-show="matriculaExiste">Matrícula existente. Nome preenchido automaticamente.</p>
        <p class="text-xs text-gray-500 mt-1" x-show="!matricula">Deixe em branco para gerar uma matrícula temporária e obrigar o usuário a completar no primeiro acesso.</p>
    </div>
    <div>
        <x-input-label for="NOMEUSER" value="Nome Completo *" />
        <x-text-input id="NOMEUSER" name="NOMEUSER" type="text" class="mt-1 block w-full" x-model="nome" x-bind:readonly="nomeBloqueado"
            x-bind:class="nomeBloqueado ? 'bg-blue-50 dark:bg-blue-900/20 cursor-not-allowed ring-1 ring-blue-300/50 border-blue-300/60' : ''" x-bind:required="nameRequired" />
        <p class="text-xs text-gray-500 mt-1" x-show="isPlaceholderMatricula()">Nome poderá ser preenchido pelo usuário no primeiro acesso.</p>
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
            <option value="C" @selected(old('PERFIL', $usuario->PERFIL ?? '' )=='C' )>Consultor (Somente Leitura)</option>
            <option value="ADM" @selected(old('PERFIL', $usuario->PERFIL ?? '' )=='ADM' )>Administrador</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">
            <span x-show="perfil === 'USR'">Usuario com acesso definido pelas telas liberadas.</span>
            <span x-show="perfil === 'C'">Consultor com acesso definido pelas telas liberadas (somente leitura).</span>
            <span x-show="perfil === 'ADM'">Administrador com acesso a todas as telas.</span>
        </p>
    </div>
    @if($canGrantTelas)
    <div class="flex items-start gap-2">
        <input type="hidden" name="needs_identity_update" value="0">
        <input
            id="needs_identity_update"
            name="needs_identity_update"
            value="1"
            type="checkbox"
            class="h-4 w-4 rounded border-gray-300 text-plansul-blue focus:ring-plansul-blue"
            x-model="needsIdentityUpdate">
        <label for="needs_identity_update" class="text-sm text-gray-700 dark:text-gray-300">
            Exigir atualização do cadastro no próximo login
        </label>
    </div>
    <p class="text-xs text-gray-500">
        Ative esta opção para obrigar o usuário a revisar nome e matrícula ao logar novamente. A flag é removida automaticamente após a conclusão.
    </p>
    @endif

    @if($canGrantTelas)
    <x-permissions-section title="Permissões de Tela" description="Controle de Patrimônio, Histórico, Gráficos e Relatórios." badge="Admin">
        @if(isset($usuario) && ($usuario->PERFIL ?? '') === 'ADM')
        <p class="mt-2 text-xs text-gray-400">
            Perfil ADM tem acesso total. A seleção abaixo não altera esse acesso.
        </p>
        @endif

        <div class="mt-3 flex flex-wrap gap-2 flex-shrink-0">
            <button type="button" @click.prevent="marcarTodas()" class="text-xs px-3 py-1 rounded bg-gray-700 dark:bg-gray-700 border border-gray-600 dark:border-gray-600 text-gray-200 dark:text-gray-200 hover:bg-gray-600">
                Marcar todas
            </button>
            <button type="button" @click.prevent="desmarcarTodas()" class="text-xs px-3 py-1 rounded bg-gray-700 dark:bg-gray-700 border border-gray-600 dark:border-gray-600 text-gray-200 dark:text-gray-200 hover:bg-gray-600">
                Desmarcar todas
            </button>
        </div>

        <div class="mt-3 flex-1 overflow-y-auto">
            @if($telasPrincipais->isEmpty())
            <p class="text-xs text-gray-400">
                Nenhuma tela cadastrada em acessotela.
            </p>
            @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pr-2">
                @if($telaPatrimonio)
                @php
                    $selecionada = in_array($telaObrigatoria, $telasSelecionadas, true);
                    $nomePatrimonio = $telaPatrimonio->DETELA ?? 'Controle de Patrimônio';
                @endphp
                <x-permission-checkbox 
                    :checked="$selecionada" 
                    :disabled="$telaObrigatoria"
                    value="{{ $telaObrigatoria }}"
                    title="{{ $nomePatrimonio }}"
                    subtitle="Códigos: 1000 e 1006{{ $telaObrigatoria ? ' | Obrigatória' : '' }}" />
                <input type="hidden" name="telas[]" value="1006">
                @endif
                @foreach($telasPrincipais as $tela)
                @php
                    $selecionada = in_array((int) $tela->NUSEQTELA, $telasSelecionadas, true);
                @endphp
                <x-permission-checkbox 
                    :checked="$selecionada"
                    value="{{ $tela->NUSEQTELA }}"
                    title="{{ $tela->DETELA }}"
                    subtitle="Código: {{ $tela->NUSEQTELA }}{{ !empty($tela->NMSISTEMA) ? ' | Sistema: ' . $tela->NMSISTEMA : '' }}" />
                @endforeach
            </div>
            @endif

            @error('telas')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </x-permissions-section>
    @endif

    @if($telasSolicitacoes->isNotEmpty())
    <x-permissions-section title="Solicitações de Bens" description="Permissões para criar, visualizar e gerenciar solicitações de bens." badge="Admin">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
            @foreach($telasSolicitacoes as $tela)
            @php
                $selecionada = in_array((int) $tela->NUSEQTELA, $telasSelecionadas, true);
            @endphp
            <x-permission-checkbox 
                :checked="$selecionada"
                value="{{ $tela->NUSEQTELA }}"
                title="{{ $tela->DETELA }}"
                subtitle="Código: {{ $tela->NUSEQTELA }}{{ !empty($tela->NMSISTEMA) ? ' | Sistema: ' . $tela->NMSISTEMA : '' }}" />
            @endforeach
        </div>
    </x-permissions-section>
    @endif

    @if($telasEspeciais->isNotEmpty())
    <x-permissions-section title="Permissões Especiais" description="Telas administrativas e operacionais adicionais." badge="Admin">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
            @foreach($telasEspeciais as $tela)
            @php
                $selecionada = in_array((int) $tela->NUSEQTELA, $telasSelecionadas, true);
            @endphp
            <x-permission-checkbox 
                :checked="$selecionada"
                value="{{ $tela->NUSEQTELA }}"
                title="{{ $tela->DETELA }}"
                subtitle="Código: {{ $tela->NUSEQTELA }}{{ !empty($tela->NMSISTEMA) ? ' | Sistema: ' . $tela->NMSISTEMA : '' }}" />
            @endforeach
        </div>
    </x-permissions-section>
    @endif

    <div class="mt-4">
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
            perfilOld,
            needsIdentityUpdateOld
        }) {
            return {
                matricula: matriculaOld || '',
                nome: nomeOld || '',
                login: loginOld || '',
                perfil: perfilOld || 'USR',
                needsIdentityUpdate: needsIdentityUpdateOld ?? false,
                loginAuto: false,
                loginDisponivel: true,
                matriculaExiste: false,
                nomeBloqueado: false,
                get nameRequired() {
                    return !this.isPlaceholderMatricula();
                },
                isPlaceholderMatricula() {
                    const mat = (this.matricula || '').trim();
                    return mat === '' || ['0', '1'].includes(mat) || mat.startsWith('TMP-');
                },
                get loginHint() {
                    return this.login ? (this.loginDisponivel ? 'Login disponível' : 'Login já em uso') : '';
                },
                marcarTodas() {
                    document.querySelectorAll('input[name="telas[]"]').forEach(cb => {
                        if (!cb.disabled) cb.checked = true;
                    });
                },
                desmarcarTodas() {
                    document.querySelectorAll('input[name="telas[]"]').forEach(cb => {
                        if (!cb.disabled) cb.checked = false;
                    });
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
                    if (this.isPlaceholderMatricula()) {
                        this.matriculaExiste = false;
                        this.nomeBloqueado = false;
                        return;
                    }
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
                },
                validateForm() {
                    return true;
                }
            }
        }
    </script>
</div>

<div class="flex items-center justify-end gap-3 mt-6">
    <a href="{{ route('usuarios.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
        Cancelar
    </a>
    <x-primary-button @click="validateForm() && $el.closest('form').submit()">
        {{ isset($usuario) ? 'Atualizar Usuário' : 'Criar Usuário' }}
    </x-primary-button>
</div>
