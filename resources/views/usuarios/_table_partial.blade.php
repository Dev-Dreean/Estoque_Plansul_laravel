<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <table class="w-full text-base text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">Nome</th>
                <th scope="col" class="px-6 py-3">Login</th>
                <th scope="col" class="px-6 py-3">Matrícula</th>
                <th scope="col" class="px-6 py-3">UF</th>
                <th scope="col" class="px-6 py-3">Perfil</th>
                <th scope="col" class="px-6 py-3">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usuarios as $usuario)
            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $usuario->NOMEUSER }}</td>
                <td class="px-6 py-4">{{ $usuario->NMLOGIN }}</td>
                <td class="px-6 py-4">{{ $usuario->CDMATRFUNCIONARIO }}</td>
                <td class="px-6 py-4">{{ $usuario->UF ?? '—' }}</td>
                <td class="px-6 py-4">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $usuario->PERFIL === 'ADM' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ $usuario->PERFIL }}
                    </span>
                </td>
                <td class="px-6 py-4 flex items-center space-x-2">
                    <a href="{{ route('usuarios.edit', $usuario) }}" class="font-medium text-plansul-orange hover:underline">Editar</a>
                    @if(Auth::user()->isSuperAdmin() && Auth::id() !== $usuario->NUSEQUSUARIO)
                    <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" onsubmit="return confirm('Tem certeza?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="font-medium text-red-600 hover:underline">Deletar</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-4 text-center">Nenhum usuário encontrado.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">
    {{ $usuarios->appends(request()->query())->links() }}
</div>