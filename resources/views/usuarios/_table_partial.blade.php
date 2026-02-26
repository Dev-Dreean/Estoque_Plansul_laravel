<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                @php
                    $currSort = $sort ?? request('sort', 'NOMEUSER');
                    $currDir = $direction ?? request('direction', 'asc');
                    $nextDir = fn ($col) => ($currSort === $col && $currDir === 'asc') ? 'desc' : 'asc';
                    $isSort = fn ($col) => ($currSort === $col);
                @endphp
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'NOMEUSER', 'direction' => $nextDir('NOMEUSER'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        Nome <span class="text-[10px]">{{ $isSort('NOMEUSER') ? ($currDir === 'asc' ? '↑' : '↓') : '↕' }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'NMLOGIN', 'direction' => $nextDir('NMLOGIN'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        Login <span class="text-[10px]">{{ $isSort('NMLOGIN') ? ($currDir === 'asc' ? '↑' : '↓') : '↕' }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'CDMATRFUNCIONARIO', 'direction' => $nextDir('CDMATRFUNCIONARIO'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        Matrícula <span class="text-[10px]">{{ $isSort('CDMATRFUNCIONARIO') ? ($currDir === 'asc' ? '↑' : '↓') : '↕' }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'UF', 'direction' => $nextDir('UF'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        UF <span class="text-[10px]">{{ $isSort('UF') ? ($currDir === 'asc' ? '↑' : '↓') : '↕' }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'PERFIL', 'direction' => $nextDir('PERFIL'), 'page' => 1]) }}" class="inline-flex items-center gap-1 hover:text-indigo-600">
                        Perfil <span class="text-[10px]">{{ $isSort('PERFIL') ? ($currDir === 'asc' ? '↑' : '↓') : '↕' }}</span>
                    </a>
                </th>
                <th scope="col" class="px-4 py-2">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usuarios as $usuario)
            @php
                $isCurrentUser = isset($currentUserId) && (int) $usuario->NUSEQUSUARIO === (int) $currentUserId;
            @endphp
            <tr class="border-b dark:border-gray-700 {{ $isCurrentUser ? 'bg-amber-50 dark:bg-amber-900/20 ring-1 ring-amber-300/50' : 'bg-white dark:bg-gray-800' }}">
                <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">
                    <div class="flex items-center gap-2">
                        {{ $usuario->NOMEUSER }}
                        @if($isCurrentUser)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">VOCÊ</span>
                        @endif
                    </div>
                </td>
                <td class="px-4 py-2">{{ $usuario->NMLOGIN }}</td>
                <td class="px-4 py-2">{{ $usuario->CDMATRFUNCIONARIO }}</td>
                <td class="px-4 py-2">{{ $usuario->UF ?? '—' }}</td>
                <td class="px-4 py-2">
                    @if($usuario->PERFIL === 'ADM')
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ $usuario->PERFIL }}</span>
                    @else
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ $usuario->PERFIL }}</span>
                    @endif
                </td>
                <td class="px-4 py-2 flex items-center space-x-2">
                    <a href="{{ route('usuarios.edit', $usuario) }}" class="font-medium text-plansul-orange hover:underline">Editar</a>
                    @if(Auth::user()->isAdmin() && Auth::id() !== $usuario->NUSEQUSUARIO)
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
                <td colspan="6" class="px-4 py-3 text-center text-sm">Nenhum usuário encontrado.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">
    {{ $usuarios->appends(request()->query())->links() }}
</div>
