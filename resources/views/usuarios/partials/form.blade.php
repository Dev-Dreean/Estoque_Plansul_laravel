{{-- resources/views/usuarios/partials/form.blade.php --}}

@if ($errors->any())
<div class="mb-5 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-5 py-4">
    <div class="flex items-start gap-3">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <p class="text-sm font-semibold text-red-800 dark:text-red-300">Corrija os erros abaixo:</p>
            <ul class="mt-2 list-disc list-inside space-y-1 text-sm text-red-700 dark:text-red-400">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

@php
    $canGrantTelas = auth()->user()?->isAdmin() ?? false;
    $telaObrigatoria = 1000;
    $telasCombinadasIds = [1000, 1006];
    $telasPrincipaisIds = [1001, 1007];
    $telasSolicitacoesIds = [1010, 1011, 1012, 1013, 1014, 1015, 1016, 1017, 1018, 1019, 1020, 1021];
    $ordemSolicitacoes = [1010, 1013, 1019, 1012, 1021, 1020, 1014, 1015, 1011, 1018, 1017, 1016];
    $telasDisponiveis = collect($telasDisponiveis ?? []);

    $telaPatrimonio = $telasDisponiveis->firstWhere('NUSEQTELA', $telaObrigatoria);
    $telasPrincipais = $telasDisponiveis->filter(function ($tela) use ($telasPrincipaisIds) {
        return in_array((int) $tela->NUSEQTELA, $telasPrincipaisIds, true);
    })->values();

    $telasSolicitacoes = $telasDisponiveis->filter(function ($tela) use ($telasSolicitacoesIds) {
        return in_array((int) $tela->NUSEQTELA, $telasSolicitacoesIds, true);
    })->sortBy(function ($tela) use ($ordemSolicitacoes) {
        $posicao = array_search((int) $tela->NUSEQTELA, $ordemSolicitacoes, true);
        return $posicao === false ? 999 : $posicao;
    })->values();

    $telaSolicitacoesPrincipal = $telasSolicitacoes->firstWhere('NUSEQTELA', 1010);
    $telasSolicitacoesBase = $telasSolicitacoes->filter(fn ($tela) => in_array((int) $tela->NUSEQTELA, [1013], true))->values();
    $telasSolicitacoesTriagem = $telasSolicitacoes->filter(fn ($tela) => in_array((int) $tela->NUSEQTELA, [1019, 1015], true))->values();
    $telasSolicitacoesOperacao = $telasSolicitacoes->filter(fn ($tela) => in_array((int) $tela->NUSEQTELA, [1012, 1014], true))->values();
    $telasSolicitacoesLiberacao = $telasSolicitacoes->filter(fn ($tela) => in_array((int) $tela->NUSEQTELA, [1021, 1020], true))->values();
    $telasSolicitacoesVisibilidade = $telasSolicitacoes->filter(fn ($tela) => in_array((int) $tela->NUSEQTELA, [1011, 1018, 1017, 1016], true))->values();

    $rotulosSolicitacoes = [
        1010 => ['titulo' => 'Tela principal de Solicitações', 'descricao' => 'Libera a aba Solicitações de Bens. Sem esta permissão, as demais abaixo não têm efeito.'],
        1011 => ['titulo' => 'Ver todas as solicitações', 'descricao' => 'Permite enxergar solicitações de outros usuários e acompanhar o fluxo completo.'],
        1012 => ['titulo' => 'Dar andamento em Em Análise', 'descricao' => 'Permite atuar na etapa Em Análise e encaminhar o pedido para a próxima fase.'],
        1013 => ['titulo' => 'Criar solicitação', 'descricao' => 'Permite abrir novas solicitações de bens.'],
        1014 => ['titulo' => 'Registrar envio', 'descricao' => 'Permite informar o código de rastreio e marcar o pedido como enviado.'],
        1015 => ['titulo' => 'Cancelar solicitação', 'descricao' => 'Permite cancelar pedidos que não seguirão no fluxo.'],
        1016 => ['titulo' => 'Ver histórico', 'descricao' => 'Mostra a linha do tempo com movimentações, responsáveis e detalhes do pedido.'],
        1017 => ['titulo' => 'Gerenciar visibilidade', 'descricao' => 'Permite liberar ou remover visualização manual de solicitações.'],
        1018 => ['titulo' => 'Visualização restrita', 'descricao' => 'Mostra apenas solicitações vinculadas ao usuário ou ao fluxo dele.'],
        1019 => ['titulo' => 'Triagem inicial', 'descricao' => 'Confirma o pedido inicial e move a solicitação para Em Análise.'],
        1020 => ['titulo' => 'Liberação final do Bruno', 'descricao' => 'Permite concluir a etapa final de liberação antes do envio.'],
        1021 => ['titulo' => 'Autorização de liberação do Theo', 'descricao' => 'Permite autorizar a solicitação após as cotações e antes da liberação final do Bruno.'],
    ];

    $formatarMetaTela = function (int $codigo, ?string $sistema = null): string {
        $meta = 'Referência interna: ' . $codigo;
        if (!empty($sistema)) {
            $meta .= ' | Sistema: ' . $sistema;
        }

        return $meta;
    };

    $rotulosTelasGerais = [
        1000 => ['titulo' => 'Controle de Patrimônio', 'descricao' => 'Acesso base ao módulo principal de patrimônio.'],
        1001 => ['titulo' => 'Gráficos e indicadores', 'descricao' => 'Visualiza painéis e indicadores do sistema.'],
        1006 => ['titulo' => 'Relatórios', 'descricao' => 'Permite consultar e emitir relatórios.'],
        1007 => ['titulo' => 'Histórico principal', 'descricao' => 'Consulta movimentações e históricos gerais.'],
    ];

    $descreverTelaGeral = function ($tela) use ($rotulosTelasGerais, $formatarMetaTela) {
        $codigo = (int) $tela->NUSEQTELA;
        $rotulo = $rotulosTelasGerais[$codigo] ?? [
            'titulo' => $tela->DETELA,
            'descricao' => 'Libera acesso a esta area do sistema.',
        ];

        return [
            'titulo' => $rotulo['titulo'],
            'descricao' => $rotulo['descricao'],
            'meta' => $formatarMetaTela($codigo, $tela->NMSISTEMA ?? null),
        ];
    };

    $descreverTelaSolicitacao = function ($tela) use ($rotulosSolicitacoes, $formatarMetaTela) {
        $codigo = (int) $tela->NUSEQTELA;
        $rotulo = $rotulosSolicitacoes[$codigo] ?? [
            'titulo' => $tela->DETELA,
            'descricao' => 'Permissão vinculada ao fluxo de solicitações.',
        ];
        $meta = 'Código: ' . $codigo;
        if (!empty($tela->NMSISTEMA)) {
            $meta .= ' | Sistema: ' . $tela->NMSISTEMA;
        }
        $meta = $formatarMetaTela($codigo, $tela->NMSISTEMA ?? null);
        return [
            'titulo' => $rotulo['titulo'],
            'descricao' => $rotulo['descricao'],
            'meta' => $meta,
        ];
    };

    $telasSolicitacoesDependentes = $telasSolicitacoes
        ->pluck('NUSEQTELA')
        ->map(fn ($codigo) => (int) $codigo)
        ->filter(fn ($codigo) => $codigo !== 1010)
        ->values()
        ->all();

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
    if (in_array($telaObrigatoria, $telasSelecionadas, true) && !in_array(1006, $telasSelecionadas, true)) {
        $telasSelecionadas[] = 1006;
    }

    $papeisNotificacaoDisponiveis = $papeisNotificacaoDisponiveis ?? [];
    $papeisNotificacaoSelecionados = collect(old('notificacao_papeis', $papeisNotificacaoSelecionados ?? []))
        ->map(fn ($papel) => trim((string) $papel))
        ->filter()
        ->values()
        ->all();

    $isEditing = isset($usuario);
@endphp

<div class="space-y-6">

    {{-- ═══════════════════ ABA 1: IDENTIFICAÇÃO ═══════════════════ --}}
    <div x-show="activeTab === 'dados'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

        {{-- Card: Dados Pessoais --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="border-b border-gray-100 dark:border-gray-700 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Dados pessoais</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Identificação principal do usuário no sistema.</p>
            </div>

            <div class="p-6 space-y-5">
                {{-- Linha 1: Matrícula + Nome --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="md:col-span-1">
                        <x-input-label for="CDMATRFUNCIONARIO" value="Matrícula" />
                        <div class="relative mt-1">
                            <x-text-input
                                id="CDMATRFUNCIONARIO"
                                name="CDMATRFUNCIONARIO"
                                type="text"
                                class="block w-full dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600"
                                x-model="matricula"
                                placeholder="Ex: 12345"
                                autofocus
                                @blur="onMatriculaBlur"
                                @input="onMatriculaInput" />
                            <div x-show="matriculaExiste" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="matriculaExiste" x-cloak>
                            Matrícula encontrada. Nome carregado automaticamente.
                        </p>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500" x-show="!matricula">
                            Opcional — será gerada uma temporária se vazio.
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="NOMEUSER" value="Nome completo *" />
                        <x-text-input
                            id="NOMEUSER"
                            name="NOMEUSER"
                            type="text"
                            class="mt-1 block w-full dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600"
                            x-model="nome"
                            placeholder="Nome completo do funcionário"
                            x-bind:readonly="nomeBloqueado"
                            x-bind:class="nomeBloqueado ? 'bg-gray-50 dark:bg-gray-900/60 cursor-not-allowed opacity-75' : ''"
                            x-bind:required="nameRequired" />
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500" x-show="isPlaceholderMatricula()" x-cloak>
                            O usuário poderá preencher no primeiro acesso.
                        </p>
                    </div>
                </div>

                {{-- Linha 2: Login + Perfil --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <x-input-label for="NMLOGIN" value="Login de acesso *" />
                        <div class="relative mt-1">
                            <x-text-input
                                id="NMLOGIN"
                                name="NMLOGIN"
                                type="text"
                                class="block w-full font-mono dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600"
                                x-model="login"
                                required
                                placeholder="nome.sobrenome"
                                @input="onLoginTyping"
                                x-bind:class="[
                                    login ? (loginDisponivel ? 'ring-1 ring-green-300 dark:ring-green-700 border-green-300 dark:border-green-700' : 'ring-1 ring-red-300 dark:ring-red-700 border-red-300 dark:border-red-700') : ''
                                ].join(' ')" />
                            <div x-show="login" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg x-show="loginDisponivel" class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <svg x-show="!loginDisponivel" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                        </div>
                        <p class="mt-1 text-xs font-medium"
                           :class="loginDisponivel ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                           x-text="loginHint" x-show="login" x-cloak></p>
                    </div>

                    <div>
                        <x-input-label for="PERFIL" value="Perfil de acesso *" />
                        <select
                            id="PERFIL"
                            name="PERFIL"
                            x-model="perfil"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500 text-sm"
                            required>
                            <option value="USR" @selected(old('PERFIL', $usuario->PERFIL ?? '') == 'USR')>Usuário padrão</option>
                            <option value="C" @selected(old('PERFIL', $usuario->PERFIL ?? '') == 'C')>Consultor (somente leitura)</option>
                            <option value="ADM" @selected(old('PERFIL', $usuario->PERFIL ?? '') == 'ADM')>Administrador</option>
                        </select>

                        <div class="mt-2 rounded-lg px-3 py-2 text-xs"
                             :class="{
                                 'bg-gray-50 dark:bg-gray-900/50 text-gray-600 dark:text-gray-400': perfil === 'USR',
                                 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400': perfil === 'C',
                                 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400': perfil === 'ADM'
                             }">
                            <span x-show="perfil === 'USR'">Acesso definido pelas permissões de tela atribuídas.</span>
                            <span x-show="perfil === 'C'">Acesso somente leitura, respeitando as telas liberadas.</span>
                            <span x-show="perfil === 'ADM'">Acesso total ao sistema, independente das permissões de tela.</span>
                        </div>
                    </div>
                </div>

                {{-- Linha 3: E-mail --}}
                <div>
                    <x-input-label for="email" value="E-mail" />
                    <x-text-input
                        id="email"
                        name="email"
                        type="email"
                        class="mt-1 block w-full dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600"
                        :value="old('email', $usuario->email ?? '')"
                        placeholder="usuario@plansul.com.br"
                        autocomplete="email" />
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Opcional — o próprio usuário poderá preencher no primeiro acesso.</p>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
            </div>
        </div>

        {{-- Card: Comportamento de acesso (Admin only) --}}
        @if($canGrantTelas)
        <div class="mt-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="border-b border-gray-100 dark:border-gray-700 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Comportamento do acesso</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Controle o que o usuário deve revisar no próximo login.</p>
            </div>
            <div class="p-6">
                <label class="flex items-start gap-3 cursor-pointer rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 p-4 transition hover:border-indigo-300 dark:hover:border-indigo-700">
                    <input type="hidden" name="needs_identity_update" value="0">
                    <input
                        id="needs_identity_update"
                        name="needs_identity_update"
                        value="1"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-indigo-600 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                        x-model="needsIdentityUpdate">
                    <div>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            Exigir atualização do cadastro no próximo login
                        </span>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Ative quando o usuário precisar revisar nome, matrícula ou outros dados obrigatórios.
                        </p>
                    </div>
                </label>
            </div>
        </div>
        {{-- Card: Senha de acesso (somente edição) --}}
        @if($isEditing)
        <div class="mt-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="border-b border-gray-100 dark:border-gray-700 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Senha de acesso</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Gerencie o acesso do usuário ao sistema.</p>
            </div>
            <div class="p-6">
                <div class="max-w-lg space-y-3">
                    <div class="flex items-start gap-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 p-4">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Senha atual preservada</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">A senha não é exibida por segurança. Use o botão abaixo se o usuário esqueceu o acesso.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Usuário esqueceu a senha?</p>
                            <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">
                                Redefine para o padrão <span class="font-mono font-semibold">{{ 'Plansul@' . ($usuario->CDMATRFUNCIONARIO ?? '000000') }}</span> e força troca no próximo login.
                            </p>
                            @php
                                $resetConfirmMsg = 'Resetar a senha de ' . ($usuario->NOMEUSER ?? 'este usuário') . '? A nova senha será Plansul@ + matrícula.';
                            @endphp
                            <form method="POST" action="{{ route('usuarios.resetSenha', $usuario) }}" class="mt-3"
                                  onsubmit="return confirm({{ json_encode($resetConfirmMsg) }})">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-amber-300 dark:border-amber-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs font-medium text-amber-700 dark:text-amber-300 shadow-sm transition hover:bg-amber-50 dark:hover:bg-amber-900/40">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                    Resetar senha
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @endif
    </div>

    {{-- ═══════════════════ ABA 2: SEGURANÇA ═══════════════════ --}}
    <div x-show="activeTab === 'seguranca'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <h3 class="mt-4 text-base font-semibold text-gray-700 dark:text-gray-300">Em breve</h3>
                <p class="mt-2 max-w-sm mx-auto text-sm text-gray-500 dark:text-gray-400">
                    Configurações avançadas de segurança serão disponibilizadas em uma atualização futura.
                </p>
                <span class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                    🚧 Coming soon
                </span>
            </div>
        </div>
    </div>

    {{-- ═══════════════════ ABA 3: PERMISSÕES GERAIS ═══════════════════ --}}
    @if($canGrantTelas)
    <div x-show="activeTab === 'permissoes'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

        @if($isEditing && ($usuario->PERFIL ?? '') === 'ADM')
        <div class="mb-5 rounded-xl border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20 px-5 py-3.5 flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-violet-500 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm text-violet-700 dark:text-violet-300">
                Este usuário é <strong>Administrador</strong> — já tem acesso total ao sistema. As marcações abaixo servem apenas como referência.
            </p>
        </div>
        @endif

        {{-- Card: Acessos Gerais --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Acessos gerais do sistema</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Módulos principais: Patrimônio, gráficos, histórico.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click.prevent="marcarTodas('principais')"
                        class="rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 transition hover:bg-gray-50 dark:hover:bg-gray-600">
                        Marcar todas
                    </button>
                    <button type="button" @click.prevent="desmarcarTodas('principais')"
                        class="rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 transition hover:bg-gray-50 dark:hover:bg-gray-600">
                        Limpar
                    </button>
                </div>
            </div>

            <div class="p-6" id="telas-principais-container">
                @if($telasPrincipais->isEmpty() && !$telaPatrimonio)
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma tela principal encontrada.</p>
                @else
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @if($telaPatrimonio)
                    @php
                        $selecionada = in_array($telaObrigatoria, $telasSelecionadas, true);
                        $dadosPatrimonio = $descreverTelaGeral((object) [
                            'NUSEQTELA' => $telaObrigatoria,
                            'DETELA' => $telaPatrimonio->DETELA ?? 'Controle de Patrimônio',
                            'NMSISTEMA' => $telaPatrimonio->NMSISTEMA ?? null,
                        ]);
                        $nomePatrimonio = $telaPatrimonio->DETELA ?? 'Controle de Patrimônio';
                    @endphp
                    <x-permission-checkbox
                        :checked="$selecionada"
                        :disabled="true"
                        value="{{ $telaObrigatoria }}"
                        title="{{ $dadosPatrimonio['titulo'] }}"
                        subtitle="Acesso base obrigatório para o módulo de patrimônio."
                        meta="Códigos: 1000 e 1006 | Obrigatória" />
                    <input type="hidden" name="telas[]" value="1006">
                    @endif

                    @foreach($telasPrincipais as $tela)
                    @php
                        $selecionada = in_array((int) $tela->NUSEQTELA, $telasSelecionadas, true);
                        $dadosTela = $descreverTelaGeral($tela);
                        $meta = 'Código: ' . $tela->NUSEQTELA . (!empty($tela->NMSISTEMA) ? ' | ' . $tela->NMSISTEMA : '');
                    @endphp
                    <x-permission-checkbox
                        :checked="$selecionada"
                        value="{{ $tela->NUSEQTELA }}"
                        title="{{ $dadosTela['titulo'] }}"
                        subtitle="Libera acesso a esta área do sistema."
                        meta="{{ $dadosTela['meta'] }}" />
                    @endforeach
                </div>

                @error('telas')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                @endif
            </div>
        </div>

        {{-- Card: Permissões Especiais --}}
        @if($telasEspeciais->isNotEmpty())
        <div class="mt-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="border-b border-gray-100 dark:border-gray-700 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Permissões especiais</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Telas administrativas e operacionais adicionais.</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($telasEspeciais as $tela)
                    @php
                        $selecionada = in_array((int) $tela->NUSEQTELA, $telasSelecionadas, true);
                        $dadosTela = $descreverTelaGeral($tela);
                        $meta = 'Código: ' . $tela->NUSEQTELA . (!empty($tela->NMSISTEMA) ? ' | ' . $tela->NMSISTEMA : '');
                    @endphp
                    <x-permission-checkbox
                        :checked="$selecionada"
                        value="{{ $tela->NUSEQTELA }}"
                        title="{{ $dadosTela['titulo'] }}"
                        subtitle="Permissão administrativa ou operacional."
                        meta="{{ $dadosTela['meta'] }}" />
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ═══════════════════ ABA 4: SOLICITAÇÕES DE BENS ═══════════════════ --}}
    <div x-show="activeTab === 'solicitacoes'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

        @if($telasSolicitacoes->isNotEmpty())

        {{-- Mapa de papéis --}}
        <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-900/20 p-3">
                <div class="text-xs font-bold text-sky-800 dark:text-sky-300 uppercase tracking-wide">Solicitante</div>
                <p class="mt-1 text-[11px] text-sky-700 dark:text-sky-400 leading-relaxed">Cria o pedido e acompanha o recebimento.</p>
            </div>
            <div class="rounded-lg border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20 p-3">
                <div class="text-xs font-bold text-violet-800 dark:text-violet-300 uppercase tracking-wide">Triagem</div>
                <p class="mt-1 text-[11px] text-violet-700 dark:text-violet-400 leading-relaxed">Faz a triagem inicial e envia para análise.</p>
            </div>
            <div class="rounded-lg border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 p-3">
                <div class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wide">Operação</div>
                <p class="mt-1 text-[11px] text-emerald-700 dark:text-emerald-400 leading-relaxed">Atuam na análise e registram envio.</p>
            </div>
            <div class="rounded-lg border border-fuchsia-200 dark:border-fuchsia-800 bg-fuchsia-50 dark:bg-fuchsia-900/20 p-3">
                <div class="text-xs font-bold text-fuchsia-800 dark:text-fuchsia-300 uppercase tracking-wide">Liberação</div>
                <p class="mt-1 text-[11px] text-fuchsia-700 dark:text-fuchsia-400 leading-relaxed">Controla a autorização do Theo e a liberação final do Bruno.</p>
            </div>
        </div>

        {{-- Atalhos rápidos --}}
        <div class="mb-5 flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
            <span class="mr-2 text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Aplicar perfil:</span>
            <button type="button" @click.prevent="aplicarPerfilSolicitante()"
                class="rounded-md border border-sky-200 dark:border-sky-700 bg-sky-50 dark:bg-sky-900/30 px-3 py-1.5 text-xs font-medium text-sky-700 dark:text-sky-300 transition hover:bg-sky-100 dark:hover:bg-sky-900/50">
                Solicitante
            </button>
            <button type="button" @click.prevent="aplicarPerfilSupervisor()"
                class="rounded-md border border-violet-200 dark:border-violet-700 bg-violet-50 dark:bg-violet-900/30 px-3 py-1.5 text-xs font-medium text-violet-700 dark:text-violet-300 transition hover:bg-violet-100 dark:hover:bg-violet-900/50">
                Triagem
            </button>
            <button type="button" @click.prevent="aplicarPerfilOperacao()"
                class="rounded-md border border-emerald-200 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-300 transition hover:bg-emerald-100 dark:hover:bg-emerald-900/50">
                Operação
            </button>
            <button type="button" @click.prevent="aplicarPerfilLiberacao()"
                class="rounded-md border border-fuchsia-200 dark:border-fuchsia-700 bg-fuchsia-50 dark:bg-fuchsia-900/30 px-3 py-1.5 text-xs font-medium text-fuchsia-700 dark:text-fuchsia-300 transition hover:bg-fuchsia-100 dark:hover:bg-fuchsia-900/50">
                Liberação
            </button>
        </div>

        <div class="mb-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-4 shadow-sm">
            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Como ler esta tela</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">O nome e a descrição mostram a ação da permissão. O número fica apenas como referência interna para suporte.</p>
        </div>

        {{-- Passo 1: Chave de acesso --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="border-b border-gray-100 dark:border-gray-700 px-6 py-4">
                <div class="flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-xs font-bold text-indigo-700 dark:text-indigo-300">1</span>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Chave de acesso do módulo</h3>
                </div>
                <p class="mt-1 ml-8 text-xs text-gray-500 dark:text-gray-400">Libere a entrada em Solicitações de Bens. Sem isso, as demais permissões não funcionam.</p>
            </div>
            <div class="p-6">
                @if($telaSolicitacoesPrincipal)
                @php
                    $selecionada = in_array((int) $telaSolicitacoesPrincipal->NUSEQTELA, $telasSelecionadas, true);
                    $dadosTelaPrincipal = $descreverTelaSolicitacao($telaSolicitacoesPrincipal);
                @endphp
                <x-permission-checkbox
                    :checked="$selecionada"
                    value="{{ $telaSolicitacoesPrincipal->NUSEQTELA }}"
                    title="{{ $dadosTelaPrincipal['titulo'] }}"
                    subtitle="{{ $dadosTelaPrincipal['descricao'] }}"
                    meta="{{ $dadosTelaPrincipal['meta'] }}" />
                @endif
            </div>
        </div>

        {{-- Aviso quando chave não ativa --}}
        <div x-show="!hasTela(1010)" x-cloak class="mt-5 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-5 py-4">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 shrink-0 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-sm text-amber-800 dark:text-amber-300">
                    Ative a chave de acesso acima para configurar as permissões de Solicitações de Bens.
                </p>
            </div>
        </div>

        {{-- Passos 2-6 --}}
        <div x-show="hasTela(1010)" x-cloak class="mt-5 space-y-5">

            @if($telasSolicitacoesBase->isNotEmpty())
            <div class="rounded-xl border border-sky-200 dark:border-sky-800/50 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <div class="border-b border-sky-100 dark:border-sky-800/30 bg-sky-50/50 dark:bg-sky-900/10 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-sky-100 dark:bg-sky-900/40 text-xs font-bold text-sky-700 dark:text-sky-300">2</span>
                        <h3 class="text-sm font-semibold text-sky-900 dark:text-sky-200">Solicitação inicial</h3>
                    </div>
                    <p class="mt-1 ml-8 text-xs text-sky-700 dark:text-sky-400">Permissões para quem abre o pedido.</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach($telasSolicitacoesBase as $tela)
                        @php
                            $selecionada = in_array((int) $tela->NUSEQTELA, $telasSelecionadas, true);
                            $dadosTela = $descreverTelaSolicitacao($tela);
                        @endphp
                        <x-permission-checkbox
                            :checked="$selecionada"
                            value="{{ $tela->NUSEQTELA }}"
                            title="{{ $dadosTela['titulo'] }}"
                            subtitle="{{ $dadosTela['descricao'] }}"
                            meta="{{ $dadosTela['meta'] }}" />
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if($telasSolicitacoesTriagem->isNotEmpty())
            <div class="rounded-xl border border-violet-200 dark:border-violet-800/50 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <div class="border-b border-violet-100 dark:border-violet-800/30 bg-violet-50/50 dark:bg-violet-900/10 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-violet-100 dark:bg-violet-900/40 text-xs font-bold text-violet-700 dark:text-violet-300">3</span>
                        <h3 class="text-sm font-semibold text-violet-900 dark:text-violet-200">Triagem inicial</h3>
                    </div>
                    <p class="mt-1 ml-8 text-xs text-violet-700 dark:text-violet-400">Quem decide se o pedido segue para Em Análise.</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach($telasSolicitacoesTriagem as $tela)
                        @php
                            $selecionada = in_array((int) $tela->NUSEQTELA, $telasSelecionadas, true);
                            $dadosTela = $descreverTelaSolicitacao($tela);
                        @endphp
                        <x-permission-checkbox
                            :checked="$selecionada"
                            value="{{ $tela->NUSEQTELA }}"
                            title="{{ $dadosTela['titulo'] }}"
                            subtitle="{{ $dadosTela['descricao'] }}"
                            meta="{{ $dadosTela['meta'] }}" />
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if($telasSolicitacoesOperacao->isNotEmpty())
            <div class="rounded-xl border border-emerald-200 dark:border-emerald-800/50 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <div class="border-b border-emerald-100 dark:border-emerald-800/30 bg-emerald-50/50 dark:bg-emerald-900/10 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-xs font-bold text-emerald-700 dark:text-emerald-300">4</span>
                        <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">Em Análise e envio</h3>
                    </div>
                    <p class="mt-1 ml-8 text-xs text-emerald-700 dark:text-emerald-400">Quem acompanha o processo e registra envio.</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach($telasSolicitacoesOperacao as $tela)
                        @php
                            $selecionada = in_array((int) $tela->NUSEQTELA, $telasSelecionadas, true);
                            $dadosTela = $descreverTelaSolicitacao($tela);
                        @endphp
                        <x-permission-checkbox
                            :checked="$selecionada"
                            value="{{ $tela->NUSEQTELA }}"
                            title="{{ $dadosTela['titulo'] }}"
                            subtitle="{{ $dadosTela['descricao'] }}"
                            meta="{{ $dadosTela['meta'] }}" />
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if($telasSolicitacoesLiberacao->isNotEmpty())
            <div class="rounded-xl border border-fuchsia-200 dark:border-fuchsia-800/50 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <div class="border-b border-fuchsia-100 dark:border-fuchsia-800/30 bg-fuchsia-50/50 dark:bg-fuchsia-900/10 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-fuchsia-100 dark:bg-fuchsia-900/40 text-xs font-bold text-fuchsia-700 dark:text-fuchsia-300">5</span>
                        <h3 class="text-sm font-semibold text-fuchsia-900 dark:text-fuchsia-200">Autorização e liberação</h3>
                    </div>
                    <p class="mt-1 ml-8 text-xs text-fuchsia-700 dark:text-fuchsia-400">Quem autoriza a solicitação após as cotações e quem faz a liberação final antes do envio.</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach($telasSolicitacoesLiberacao as $tela)
                        @php
                            $selecionada = in_array((int) $tela->NUSEQTELA, $telasSelecionadas, true);
                            $dadosTela = $descreverTelaSolicitacao($tela);
                        @endphp
                        <x-permission-checkbox
                            :checked="$selecionada"
                            value="{{ $tela->NUSEQTELA }}"
                            title="{{ $dadosTela['titulo'] }}"
                            subtitle="{{ $dadosTela['descricao'] }}"
                            meta="{{ $dadosTela['meta'] }}" />
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if($telasSolicitacoesVisibilidade->isNotEmpty())
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <div class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-900/20 px-6 py-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-xs font-bold text-gray-700 dark:text-gray-300">6</span>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Visibilidade e histórico</h3>
                    </div>
                    <p class="mt-1 ml-8 text-xs text-gray-500 dark:text-gray-400">Controles de visualização de pedidos e histórico.</p>
                </div>
                <div class="p-6 space-y-5">

                    {{-- Escopo de visualização: 1011 x 1018 (mutuamente exclusivos) --}}
                    @php
                        $tela1011 = $telasSolicitacoesVisibilidade->firstWhere('NUSEQTELA', 1011);
                        $tela1018 = $telasSolicitacoesVisibilidade->firstWhere('NUSEQTELA', 1018);
                        $sel1011 = in_array(1011, $telasSelecionadas, true);
                        $sel1018 = in_array(1018, $telasSelecionadas, true);
                        $escopoInicial = $sel1011 ? 'todas' : ($sel1018 ? 'restrita' : 'nenhum');
                    @endphp
                    @if($tela1011 || $tela1018)
                    <div>
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">
                            Escopo de visualização
                            <span class="ml-1 normal-case font-normal text-gray-400 dark:text-gray-500">— escolha uma opção</span>
                        </p>

                        {{-- Checkboxes ocultos (submetidos como telas[]) --}}
                        <input type="checkbox" id="tela-cb-1011" name="telas[]" value="1011" class="sr-only" {{ $sel1011 ? 'checked' : '' }}>
                        <input type="checkbox" id="tela-cb-1018" name="telas[]" value="1018" class="sr-only" {{ $sel1018 ? 'checked' : '' }}>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            {{-- Opção: Sem acesso --}}
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border-2 p-4 transition select-none"
                                   :class="visEscopo === 'nenhum' ? 'border-indigo-400 dark:border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                                   x-data="{ get visEscopo() { return document.getElementById('tela-cb-1011')?.checked ? 'todas' : (document.getElementById('tela-cb-1018')?.checked ? 'restrita' : 'nenhum'); } }">
                                <input type="radio" name="visibilidade_escopo" value="nenhum" class="mt-0.5 accent-indigo-600"
                                       onchange="syncVisibilidadeEscopo('nenhum')"
                                       {{ $escopoInicial === 'nenhum' ? 'checked' : '' }}>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Sem acesso de visualização</p>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Não vê pedidos além dos próprios.</p>
                                </div>
                            </label>

                            {{-- Opção: Visualização restrita (1018) --}}
                            @if($tela1018)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border-2 p-4 transition select-none"
                                   :class="visEscopo === 'restrita' ? 'border-indigo-400 dark:border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                                   x-data="{ get visEscopo() { return document.getElementById('tela-cb-1011')?.checked ? 'todas' : (document.getElementById('tela-cb-1018')?.checked ? 'restrita' : 'nenhum'); } }">
                                <input type="radio" name="visibilidade_escopo" value="restrita" class="mt-0.5 accent-indigo-600"
                                       onchange="syncVisibilidadeEscopo('restrita')"
                                       {{ $escopoInicial === 'restrita' ? 'checked' : '' }}>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Visualização restrita</p>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Somente pedidos vinculados ao próprio fluxo.</p>
                                    <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500 font-mono">CÓDIGO: 1018</p>
                                </div>
                            </label>
                            @endif

                            {{-- Opção: Ver todas (1011) --}}
                            @if($tela1011)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border-2 p-4 transition select-none"
                                   :class="visEscopo === 'todas' ? 'border-indigo-400 dark:border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                                   x-data="{ get visEscopo() { return document.getElementById('tela-cb-1011')?.checked ? 'todas' : (document.getElementById('tela-cb-1018')?.checked ? 'restrita' : 'nenhum'); } }">
                                <input type="radio" name="visibilidade_escopo" value="todas" class="mt-0.5 accent-indigo-600"
                                       onchange="syncVisibilidadeEscopo('todas')"
                                       {{ $escopoInicial === 'todas' ? 'checked' : '' }}>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Ver todas as solicitações</p>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Enxerga pedidos de todos os usuários no sistema.</p>
                                    <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500 font-mono">CÓDIGO: 1011</p>
                                </div>
                            </label>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Gerenciamento e histórico: 1017 e 1016 (independentes) --}}
                    @php
                        $telasVisIndep = $telasSolicitacoesVisibilidade->filter(fn($t) => in_array((int)$t->NUSEQTELA, [1017, 1016], true))->values();
                    @endphp
                    @if($telasVisIndep->isNotEmpty())
                    <div>
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Gerenciamento e histórico</p>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            @foreach($telasVisIndep as $tela)
                            @php
                                $selecionada = in_array((int) $tela->NUSEQTELA, $telasSelecionadas, true);
                                $dadosTela = $descreverTelaSolicitacao($tela);
                            @endphp
                            <x-permission-checkbox
                                :checked="$selecionada"
                                value="{{ $tela->NUSEQTELA }}"
                                title="{{ $dadosTela['titulo'] }}"
                                subtitle="{{ $dadosTela['descricao'] }}"
                                meta="{{ $dadosTela['meta'] }}" />
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        @else
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-8 text-center shadow-sm">
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma tela de Solicitações de Bens configurada no sistema.</p>
        </div>
        @endif
    </div>
    @endif

    {{-- ═══════════════════ RODAPÉ: AÇÕES ═══════════════════ --}}
    @if($canGrantTelas)
    <div x-show="activeTab === 'notificacoes'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="rounded-2xl border border-orange-200 dark:border-orange-900/40 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="border-b border-orange-100 dark:border-orange-900/30 bg-gradient-to-r from-sky-50 via-white to-orange-50 dark:from-sky-950/20 dark:via-gray-800 dark:to-orange-950/20 px-6 py-5">
                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Notificações por etapa</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Defina em quais etapas do fluxo este usuário deve receber os e-mails automáticos de Solicitações de Bens.
                        </p>
                    </div>
                    <a href="{{ route('usuarios.notificacoes.solicitacoes') }}"
                       class="inline-flex items-center justify-center rounded-lg border border-orange-200 dark:border-orange-800 px-3 py-2 text-xs font-semibold text-orange-700 dark:text-orange-300 transition hover:bg-orange-50 dark:hover:bg-orange-900/20">
                        Ver visão geral
                    </a>
                </div>
            </div>

            <div class="p-6 space-y-4">
                <div class="rounded-xl border border-sky-200 dark:border-sky-900/40 bg-sky-50/70 dark:bg-sky-950/20 px-4 py-3">
                    <p class="text-sm font-semibold text-sky-900 dark:text-sky-200">Regra da abertura</p>
                    <p class="mt-1 text-xs text-sky-800 dark:text-sky-300">
                        Quando uma solicitação é criada, o sistema avisa todos os responsáveis operacionais marcados nas etapas abaixo e também mantém o solicitante informado.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    @foreach($papeisNotificacaoDisponiveis as $papel => $dadosPapel)
                    <label class="flex items-start gap-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-900/30 p-4 transition hover:border-orange-300 dark:hover:border-orange-700">
                        <input type="checkbox"
                               name="notificacao_papeis[]"
                               value="{{ $papel }}"
                               class="mt-1 h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                               {{ in_array($papel, $papeisNotificacaoSelecionados, true) ? 'checked' : '' }}>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $dadosPapel['titulo'] }}</p>
                                @if(in_array($papel, ['triagem', 'medicao', 'cotacao', 'liberacao'], true))
                                <span class="inline-flex items-center rounded-full bg-orange-100 dark:bg-orange-900/30 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-orange-700 dark:text-orange-300">
                                    Entra na criação
                                </span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-400">{{ $dadosPapel['descricao'] }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-4 shadow-sm">
        <a href="{{ route('usuarios.index') }}"
           class="text-sm font-medium text-gray-500 dark:text-gray-400 transition hover:text-gray-700 dark:hover:text-gray-200">
            Cancelar
        </a>
        <x-primary-button type="submit" class="px-6 py-2.5">
            {{ $isEditing ? 'Salvar alterações' : 'Criar usuário' }}
        </x-primary-button>
    </div>

    {{-- ═══════════════════ ALPINE.JS ═══════════════════ --}}
    <script>
        function syncVisibilidadeEscopo(modo) {
            const cb1011 = document.getElementById('tela-cb-1011');
            const cb1018 = document.getElementById('tela-cb-1018');
            if (cb1011) cb1011.checked = (modo === 'todas');
            if (cb1018) cb1018.checked = (modo === 'restrita');
        }

        function userForm({
            existingId,
            nomeOld,
            loginOld,
            matriculaOld,
            perfilOld,
            needsIdentityUpdateOld
        }) {
            return {
                activeTab: 'dados',
                matricula: matriculaOld || '',
                nome: nomeOld || '',
                login: loginOld || '',
                perfil: perfilOld || 'USR',
                needsIdentityUpdate: needsIdentityUpdateOld ?? false,
                loginAuto: false,
                loginDisponivel: true,
                matriculaExiste: false,
                nomeBloqueado: false,
                solicitacoesPrincipal: 1010,
                solicitacoesDependentes: @js($telasSolicitacoesDependentes),

                init() {
                    this.registrarWatchers();
                    this.$nextTick(() => {
                        this.registrarEventosPermissoes();
                        this.syncPermissoesSolicitacoes();
                    });
                },

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

                registrarWatchers() {
                    this.$watch('nome', async (val) => {
                        if (this.nomeBloqueado) return;
                        if (!this.login || this.loginDisponivel) {
                            const clean = (val || '').replace(/[^\p{L}\s]/gu, ' ').replace(/\s+/g, ' ').trim();
                            this.login = await this.sugerirLogin(clean, this.matriculaExiste ? this.matricula : null);
                            this.loginAuto = !!this.login;
                            this.loginDisponivel = await this.checkLoginDisponivel(this.login, existingId);
                        }
                    });

                    this.$watch('login', async (val) => {
                        this.loginDisponivel = await this.checkLoginDisponivel(val, existingId);
                    });
                },

                registrarEventosPermissoes() {
                    document.querySelectorAll('input[name="telas[]"]').forEach((checkbox) => {
                        checkbox.addEventListener('change', () => this.syncPermissoesSolicitacoes());
                    });
                    // Sincroniza radio de visibilidade ao marcar 1011/1018 por outras vias (ex: limpar tudo)
                    const cb1011 = document.getElementById('tela-cb-1011');
                    const cb1018 = document.getElementById('tela-cb-1018');
                    if (cb1011) cb1011.addEventListener('change', () => this.syncVisibilidadeRadio());
                    if (cb1018) cb1018.addEventListener('change', () => this.syncVisibilidadeRadio());
                },

                syncVisibilidadeRadio() {
                    const hasTodas = document.getElementById('tela-cb-1011')?.checked;
                    const hasRestrita = document.getElementById('tela-cb-1018')?.checked;
                    const modo = hasTodas ? 'todas' : (hasRestrita ? 'restrita' : 'nenhum');
                    document.querySelectorAll('input[name="visibilidade_escopo"]').forEach(r => {
                        r.checked = (r.value === modo);
                    });
                },

                getTelaCheckbox(codigo) {
                    return document.querySelector(`input[name="telas[]"][value="${codigo}"]`);
                },

                hasTela(codigo) {
                    const checkbox = this.getTelaCheckbox(codigo);
                    return !!(checkbox && checkbox.checked);
                },

                setTela(codigo, ativo) {
                    const checkbox = this.getTelaCheckbox(codigo);
                    if (!checkbox || checkbox.disabled) return;
                    checkbox.checked = !!ativo;
                },

                syncPermissoesSolicitacoes() {
                    if (this.hasTela(this.solicitacoesPrincipal)) return;
                    this.solicitacoesDependentes.forEach((codigo) => this.setTela(codigo, false));
                },

                limparPermissoesSolicitacoes() {
                    this.solicitacoesDependentes.forEach((codigo) => this.setTela(codigo, false));
                },

                aplicarPerfilSolicitante() {
                    this.setTela(this.solicitacoesPrincipal, true);
                    this.limparPermissoesSolicitacoes();
                    [1013].forEach((codigo) => this.setTela(codigo, true));
                },

                aplicarPerfilOperacao() {
                    this.setTela(this.solicitacoesPrincipal, true);
                    this.limparPermissoesSolicitacoes();
                    [1011, 1012, 1014, 1016].forEach((codigo) => this.setTela(codigo, true));
                },

                aplicarPerfilSupervisor() {
                    this.setTela(this.solicitacoesPrincipal, true);
                    this.limparPermissoesSolicitacoes();
                    [1011, 1015, 1016, 1019].forEach((codigo) => this.setTela(codigo, true));
                },

                aplicarPerfilLiberacao() {
                    this.setTela(this.solicitacoesPrincipal, true);
                    this.limparPermissoesSolicitacoes();
                    [1011, 1016, 1020, 1021].forEach((codigo) => this.setTela(codigo, true));
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
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error('Falha ao buscar matrícula');
                        const data = await res.json();
                        this.matriculaExiste = !!data?.exists;
                        if (data?.exists && data?.nome) {
                            this.nome = data.nome;
                            this.nomeBloqueado = true;
                            if (!this.login) {
                                this.login = await this.sugerirLogin(this.nome, this.matriculaExiste ? mat : null);
                                this.loginAuto = !!this.login;
                            }
                        } else {
                            this.nomeBloqueado = false;
                            if (!this.login && this.nome) {
                                this.login = await this.sugerirLogin(this.nome, null);
                                this.loginAuto = !!this.login;
                            }
                        }
                        if (this.login) this.loginDisponivel = await this.checkLoginDisponivel(this.login, existingId);
                    } catch (e) {
                        console.warn('Falha ao consultar matrícula', e);
                    }
                },

                async sugerirLogin(nome, matricula = null) {
                    const url = `{{ route('api.usuarios.sugerirLogin') }}?nome=${encodeURIComponent(nome)}${matricula ? `&matricula=${encodeURIComponent(matricula)}` : ''}`;
                    try {
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        return data?.login || '';
                    } catch {
                        return '';
                    }
                },

                async checkLoginDisponivel(login, existingId) {
                    const url = `{{ route('api.usuarios.loginDisponivel') }}?login=${encodeURIComponent(login)}${existingId ? `&ignore=${existingId}` : ''}`;
                    try {
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        return !!data?.available;
                    } catch {
                        return true;
                    }
                },

                onLoginTyping() {
                    this.loginAuto = false;
                },

                validateForm() {
                    return true;
                }
            }
        }
    </script>
</div>
