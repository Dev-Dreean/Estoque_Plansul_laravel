{{-- Este código deve estar no arquivo resources/views/projetos/_form.blade.php --}}

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
        {{-- Em modo duplicação: exibir projeto associado como texto e enviar tabfant_id via hidden --}}
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
        @if(session('duplicating_mode'))
        <p class="text-xs text-blue-500 mt-1">Selecione o projeto para o novo local clonado.</p>
        @endif
        @endif
    </div>

    <div class="md:col-span-1">
        <label for="delocal" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nome do Local</label>
        <input id="delocal" name="delocal" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm"
            value="{{ old('delocal', $local->delocal ?? '') }}" required>
        @if(session('duplicating_mode'))
        <p class="text-xs text-blue-500 mt-1">Informe o novo nome para este local.</p>
        @endif
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
                if (!codigo || duplicando) { // não busca em modo duplicação
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
                        const select = document.getElementById('tabfant_id');
                        if (data.local.tabfant_id) {
                            select.value = data.local.tabfant_id;
                        }
                        this.mensagemBusca = 'Dados preenchidos a partir de local existente.';
                    } else {
                        this.mensagemBusca = 'Código livre. Informe nome e projeto.';
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