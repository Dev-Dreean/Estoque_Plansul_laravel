@php
    $isModal = request('modal') === '1';
    $containerClass = $isModal ? 'p-4 sm:p-5' : 'py-12';
    $wrapperClass = $isModal ? 'w-full' : 'max-w-6xl mx-auto sm:px-6 lg:px-8';
@endphp

@unless($isModal)
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Solicitacao #') }}{{ $solicitacao->id }}
            </h2>
            <a href="{{ route('solicitacoes-bens.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Voltar</a>
        </div>
    </x-slot>
@endunless

    @php
        $statusColors = [
            'PENDENTE' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            'SEPARADO' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'CONCLUIDO' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'CANCELADO' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        ];
        $matriculaOld = old('matricula_recebedor', $solicitacao->matricula_recebedor ?? '');
        $nomeOld = old('nome_recebedor', $solicitacao->nome_recebedor ?? '');
        $lookupOnInit = trim((string) $nomeOld) === '' && trim((string) $matriculaOld) !== '';
    @endphp

    <div class="{{ $containerClass }}">
        <div class="{{ $wrapperClass }}">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
                    <span class="font-semibold">Sucesso:</span> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
                    <span class="font-semibold">Erro:</span> {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
                    <span class="font-semibold">Erro:</span> {{ $errors->first() }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold">Detalhes da solicitacao</h3>
                                <x-status-badge :status="$solicitacao->status" :color-map="$statusColors" />
                            </div>
                            <div class="grid gap-4 md:grid-cols-2 text-sm">
                                <div>
                                    <div class="text-xs text-gray-500">Solicitante</div>
                                    <div class="font-medium">{{ $solicitacao->solicitante_nome ?? '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ $solicitacao->solicitante_matricula ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Setor</div>
                                    <div class="font-medium">{{ $solicitacao->setor ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Projeto</div>
                                    <div class="font-medium">{{ $solicitacao->projeto?->NOMEPROJETO ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Local destino</div>
                                    <div class="font-medium">{{ $solicitacao->local_destino ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">UF</div>
                                    <div class="font-medium">{{ $solicitacao->uf ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Criado em</div>
                                    <div class="font-medium">{{ optional($solicitacao->created_at)->format('d/m/Y H:i') }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Separado em</div>
                                    <div class="font-medium">{{ $solicitacao->separado_em ? $solicitacao->separado_em->format('d/m/Y H:i') : '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Concluido em</div>
                                    <div class="font-medium">{{ $solicitacao->concluido_em ? $solicitacao->concluido_em->format('d/m/Y H:i') : '-' }}</div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <div class="text-sm font-semibold mb-2">Observacao do solicitante</div>
                                <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                                    {{ $solicitacao->observacao ?: '-' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            <h3 class="text-lg font-semibold mb-4">Itens solicitados</h3>
                            <div class="relative overflow-x-auto shadow-sm sm:rounded-lg">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th class="px-4 py-2">Descricao</th>
                                            <th class="px-4 py-2">Quantidade</th>
                                            <th class="px-4 py-2">Unidade</th>
                                            <th class="px-4 py-2">Observacao</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($solicitacao->itens as $item)
                                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $item->descricao }}</td>
                                                <td class="px-4 py-2">{{ $item->quantidade }}</td>
                                                <td class="px-4 py-2">{{ $item->unidade ?: '-' }}</td>
                                                <td class="px-4 py-2">{{ $item->observacao ?: '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-4 py-4 text-center text-gray-500">Nenhum item informado.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100" x-data="matriculaLookup({
                            matriculaOld: @js($matriculaOld),
                            nomeOld: @js($nomeOld),
                            lookupOnInit: @js($lookupOnInit)
                        })" x-init="initLookup()">
                            <h3 class="text-lg font-semibold mb-4">Atualizar solicitacao</h3>

                            <form method="POST" action="{{ route('solicitacoes-bens.update', $solicitacao) }}">
                                @csrf
                                @method('PATCH')

                                <div class="space-y-4">
                                    <div>
                                        <x-input-label for="status" value="Status" />
                                        <select id="status" name="status" class="input-base mt-1 block w-full">
                                            @foreach($statusOptions as $status)
                                                <option value="{{ $status }}" @selected(old('status', $solicitacao->status) === $status)>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="local_destino" value="Local destino" />
                                        <x-text-input id="local_destino" name="local_destino" type="text" class="mt-1 block w-full" value="{{ old('local_destino', $solicitacao->local_destino) }}" />
                                        <x-input-error :messages="$errors->get('local_destino')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="matricula_recebedor" value="Matricula recebedor" />
                                        <x-text-input id="matricula_recebedor" name="matricula_recebedor" type="text" class="mt-1 block w-full" value="{{ $matriculaOld }}" x-model="matricula" @blur="onMatriculaBlur" @input="onMatriculaInput" />
                                        <p class="text-xs text-gray-500 mt-1" x-show="matriculaExiste">Matricula encontrada. Nome preenchido automaticamente.</p>
                                        <x-input-error :messages="$errors->get('matricula_recebedor')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="nome_recebedor" value="Nome recebedor" />
                                        <x-text-input id="nome_recebedor" name="nome_recebedor" type="text" class="mt-1 block w-full" value="{{ $nomeOld }}" x-model="nome" x-bind:readonly="nomeBloqueado"
                                            x-bind:class="nomeBloqueado ? 'bg-blue-50 dark:bg-blue-900/20 cursor-not-allowed ring-1 ring-blue-300/50 border-blue-300/60' : ''" />
                                        <x-input-error :messages="$errors->get('nome_recebedor')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="observacao_controle" value="Observacao do controle" />
                                        <textarea id="observacao_controle" name="observacao_controle" class="input-base mt-1 block w-full" rows="4">{{ old('observacao_controle', $solicitacao->observacao_controle) }}</textarea>
                                        <x-input-error :messages="$errors->get('observacao_controle')" class="mt-2" />
                                    </div>
                                </div>

                                <div class="mt-6 flex items-center gap-3">
                                    <x-primary-button>Salvar atualizacao</x-primary-button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($isModal)
        <script>
            function matriculaLookup({ matriculaOld, nomeOld, lookupOnInit }) {
                return {
                    matricula: matriculaOld || '',
                    nome: nomeOld || '',
                    lookupOnInit: !!lookupOnInit,
                    matriculaExiste: false,
                    nomeBloqueado: false,
                    initLookup() {
                        if (this.lookupOnInit && this.matricula) {
                            this.lookupMatricula(this.matricula);
                        }
                    },
                    onMatriculaInput(e) {
                        const val = (e?.target?.value ?? '').trim();
                        if (val === '') {
                            this.matriculaExiste = false;
                            this.nomeBloqueado = false;
                            this.nome = '';
                        }
                    },
                    async onMatriculaBlur() {
                        const mat = (this.matricula || '').trim();
                        if (!mat) return;
                        await this.lookupMatricula(mat);
                    },
                    async lookupMatricula(mat) {
                        try {
                            const url = `{{ route('api.usuarios.porMatricula') }}?matricula=${encodeURIComponent(mat)}`;
                            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                            if (!res.ok) throw new Error('Falha busca matricula');
                            const data = await res.json();
                            this.matriculaExiste = !!data?.exists;
                            if (data?.exists && data?.nome) {
                                this.nome = data.nome;
                                this.nomeBloqueado = true;
                            } else {
                                this.nomeBloqueado = false;
                            }
                        } catch (e) {
                            console.warn('Lookup matricula falhou', e);
                        }
                    }
                };
            }
        </script>
    @else
        @push('scripts')
            <script>
                function matriculaLookup({ matriculaOld, nomeOld, lookupOnInit }) {
                    return {
                        matricula: matriculaOld || '',
                        nome: nomeOld || '',
                        lookupOnInit: !!lookupOnInit,
                        matriculaExiste: false,
                        nomeBloqueado: false,
                        initLookup() {
                            if (this.lookupOnInit && this.matricula) {
                                this.lookupMatricula(this.matricula);
                            }
                        },
                        onMatriculaInput(e) {
                            const val = (e?.target?.value ?? '').trim();
                            if (val === '') {
                                this.matriculaExiste = false;
                                this.nomeBloqueado = false;
                                this.nome = '';
                            }
                        },
                        async onMatriculaBlur() {
                            const mat = (this.matricula || '').trim();
                            if (!mat) return;
                            await this.lookupMatricula(mat);
                        },
                        async lookupMatricula(mat) {
                            try {
                                const url = `{{ route('api.usuarios.porMatricula') }}?matricula=${encodeURIComponent(mat)}`;
                                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                                if (!res.ok) throw new Error('Falha busca matricula');
                                const data = await res.json();
                                this.matriculaExiste = !!data?.exists;
                                if (data?.exists && data?.nome) {
                                    this.nome = data.nome;
                                    this.nomeBloqueado = true;
                                } else {
                                    this.nomeBloqueado = false;
                                }
                            } catch (e) {
                                console.warn('Lookup matricula falhou', e);
                            }
                        }
                    };
                }
            </script>
        @endpush
    @endif
@unless($isModal)
</x-app-layout>
@endunless
