{{-- Renderiza apenas as linhas <tr> da tabela de usuários --}}
@forelse ($usuarios as $usuario)
<tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
        <div class="flex items-center gap-2">
            {{ $usuario->NOMEUSER }}
            @if ($usuario->isSupervisor())
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300" title="Supervisor de {{ count($usuario->supervisor_de ?? []) }} usuário(s)">
                <span class="mr-1">👥</span>
                <span>Supervisor</span>
                <span class="ml-2 px-1.5 py-0.5 bg-indigo-200 dark:bg-indigo-800 text-indigo-900 dark:text-indigo-100 text-[10px] rounded-full font-semibold">{{ count($usuario->supervisor_de ?? []) }}</span>
            </span>
            @endif
        </div>
    </td>
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
        <!-- Edit (pencil) -->
        <a href="{{ route('usuarios.edit', $usuario) }}" class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Editar usuário {{ $usuario->NOMEUSER }}">
            <x-heroicon-o-pencil-square class="h-5 w-5 text-plansul-orange" />
        </a>

        @if(Auth::user()->isAdmin() && Auth::id() !== $usuario->NUSEQUSUARIO)
                <!-- Impersonate (user) -->
                <button type="button" data-login="{{ $usuario->NMLOGIN }}" data-id="{{ $usuario->NUSEQUSUARIO }}" class="impersonate-btn p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Assumir conta {{ $usuario->NMLOGIN }}">
                        <x-heroicon-o-user class="h-5 w-5 text-blue-600" />
                </button>

                <!-- Reset password (key) -->
                <button type="button" data-login="{{ $usuario->NMLOGIN }}" data-id="{{ $usuario->NUSEQUSUARIO }}" class="reset-senha-btn p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700" title="Resetar senha de {{ $usuario->NMLOGIN }}">
                        <x-heroicon-o-key class="h-5 w-5 text-indigo-600" />
                </button>
        @endif

        @if(Auth::user()->isAdmin() && Auth::id() !== $usuario->NUSEQUSUARIO)
                <!-- Delete (trash) -->
                <button 
                        type="button"
                        class="delete-btn-usuario p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-red-600"
                        data-usuario-id="{{ $usuario->NUSEQUSUARIO }}"
                        data-usuario-nome="{{ $usuario->NOMEUSER }}"
                        title="Deletar {{ $usuario->NOMEUSER }}">
                        <x-heroicon-o-trash class="h-5 w-5" />
                </button>
        @endif
    </td>
</tr>
@empty
<tr>
    <td colspan="6" class="px-6 py-4 text-center">Nenhum usuário encontrado.</td>
</tr>
@endforelse

@if(auth()->user()->isAdmin() || app()->environment('local'))
<script>
    (function(){
        const csrf = '{{ csrf_token() }}';

        document.querySelectorAll('.impersonate-btn').forEach(btn => {
            btn.addEventListener('click', function(){
                const login = this.dataset.login;
                const id = this.dataset.id;
                if (!confirm(`Assumir conta de ${login}? Você sairá da sua sessão atual.`)) return;
                fetch(`/usuarios/${id}/impersonate`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'text/html'
                    }
                }).then(r => {
                    if (r.redirected) {
                        window.location.href = r.url;
                    } else {
                        window.location.reload();
                    }
                }).catch(err => alert('Erro ao assumir: ' + err.message));
            });
        });

        document.querySelectorAll('.reset-senha-btn').forEach(btn => {
            btn.addEventListener('click', async function(){
                const login = this.dataset.login;
                const id = this.dataset.id;
                if (!confirm(`Gerar nova senha provisória para ${login}?`)) return;
                try {
                    const res = await fetch(`/usuarios/${id}/reset-senha`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        // Mostrar senha provisória para admin copiar
                        alert(`Senha provisória para ${data.login}: ${data.senha}`);
                    } else {
                        alert('Erro: ' + (data.message || 'Não foi possível resetar senha'));
                    }
                } catch (e) {
                    alert('Erro ao resetar senha: ' + e.message);
                }
            });
        });
    })();
</script>
@endif

