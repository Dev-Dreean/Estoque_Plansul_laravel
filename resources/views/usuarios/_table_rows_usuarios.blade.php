{{-- Renderiza apenas as linhas <tr> da tabela de usuários --}}
@forelse ($usuarios as $usuario)
<tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $usuario->NOMEUSER }}</td>
    <td class="px-6 py-4">{{ $usuario->NMLOGIN }}</td>
    <td class="px-6 py-4">{{ $usuario->CDMATRFUNCIONARIO }}</td>
    <td class="px-6 py-4">{{ $usuario->UF ?? '—' }}</td>
    <td class="px-6 py-4">
        @if($usuario->PERFIL === 'ADM')
        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ $usuario->PERFIL }}</span>
        @else
        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ $usuario->PERFIL }}</span>
        @endif
    </td>
    <td class="px-6 py-4 flex items-center space-x-2">
        <a href="{{ route('usuarios.edit', $usuario) }}" class="font-medium text-plansul-orange hover:underline">Editar</a>
        @if(Auth::user()->isAdmin() && Auth::id() !== $usuario->NUSEQUSUARIO)
        <button 
            type="button"
            class="font-medium text-red-600 hover:underline delete-btn-usuario"
            data-usuario-id="{{ $usuario->NUSEQUSUARIO }}"
            data-usuario-nome="{{ $usuario->NOMEUSER }}">
            Deletar
        </button>
        @endif
    </td>
</tr>
@empty
<tr>
    <td colspan="6" class="px-6 py-4 text-center">Nenhum usuário encontrado.</td>
</tr>
@endforelse
