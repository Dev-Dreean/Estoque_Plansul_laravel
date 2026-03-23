{{-- resources/views/usuarios/partials/form.blade.php --}}

@if ($errors->any())
<div class="mb-4 rounded-2xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-400">
    <ul class="list-disc list-inside space-y-1">
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
    $telasSolicitacoesIds = [1010, 1011, 1012, 1013, 1014, 1015, 1016, 1017, 1018, 1019, 1020];
    $ordemSolicitacoes = [1010, 1013, 1019, 1012, 1020, 1014, 1015, 1011, 1018, 1017, 1016];
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
    $telasSolicitacoesLiberacao = $telasSolicitacoes->filter(fn ($tela) => in_array((int) $tela->NUSEQTELA, [1020], true))->values();
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
        1020 => ['titulo' => 'Liberação final', 'descricao' => 'Permite concluir a etapa de Liberação antes do envio.'],
    ];

    $descreverTelaSolicitacao = function ($tela) use ($rotulosSolicitacoes) {
        $codigo = (int) $tela->NUSEQTELA;
        $rotulo = $rotulosSolicitacoes[$codigo] ?? [
            'titulo' => $tela->DETELA,
            'descricao' => 'Permissão vinculada ao fluxo de solicitações.',
        ];

        $meta = 'Código: ' . $codigo;
        if (!empty($tela->NMSISTEMA)) {
            $meta .= ' | Sistema: ' . $tela->NMSISTEMA;
        }

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
@endphp

<div
    x-data="userForm({
        existingId: {{ isset($usuario) ? (int) $usuario->NUSEQUSUARIO : 'null' }},
        nomeOld: @js(old('NOMEUSER', isset($usuario) ? $usuario->NOMEUSER : '')),
        loginOld: @js(old('NMLOGIN', isset($usuario) ? $usuario->NMLOGIN : '')),
        matriculaOld: @js(old('CDMATRFUNCIONARIO', isset($usuario) ? $usuario->CDMATRFUNCIONARIO : '')),
        perfilOld: @js(old('PERFIL', isset($usuario) ? $usuario->PERFIL : 'USR')),
        needsIdentityUpdateOld: @js(old('needs_identity_update', isset($usuario) ? (bool) $usuario->needs_identity_update : false)),
    })"
    class="space-y-5">

    <section class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm">
        <div class="mb-4 flex flex-col gap-1 md:flex-row md:items-start md:justify-between border-b border-slate-100 dark:border-slate-700 pb-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Dados do usuário</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Preencha a identificação principal e defina como esse usuário vai acessar o sistema.</p>
            </div>
            <div class="rounded-full bg-violet-50 dark:bg-violet-900/30 px-3 py-1 text-xs font-medium text-violet-700 dark:text-violet-300">
                Cadastro e acesso
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-12 pt-2">
            <div class="xl:col-span-3">
                <x-input-label for="CDMATRFUNCIONARIO" value="Matrícula (opcional)" />
                <x-text-input
                    id="CDMATRFUNCIONARIO"
                    name="CDMATRFUNCIONARIO"
                    type="text"
                    class="mt-1 block w-full dark:bg-slate-900 dark:text-slate-100 dark:border-slate-600"
                    x-model="matricula"
                    autofocus
                    @blur="onMatriculaBlur"
                    @input="onMatriculaInput" />
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400" x-show="matriculaExiste">✅ Matrícula existe. Nome preenchido.</p>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400" x-show="!matricula">Será gerada uma temporária se vazio.</p>
            </div>

            <div class="xl:col-span-5">
                <x-input-label for="NOMEUSER" value="Nome completo *" />
                <x-text-input
                    id="NOMEUSER"
                    name="NOMEUSER"
                    type="text"
                    class="mt-1 block w-full transition-colors dark:bg-slate-900 dark:text-slate-100 dark:border-slate-600"
                    x-model="nome"
                    x-bind:readonly="nomeBloqueado"
                    x-bind:class="nomeBloqueado ? 'bg-sky-50 dark:bg-sky-900/20 cursor-not-allowed ring-1 ring-sky-200 dark:ring-sky-700 border-sky-200 dark:border-sky-700 opacity-80' : ''"
                    x-bind:required="nameRequired" />
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400" x-show="isPlaceholderMatricula()">Usuário completa no primeiro acesso.</p>
            </div>

            <div class="xl:col-span-4">
                <x-input-label for="NMLOGIN" value="Login de acesso *" />
                <x-text-input
                    id="NMLOGIN"
                    name="NMLOGIN"
                    type="text"
                    class="mt-1 block w-full font-mono transition-colors dark:bg-slate-900 dark:text-slate-100 dark:border-slate-600"
                    x-model="login"
                    required
                    @input="onLoginTyping"
                    x-bind:class="[
                        login ? (loginDisponivel ? 'ring-1 ring-green-200 dark:ring-green-700 border-green-200 dark:border-green-700' : 'ring-1 ring-red-200 dark:ring-red-700 border-red-200 dark:border-red-700') : '',
                        loginAuto ? 'bg-sky-50 dark:bg-sky-900/20' : ''
                    ].join(' ')" />
                <p class="mt-1.5 text-xs font-medium" :class="loginDisponivel ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="loginHint"></p>
            </div>

            <div class="xl:col-span-4 mt-2">
                <x-input-label for="PERFIL" value="Perfil *" />
                <select
                    id="PERFIL"
                    name="PERFIL"
                    x-model="perfil"
                    class="mt-1 block w-full mb-1 rounded-md border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-sm focus:border-violet-400 focus:ring-violet-400 dark:focus:border-violet-500 dark:focus:ring-violet-500"
                    required>
                    <option value="USR" @selected(old('PERFIL', $usuario->PERFIL ?? '') == 'USR')>Usuário padrão</option>
                    <option value="C" @selected(old('PERFIL', $usuario->PERFIL ?? '') == 'C')>Consultor (somente leitura)</option>
                    <option value="ADM" @selected(old('PERFIL', $usuario->PERFIL ?? '') == 'ADM')>Administrador</option>
                </select>
                <div class="mt-2 rounded-lg bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700 p-2.5">
                    <p class="text-[11px] leading-tight text-slate-500 dark:text-slate-400">
                        <span x-show="perfil === 'USR'"><strong>Padrão:</strong> Acesso definido pelas permissões de tela abaixo.</span>
                        <span x-show="perfil === 'C'"><strong>Consultor:</strong> Somente leitura, respeitando as telas liberadas.</span>
                        <span x-show="perfil === 'ADM'"><strong>Admin:</strong> Acesso total ao sistema independentemente das marcações.</span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    @if($canGrantTelas)
    <section class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm mt-5">
        <div class="mb-4 flex flex-col gap-1 md:flex-row md:items-start md:justify-between border-b border-slate-100 dark:border-slate-700 pb-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Comportamento de acesso</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Use estas opções para controlar o que o usuário precisa revisar no próximo login.</p>
            </div>
            <div class="rounded-full bg-slate-100 dark:bg-slate-700 px-3 py-1 text-xs font-medium text-slate-600 dark:text-slate-300">
                Ajustes de cadastro
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-5 py-4 transition-colors hover:border-violet-200 dark:hover:border-violet-800">
            <div class="flex items-start gap-3">
                <input type="hidden" name="needs_identity_update" value="0">
                <div class="flex items-center h-5 mt-0.5">
                    <input
                        id="needs_identity_update"
                        name="needs_identity_update"
                        value="1"
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-violet-600 focus:ring-violet-500 dark:focus:ring-offset-slate-900 transition-colors"
                        x-model="needsIdentityUpdate">
                </div>
                <div>
                    <label for="needs_identity_update" class="text-sm font-medium text-slate-800 dark:text-slate-200 cursor-pointer">
                        Exigir atualização do cadastro no próximo login
                    </label>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 cursor-pointer" @click="needsIdentityUpdate = !needsIdentityUpdate">
                        Ative quando o usuário precisar revisar nome, matrícula ou outros dados logo no primeiro acesso.
                    </p>
                </div>
            </div>
        </div>
    </section>
    @endif

    @if($canGrantTelas)
    <div class="mt-5">
        <x-permissions-section
            title="Acessos gerais do sistema"
            description="Permissões fora do fluxo de Solicitações de Bens, como Patrimônio, gráficos e histórico principal."
            badge="Admin">

            @if(isset($usuario) && ($usuario->PERFIL ?? '') === 'ADM')
            <div class="mt-3 rounded-xl border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20 px-4 py-3 text-xs text-violet-700 dark:text-violet-300">
                Este usuário está com perfil Administrador. As marcações abaixo servem apenas como referência, porque o acesso total já está liberado.
            </div>
            @endif

            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" @click.prevent="marcarTodas('principais')" class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 transition hover:border-violet-300 dark:hover:border-violet-500 hover:bg-violet-50 dark:hover:bg-violet-900/30">
                    Marcar todas principais
                </button>
                <button type="button" @click.prevent="desmarcarTodas('principais')" class="rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 transition hover:border-violet-300 dark:hover:border-violet-500 hover:bg-violet-50 dark:hover:bg-violet-900/30">
                    Desmarcar todas
                </button>
            </div>

            <div class="mt-4" id="telas-principais-container">
                @if($telasPrincipais->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400">Nenhuma tela principal foi encontrada em `acessotela`.</p>
                @else
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @if($telaPatrimonio)
                    @php
                        $selecionada = in_array($telaObrigatoria, $telasSelecionadas, true);
                        $nomePatrimonio = $telaPatrimonio->DETELA ?? 'Controle de Patrimônio';
                    @endphp
                    <x-permission-checkbox
                        :checked="$selecionada"
                        :disabled="true"
                        value="{{ $telaObrigatoria }}"
                        title="{{ $nomePatrimonio }}"
                        subtitle="Acesso base obrigatório para o módulo principal de patrimônio."
                        meta="Códigos: 1000 e 1006 | Obrigatória" />
                    <input type="hidden" name="telas[]" value="1006">
                    @endif

                    @foreach($telasPrincipais as $tela)
                    @php
                        $selecionada = in_array((int) $tela->NUSEQTELA, $telasSelecionadas, true);
                        $meta = 'Código: ' . $tela->NUSEQTELA . (!empty($tela->NMSISTEMA) ? ' | Sistema: ' . $tela->NMSISTEMA : '');
                    @endphp
                    <x-permission-checkbox
                        :checked="$selecionada"
                        value="{{ $tela->NUSEQTELA }}"
                        title="{{ $tela->DETELA }}"
                        subtitle="Libera acesso a esta área do sistema."
                        meta="{{ $meta }}" />
                    @endforeach
                </div>
                @endif

                @error('telas')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </x-permissions-section>
    </div>
    @endif

    @if($telasSolicitacoes->isNotEmpty())
    <div class="mt-5">
        <x-permissions-section
            title="Solicitações de Bens"
            description="Aqui você define quem cria, quem analisa, quem libera e quem registra o envio das solicitações."
            badge="Fluxo operacional">

            <div class="grid grid-cols-1 gap-3 lg:grid-cols-4 mt-4">
                <div class="rounded-xl border border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-900/20 px-4 py-3">
                    <div class="text-sm font-semibold text-sky-900 dark:text-sky-300">Solicitante</div>
                    <p class="mt-1 text-xs text-sky-700 dark:text-sky-400">Cria o pedido e acompanha o recebimento no fim do fluxo.</p>
                </div>
                <div class="rounded-xl border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20 px-4 py-3">
                    <div class="text-sm font-semibold text-violet-900 dark:text-violet-300">Triagem (Theo)</div>
                    <p class="mt-1 text-xs text-violet-700 dark:text-violet-400">Faz a triagem inicial e envia a solicitação para Em Análise.</p>
                </div>
                <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 px-4 py-3">
                    <div class="text-sm font-semibold text-emerald-900 dark:text-emerald-300">Operação</div>
                    <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-400">Acompanham o processo, atuam em Em Análise e registram envio.</p>
                </div>
                <div class="rounded-xl border border-fuchsia-200 dark:border-fuchsia-800 bg-fuchsia-50 dark:bg-fuchsia-900/20 px-4 py-3">
                    <div class="text-sm font-semibold text-fuchsia-900 dark:text-fuchsia-300">Liberação (Bruno)</div>
                    <p class="mt-1 text-xs text-fuchsia-700 dark:text-fuchsia-400">Conclui a etapa de Liberação antes do pedido ir para Envio.</p>
                </div>
            </div>

            <div class="mt-4 rounded-2xl border border-violet-200 dark:border-violet-800 bg-violet-50/70 dark:bg-violet-900/10 p-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="text-sm font-semibold text-violet-900 dark:text-violet-300">Modelos rápidos de permissão</div>
                        <p class="mt-1 text-xs text-violet-700 dark:text-violet-400">Use estes atalhos para marcar o conjunto mais comum de permissões por papel.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click.prevent="aplicarPerfilSolicitante()" class="rounded-lg border border-sky-200 dark:border-sky-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-medium text-sky-700 dark:text-sky-300 transition hover:bg-sky-50 dark:hover:bg-sky-900/30">
                            Solicitante
                        </button>
                        <button type="button" @click.prevent="aplicarPerfilSupervisor()" class="rounded-lg border border-violet-200 dark:border-violet-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-medium text-violet-700 dark:text-violet-300 transition hover:bg-violet-50 dark:hover:bg-violet-900/30">
                            Triagem
                        </button>
                        <button type="button" @click.prevent="aplicarPerfilOperacao()" class="rounded-lg border border-emerald-200 dark:border-emerald-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-300 transition hover:bg-emerald-50 dark:hover:bg-emerald-900/30">
                            Operação
                        </button>
                        <button type="button" @click.prevent="aplicarPerfilLiberacao()" class="rounded-lg border border-fuchsia-200 dark:border-fuchsia-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-medium text-fuchsia-700 dark:text-fuchsia-300 transition hover:bg-fuchsia-50 dark:hover:bg-fuchsia-900/30">
                            Liberação
                        </button>
                    </div>
                </div>
            </div>

        <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-12">
            <div class="xl:col-span-4">
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-5">
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">1. Chave de acesso do módulo</div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Libere primeiro a entrada em Solicitações de Bens. Sem isso, as demais permissões não funcionam.</p>

                    @if($telaSolicitacoesPrincipal)
                    @php
                        $selecionada = in_array((int) $telaSolicitacoesPrincipal->NUSEQTELA, $telasSelecionadas, true);
                        $dadosTelaPrincipal = $descreverTelaSolicitacao($telaSolicitacoesPrincipal);
                    @endphp
                    <div class="mt-4">
                        <x-permission-checkbox
                            :checked="$selecionada"
                            value="{{ $telaSolicitacoesPrincipal->NUSEQTELA }}"
                            title="{{ $dadosTelaPrincipal['titulo'] }}"
                            subtitle="{{ $dadosTelaPrincipal['descricao'] }}"
                            meta="{{ $dadosTelaPrincipal['meta'] }}" />
                    </div>
                    @endif
                </div>
            </div>

            <div class="xl:col-span-8 space-y-4">
                <div x-show="!hasTela(1010)" x-cloak class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-5 py-4 text-sm text-amber-800 dark:text-amber-300">
                    <div class="flex items-center gap-3">
                        <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Ative a chave de acesso ao lado para liberar a configuração detalhada do fluxo de Solicitações de Bens.</span>
                    </div>
                </div>

                <div x-show="hasTela(1010)" x-cloak class="space-y-4">
                    @if($telasSolicitacoesBase->isNotEmpty())
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/50 p-5">
                        <div class="flex flex-col sm:flex-row items-start justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
                            <div>
                                <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">2. Solicitação inicial</div>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Permissões para quem abre o pedido no sistema.</p>
                            </div>
                            <div class="rounded-full bg-sky-50 dark:bg-sky-900/30 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-400">Início</div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 2xl:grid-cols-3">
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
                    @endif

                    @if($telasSolicitacoesTriagem->isNotEmpty())
                    <div class="rounded-2xl border border-violet-200 dark:border-violet-800/50 bg-violet-50/40 dark:bg-violet-900/10 p-5">
                        <div class="flex flex-col sm:flex-row items-start justify-between gap-3 border-b border-violet-100 dark:border-violet-800/30 pb-3 mb-4">
                            <div>
                                <div class="text-sm font-semibold text-violet-900 dark:text-violet-200">3. Triagem inicial</div>
                                <p class="mt-1 text-xs text-violet-700 dark:text-violet-400">Use para quem decide se o pedido segue do status Solicitado para Em Análise.</p>
                            </div>
                            <div class="rounded-full bg-white dark:bg-violet-900/40 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">Triagem</div>
                        </div>
                        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 2xl:grid-cols-3">
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
                    @endif

                    @if($telasSolicitacoesOperacao->isNotEmpty())
                    <div class="rounded-2xl border border-emerald-200 dark:border-emerald-800/50 bg-emerald-50/40 dark:bg-emerald-900/10 p-5">
                        <div class="flex flex-col sm:flex-row items-start justify-between gap-3 border-b border-emerald-100 dark:border-emerald-800/30 pb-3 mb-4">
                            <div>
                                <div class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">4. Em Análise e envio</div>
                                <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-400">Estas permissões servem para quem toca o pedido no meio do fluxo e registra o envio com rastreio.</p>
                            </div>
                            <div class="rounded-full bg-white dark:bg-emerald-900/40 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Beatriz / Tiago</div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 2xl:grid-cols-3">
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
                    @endif

                    @if($telasSolicitacoesLiberacao->isNotEmpty())
                    <div class="rounded-2xl border border-fuchsia-200 dark:border-fuchsia-800/50 bg-fuchsia-50/40 dark:bg-fuchsia-900/10 p-5">
                        <div class="flex flex-col sm:flex-row items-start justify-between gap-3 border-b border-fuchsia-100 dark:border-fuchsia-800/30 pb-3 mb-4">
                            <div>
                                <div class="text-sm font-semibold text-fuchsia-900 dark:text-fuchsia-200">5. Liberação final</div>
                                <p class="mt-1 text-xs text-fuchsia-700 dark:text-fuchsia-400">Use para quem conclui a etapa de Liberação antes de o pedido seguir para Envio.</p>
                            </div>
                            <div class="rounded-full bg-white dark:bg-fuchsia-900/40 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-fuchsia-700 dark:text-fuchsia-300">Bruno</div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 2xl:grid-cols-3">
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
                    @endif

                    @if($telasSolicitacoesVisibilidade->isNotEmpty())
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-5">
                        <div class="flex flex-col sm:flex-row items-start justify-between gap-3 border-b border-slate-200 dark:border-slate-700 pb-3 mb-4">
                            <div>
                                <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">6. Visibilidade e histórico</div>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Aqui ficam as permissões de enxergar pedidos, limitar visualização e acessar o histórico completo.</p>
                            </div>
                            <div class="rounded-full bg-white dark:bg-slate-900 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Apoio</div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 2xl:grid-cols-3">
                            @foreach($telasSolicitacoesVisibilidade as $tela)
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
        </div>
    </x-permissions-section>
    </div>
    @endif

    @if($telasEspeciais->isNotEmpty())
    <div class="mt-5">
        <x-permissions-section
            title="Permissões especiais"
            description="Telas administrativas e operacionais adicionais que não fazem parte do fluxo principal acima."
            badge="Admin">
            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                @foreach($telasEspeciais as $tela)
                @php
                    $selecionada = in_array((int) $tela->NUSEQTELA, $telasSelecionadas, true);
                    $meta = 'Código: ' . $tela->NUSEQTELA . (!empty($tela->NMSISTEMA) ? ' | Sistema: ' . $tela->NMSISTEMA : '');
                @endphp
                <x-permission-checkbox
                    :checked="$selecionada"
                    value="{{ $tela->NUSEQTELA }}"
                    title="{{ $tela->DETELA }}"
                    subtitle="Permissão administrativa ou operacional fora dos grupos principais."
                    meta="{{ $meta }}" />
                @endforeach
            </div>
        </x-permissions-section>
    </div>
    @endif

    <section class="mt-5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-5 shadow-sm">
        <div class="mb-4 border-b border-slate-100 dark:border-slate-700 pb-3">
            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Segurança</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Defina ou renove a senha de acesso do usuário.</p>
        </div>

        @if(isset($usuario))
        <div class="max-w-md">
            <x-input-label for="SENHA" value="Nova senha" />
            <p class="mb-2 text-xs text-slate-500 dark:text-slate-400">Deixe em branco para manter a senha atual.</p>
            <x-text-input id="SENHA" name="SENHA" type="password" class="block w-full dark:bg-slate-900 dark:text-slate-100 dark:border-slate-600" />
        </div>
        @else
        <div class="max-w-xl">
            <x-input-label value="Senha provisória" />
            <div class="mt-2 rounded-xl border border-dashed border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900/50 px-5 py-4 text-sm text-slate-600 dark:text-slate-300">
                Uma senha inicial aleatória será gerada automaticamente, por exemplo <span class="font-mono font-semibold text-slate-800 dark:text-slate-100">Plansul@123456</span>.<br>
                Você verá a senha após o cadastro e deverá repassá-la ao usuário. No primeiro acesso, ele deverá trocá-la obrigatóriamente.
            </div>
        </div>
        @endif
    </section>

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
                solicitacoesPrincipal: 1010,
                solicitacoesDependentes: @js($telasSolicitacoesDependentes),
                init() {
                    this.registrarWatchers();
                    this.registrarEventosPermissoes();
                    this.syncPermissoesSolicitacoes();
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
                    [1011, 1016, 1020].forEach((codigo) => this.setTela(codigo, true));
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
                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
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

<div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-200 dark:border-slate-800 pt-5">
    <a href="{{ route('usuarios.index') }}" class="text-sm font-medium text-slate-600 dark:text-slate-400 transition-colors hover:text-slate-900 dark:hover:text-slate-200 hover:underline">
        Cancelar
    </a>
    <x-primary-button @click="validateForm() && $el.closest('form').submit()" class="px-5 py-2.5">
        {{ isset($usuario) ? 'Atualizar usuário' : 'Criar usuário' }}
    </x-primary-button>
</div>
