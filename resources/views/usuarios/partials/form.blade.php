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

<div class="space-y-4">
    <div>
        <x-input-label for="NOMEUSER" value="Nome Completo *" />
        <x-text-input id="NOMEUSER" name="NOMEUSER" type="text" class="mt-1 block w-full" :value="old('NOMEUSER', $usuario ?? null)" required autofocus />
    </div>
    <div>
        <x-input-label for="NMLOGIN" value="Login de Acesso *" />
        <x-text-input id="NMLOGIN" name="NMLOGIN" type="text" class="mt-1 block w-full" :value="old('NMLOGIN', $usuario ?? null)" required />
    </div>
    <div>
        <x-input-label for="CDMATRFUNCIONARIO" value="Matrícula *" />
        <x-text-input id="CDMATRFUNCIONARIO" name="CDMATRFUNCIONARIO" type="text" class="mt-1 block w-full" :value="old('CDMATRFUNCIONARIO', $usuario ?? null)" required />
    </div>
    <div>
        <x-input-label for="PERFIL" value="Perfil *" />
        <select id="PERFIL" name="PERFIL" class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm" required>
            <option value="USR" @selected(old('PERFIL', $usuario ?? '') == 'USR')>Usuário Padrão</option>
            <option value="ADM" @selected(old('PERFIL', $usuario ?? '') == 'ADM')>Administrador</option>
        </select>
    </div>
    <div>
        <x-input-label for="SENHA" value="Senha" />
        @isset($usuario)
            <span class="text-xs text-gray-500">Deixe em branco para não alterar</span>
        @endisset
        <x-text-input id="SENHA" name="SENHA" type="password" class="mt-1 block w-full" />
    </div>
</div>

<div class="flex items-center justify-end mt-6">
    <a href="{{ route('usuarios.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline mr-4">
        Cancelar
    </a>
    <x-primary-button>
        {{ isset($usuario) ? 'Atualizar Usuário' : 'Criar Usuário' }}
    </x-primary-button>
</div>