@php
    $tipoLocalAtual = old('tipo_local', $local->tipo_local_normalizado ?? \App\Models\LocalProjeto::TIPO_LOCAL_PADRAO);
    $fluxoAtual = old('fluxo_responsavel', $local->fluxo_responsavel_normalizado ?? \App\Models\LocalProjeto::FLUXO_RESPONSAVEL_PADRAO);
@endphp

@if ($errors->any())
<div class="mb-4 text-sm text-red-600 dark:text-red-400">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-6" x-data="projetoLocalForm()">
    <div class="md:col-span-1">
        <label for="cdlocal" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Código do Local</label>
        <input id="cdlocal" name="cdlocal" type="number" @if(session('duplicating_mode')) readonly class="opacity-70 cursor-not-allowed mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 rounded-md shadow-sm" @else class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" @endif
            value="{{ old('cdlocal', $local->cdlocal ?? '') }}" required @input.debounce.400ms="buscarPorCodigo">
        @if(session('duplicating_mode'))
        <p class="text-xs text-gray-500 mt-1">Código bloqueado durante clonagem.</p>
        @else
        <p class="text-xs text-gray-500 mt-1">Digite o código para carregar nome e projeto, se já existir.</p>
        @endif
    </div>

    <div class="md:col-span-1">
        <label for="tabfant_id" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Projeto Associado</label>
        @if(session('duplicating_mode') && session('duplicating_project'))
        @php
        $dup = session('duplicating_project');
        $dupNome = data_get($dup, 'NOMEPROJETO');
        $dupCod = data_get($dup, 'CDPROJETO');
        $dupId = data_get($dup, 'id');
        @endphp
        <div class="mt-1 block w-full rounded-md shadow-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 p-2">
            <strong>{{ $dupNome }}</strong> (Cód: {{ $dupCod }})
        </div>
        <input type="hidden" name="tabfant_id" value="{{ old('tabfant_id', $local->tabfant_id ?? $dupId) }}">
        <p class="text-xs text-gray-500 mt-1">Projeto associado bloqueado durante clonagem.</p>
        @else
        <select id="tabfant_id" name="tabfant_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" required>
            <option value="">Selecione um projeto</option>
            @foreach ($projetos as $projeto_opcao)
            <option value="{{ $projeto_opcao->id }}" @if(old('tabfant_id', $local->tabfant_id ?? '') == $projeto_opcao->id) selected @endif>
                {{ $projeto_opcao->NOMEPROJETO }} (Cód: {{ $projeto_opcao->CDPROJETO }})
            </option>
            @endforeach
        </select>
        @endif
    </div>

    <div class="md:col-span-1">
        <label for="delocal" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nome do Local</label>
        <input id="delocal" name="delocal" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
            value="{{ old('delocal', $local->delocal ?? '') }}" required>
    </div>

    <div class="md:col-span-1">
        <label for="tipo_local" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Tipo do Local</label>
        <select id="tipo_local" name="tipo_local" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
            @foreach (\App\Models\LocalProjeto::tipoLocalOptions() as $value => $label)
            <option value="{{ $value }}" @selected($tipoLocalAtual === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="text-xs text-gray-500 mt-1">Use <strong>Estoque TI</strong> para itens disponíveis e <strong>TI em uso</strong> para equipamentos já alocados.</p>
    </div>

    <div class="md:col-span-1">
        <label for="fluxo_responsavel" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Fluxo Responsável</label>
        <select id="fluxo_responsavel" name="fluxo_responsavel" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
            @foreach (\App\Models\LocalProjeto::fluxoResponsavelOptions() as $value => $label)
            <option value="{{ $value }}" @selected($fluxoAtual === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="text-xs text-gray-500 mt-1">Quando marcado como <strong>TI</strong>, as solicitações desse local entram na fila TI.</p>
    </div>

    <div class="md:col-span-1 flex items-end">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="flativo" value="1" class="rounded border-gray-300 dark:border-gray-700" @checked(old('flativo', $local->flativo ?? true))>
            Local ativo
        </label>
    </div>

    <div class="md:col-span-3" x-show="carregandoBusca">
        <p class="text-xs text-gray-500">Buscando dados do código...</p>
    </div>
    <div class="md:col-span-3" x-show="mensagemBusca" x-text="mensagemBusca" class="text-xs"></div>
</div>

@push('scripts')
<script>
    function projetoLocalForm() {
        return {
            carregandoBusca: false,
            mensagemBusca: '',
            async buscarPorCodigo(e) {
                const codigo = e.target.value.trim();
                const duplicando = document.getElementById('cdlocal')?.readOnly;
                if (!codigo || duplicando) {
                    return;
                }

                this.mensagemBusca = '';
                this.carregandoBusca = true;

                try {
                    const resp = await fetch(`{{ route('projetos.lookup') }}?cdlocal=${encodeURIComponent(codigo)}`);
                    if (!resp.ok) throw new Error('Erro na consulta');

                    const data = await resp.json();
                    if (data.found) {
                        document.getElementById('delocal').value = data.local.delocal;

                        const selectProjeto = document.getElementById('tabfant_id');
                        if (selectProjeto && data.local.tabfant_id) {
                            selectProjeto.value = data.local.tabfant_id;
                        }

                        const tipoLocal = document.getElementById('tipo_local');
                        if (tipoLocal && data.local.tipo_local) {
                            tipoLocal.value = data.local.tipo_local;
                        }

                        const fluxoResponsavel = document.getElementById('fluxo_responsavel');
                        if (fluxoResponsavel && data.local.fluxo_responsavel) {
                            fluxoResponsavel.value = data.local.fluxo_responsavel;
                        }

                        this.mensagemBusca = 'Dados preenchidos a partir de local existente.';
                    } else {
                        this.mensagemBusca = 'Código livre. Informe nome, projeto e comportamento do local.';
                    }
                } catch (err) {
                    console.error(err);
                    this.mensagemBusca = 'Erro ao buscar código.';
                } finally {
                    this.carregandoBusca = false;
                }
            }
        }
    }
</script>
@endpush
