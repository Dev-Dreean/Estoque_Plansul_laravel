<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Gerenciar Acessos de Usuários
                        </h1>
                    </div>

                    @if(session('success'))
                    <div class="mb-4 p-4 rounded-md bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="mb-4 p-4 rounded-md bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                        {{ session('error') }}
                    </div>
                    @endif

                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <strong>Instruções:</strong> Selecione um usuário para configurar quais telas ele terá acesso no sistema.
                            Administradores (PERFIL='ADM') têm acesso automático a todas as telas.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nome</th>
                                    <th scope="col" class="px-6 py-3">Login</th>
                                    <th scope="col" class="px-6 py-3">Matrícula</th>
                                    <th scope="col" class="px-6 py-3">Perfil</th>
                                    <th scope="col" class="px-6 py-3 text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($usuarios as $usuario)
                                <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">
                                        {{ $usuario->NOMEUSER }}
                                        @if($usuario->PERFIL === 'ADM')
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                                            Admin
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                        {{ $usuario->NMLOGIN }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                        {{ $usuario->CDMATRFUNCIONARIO }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($usuario->PERFIL === 'ADM')
                                        <span class="px-2 py-1 text-xs rounded bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                            {{ $usuario->PERFIL }}
                                        </span>
                                        @else
                                        <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $usuario->PERFIL }}
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="{{ route('acessos.edit', $usuario->CDMATRFUNCIONARIO) }}"
                                            class="inline-flex items-center px-4 py-2 bg-plansul-blue hover:bg-opacity-90 text-white text-xs font-semibold rounded-full transition-all duration-200 shadow-sm hover:shadow-md">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Gerenciar Acessos
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Nenhum usuário ativo encontrado.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>