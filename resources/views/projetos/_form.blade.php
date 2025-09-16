<div>
    <x-input-label for="CDPROJETO" value="Código do Projeto *" />
    <x-text-input id="CDPROJETO" name="CDPROJETO" type="number" class="mt-1 block w-full" :value="old('CDPROJETO', $projeto->CDPROJETO ?? '')" required />
    <x-input-error class="mt-2" :messages="$errors->get('CDPROJETO')" />
</div>

<div class="mt-4">
    <x-input-label for="NOMEPROJETO" value="Nome do Projeto *" />
    <x-text-input id="NOMEPROJETO" name="NOMEPROJETO" type="text" class="mt-1 block w-full" :value="old('NOMEPROJETO', $projeto->NOMEPROJETO ?? '')" required />
    <div class="flex items-center text-xs mt-1">
        <span id="nome-autofill-badge" class="hidden font-semibold text-[11px] text-red-600 dark:text-red-400">Projeto já cadastrado</span>
    </div>
    <x-input-error class="mt-2" :messages="$errors->get('NOMEPROJETO')" />
</div>

<div class="mt-4">
    <x-input-label for="LOCAL" value="Filial (Local) *" />
    <x-text-input id="LOCAL" name="LOCAL" type="text" class="mt-1 block w-full" :value="old('LOCAL', $projeto->LOCAL ?? '')" required />
    <x-input-error class="mt-2" :messages="$errors->get('LOCAL')" />
</div>

@push('scripts')
<script>
    (function() {
        const codigoInput = document.getElementById('CDPROJETO');
        const nomeInput = document.getElementById('NOMEPROJETO');
        const badge = document.getElementById('nome-autofill-badge');
        // No botão de desbloqueio: campo permanece travado quando já existe.
        const filialInput = document.getElementById('LOCAL');
        let lastCodigo = null;
        let debounceTimer = null;

        function showBadge() {
            badge.classList.remove('hidden');
        }

        function hideBadge() {
            badge.classList.add('hidden');
        }

        function fetchNome(codigo) {
            if (!codigo) {
                nomeInput.readOnly = false;
                hideBadge();
                return;
            }
            fetch(`/api/projetos/nome/${codigo}`)
                .then(r => r.ok ? r.json() : Promise.reject())
                .then(data => {
                    if (data.exists) {
                        nomeInput.value = data.nome;
                        nomeInput.readOnly = true; // trava definitivamente
                        nomeInput.classList.add('bg-gray-100', 'dark:bg-gray-700');
                        const isCreatePage = !document.querySelector('form[action*="update"]');
                        if (isCreatePage) showBadge();
                    } else {
                        // Código novo: permite digitar nome (ainda não cadastrado)
                        if (lastCodigo !== codigo && nomeInput.readOnly) {
                            nomeInput.value = '';
                        }
                        nomeInput.readOnly = false;
                        nomeInput.classList.remove('bg-gray-100', 'dark:bg-gray-700');
                        hideBadge();
                    }
                    lastCodigo = codigo;
                })
                .catch(() => {
                    nomeInput.readOnly = false;
                    nomeInput.classList.remove('bg-gray-100', 'dark:bg-gray-700');
                    hideBadge();
                });
        }

        function debounceFetch() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchNome(codigoInput.value.trim()), 400);
        }

        if (codigoInput) {
            codigoInput.addEventListener('input', debounceFetch);
            codigoInput.addEventListener('blur', () => fetchNome(codigoInput.value.trim()));
        }
        // Sem desbloqueio manual.

        // If page loaded with existing codigo (e.g., incluir filial) trigger fetch
        document.addEventListener('DOMContentLoaded', () => {
            const initialCodigo = codigoInput.value.trim();
            const url = new URL(window.location.href);
            const isFilialInclusion = url.searchParams.has('filial'); // modo inclusão filial via edit

            if (initialCodigo) {
                fetchNome(initialCodigo);
            }

            if (isFilialInclusion) {
                codigoInput.readOnly = true;
                nomeInput.readOnly = true;
                codigoInput.classList.add('bg-gray-100', 'text-yellow-600', 'dark:bg-gray-700', 'dark:text-yellow-400', 'cursor-not-allowed');
                nomeInput.classList.add('bg-gray-100', 'text-yellow-600', 'dark:bg-gray-700', 'dark:text-yellow-400', 'cursor-not-allowed');
                // Badge não aparece em modo filial
                hideBadge();
                setTimeout(() => {
                    if (filialInput) filialInput.focus();
                }, 50);
            }
        });
    })();
</script>
@endpush