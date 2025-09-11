<div>
    <x-input-label for="CDPROJETO" value="Código do Projeto *" />
    <x-text-input id="CDPROJETO" name="CDPROJETO" type="number" class="mt-1 block w-full" :value="old('CDPROJETO', $projeto->CDPROJETO ?? '')" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('CDPROJETO')" />
</div>

<div class="mt-4">
    <x-input-label for="NOMEPROJETO" value="Nome do Projeto *" />
    <x-text-input id="NOMEPROJETO" name="NOMEPROJETO" type="text" class="mt-1 block w-full" :value="old('NOMEPROJETO', $projeto->NOMEPROJETO ?? '')" required />
    <x-input-error class="mt-2" :messages="$errors->get('NOMEPROJETO')" />
</div>

<div class="mt-4">
    <x-input-label for="LOCAL" value="Filial (Local) *" />
    <x-text-input id="LOCAL" name="LOCAL" type="text" class="mt-1 block w-full" :value="old('LOCAL', $projeto->LOCAL ?? '')" required />
    <x-input-error class="mt-2" :messages="$errors->get('LOCAL')" />
</div>