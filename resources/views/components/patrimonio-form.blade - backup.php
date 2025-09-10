@props(['patrimonio' => null])

{{-- Adicionamos x-data e o listener @keydown para o Alpine.js controlar a navegação --}}
<div x-data="formNavigation()" @keydown.enter.prevent="focusNext($event.target)">

    {{-- Primeira Linha --}}
    <div class="grid grid-cols-3 gap-6">
        <div>
            <x-input-label for="NUPATRIMONIO" value="Nº Patrimônio *" />
            <x-text-input data-index="1" id="NUPATRIMONIO" name="NUPATRIMONIO" type="number" class="mt-1 block w-full" :value="old('NUPATRIMONIO', $patrimonio?->NUPATRIMONIO)" required />
        </div>
        <div>
            <x-input-label for="NUMOF" value="Nº OC" />
            <x-text-input data-index="2" id="NUMOF" name="NUMOF" type="number" class="mt-1 block w-full" :value="old('NUMOF', $patrimonio?->NUMOF)" />
        </div>
        <div>
            {{-- Campo extra sem legenda, pode ser usado para algo no futuro ou removido --}}
            <x-input-label for="campo_extra" value="-" />
            <x-text-input data-index="3" id="campo_extra" name="campo_extra" type="text" class="mt-1 block w-full" />
        </div>
    </div>

    {{-- Segunda Linha --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-6">
        <div class="md:col-span-1">
            <x-input-label for="CODOBJETO" value="Código *" />
            <x-text-input data-index="4" id="CODOBJETO" name="CODOBJETO" type="number" class="mt-1 block w-full" :value="old('CODOBJETO', $patrimonio?->CODOBJETO)" required />
        </div>
        <div class="md:col-span-3">
            <x-input-label for="DEPATRIMONIO" value="Descrição do Código" />
            <x-text-input data-index="5" id="DEPATRIMONIO" name="DEPATRIMONIO" type="text" class="mt-1 block w-full" :value="old('DEPATRIMONIO', $patrimonio?->DEPATRIMONIO)" required />
        </div>
    </div>

    {{-- Terceira Linha --}}
    <div class="mt-4">
        <x-input-label for="DEHISTORICO" value="Observação" />
        <textarea data-index="6" id="DEHISTORICO" name="DEHISTORICO" rows="2" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">{{ old('DEHISTORICO', $patrimonio?->DEHISTORICO) }}</textarea>
    </div>

    {{-- Quarta Linha --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
        <div>
            <x-input-label for="CDPROJETO" value="Projeto" />
            <div class="flex items-center space-x-2">
                <select data-index="7" id="CDPROJETO" name="CDPROJETO" class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                    <option value="">Selecione...</option>
                </select>
                <x-text-input data-index="8" type="text" class="mt-1 block w-full" placeholder="Nome do Projeto" readonly />
            </div>
        </div>
        <div>
            <x-input-label for="CDLOCAL" value="Local" />
            <div class="flex items-center space-x-2">
                <select data-index="9" id="CDLOCAL" name="CDLOCAL" class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                    <option value="">Selecione...</option>
                </select>
                <x-text-input data-index="10" type="text" class="mt-1 block w-full" placeholder="Nome do Local" readonly />
            </div>
        </div>
    </div>

    {{-- Linhas Finais --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <div>
            <x-input-label for="MARCA" value="Marca" />
            <x-text-input data-index="11" id="MARCA" name="MARCA" type="text" class="mt-1 block w-full" :value="old('MARCA', $patrimonio?->MARCA)" />
        </div>
        <div>
            <x-input-label for="MODELO" value="Modelo" />
            <x-text-input data-index="12" id="MODELO" name="MODELO" type="text" class="mt-1 block w-full" :value="old('MODELO', $patrimonio?->MODELO)" />
        </div>
        <div>
            <x-input-label for="SITUACAO" value="Situação *" />
            <select data-index="13" id="SITUACAO" name="SITUACAO" class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" required>
                <option value="EM USO" @selected(old('SITUACAO', $patrimonio?->SITUACAO) == 'EM USO')>EM USO</option>
                <option value="CONSERTO" @selected(old('SITUACAO', $patrimonio?->SITUACAO) == 'CONSERTO')>CONSERTO</option>
                <option value="BAIXA" @selected(old('SITUACAO', $patrimonio?->SITUACAO) == 'BAIXA')>BAIXA</option>
                <option value="À DISPOSIÇÃO" @selected(old('SITUACAO', $patrimonio?->SITUACAO) == 'À DISPOSIÇÃO')>À DISPOSIÇÃO</option>
            </select>
        </div>
    </div>
    
    {{-- Datas (última linha do formulário) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
        <div>
            <x-input-label for="DTAQUISICAO" value="Data de Aquisição" />
            <x-text-input data-index="14" id="DTAQUISICAO" name="DTAQUISICAO" type="date" class="mt-1 block w-full" :value="old('DTAQUISICAO', $patrimonio?->DTAQUISICAO)" />
        </div>
        <div>
            <x-input-label for="DTBAIXA" value="Data de Baixa" />
            <x-text-input data-index="15" id="DTBAIXA" name="DTBAIXA" type="date" class="mt-1 block w-full" :value="old('DTBAIXA', $patrimonio?->DTBAIXA)" />
        </div>
    </div>
</div>

{{-- Script do Alpine.js para a navegação com Enter --}}
<script>
    function formNavigation() {
        return {
            focusNext(currentElement) {
                // Acha todos os campos que podem receber foco
                const focusable = Array.from(currentElement.closest('form').querySelectorAll(
                    'input:not([readonly]):not([disabled]), select:not([readonly]):not([disabled]), textarea:not([readonly]):not([disabled])'
                ));
                
                const currentIndex = focusable.indexOf(currentElement);
                const nextElement = focusable[currentIndex + 1];

                if (nextElement) {
                    nextElement.focus();
                }
            }
        }
    }
</script>