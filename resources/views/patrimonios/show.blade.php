<x-app-layout>
    {{-- Abas de navegação do patrimônio --}}
    <x-patrimonio-nav-tabs />

    @php
        $podeGerarTermo = trim((string) ($patrimonio->CDPROJETO ?? '')) !== '' && trim((string) ($patrimonio->CDMATRFUNCIONARIO ?? '')) !== '';
        $termoGerado = strtoupper(trim((string) ($patrimonio->FLTERMORESPONSABILIDADE ?? 'N'))) === 'S';
        $podeBaixarTermo = $podeGerarTermo && auth()->user()?->can('create', \App\Models\Patrimonio::class);
    @endphp

    <div class="py-6">
        <div class="w-full sm:px-6 lg:px-8">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                    {{ __('Visualizar Patrimônio') }}: <span class="font-normal text-gray-600 dark:text-gray-400">{{ $patrimonio->DEPATRIMONIO }}</span>
                </h2>
                <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">ℹ️ Modo de visualização (somente leitura)</p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{-- Exibir form em modo read-only --}}
                    <div class="space-y-6">
                        {{-- Identificação --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="NUPATRIMONIO" value="Número do Patrimônio" />
                                <input type="text" id="NUPATRIMONIO" value="{{ $patrimonio->NUPATRIMONIO }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <x-input-label for="DEPATRIMONIO" value="Descrição" />
                                <input type="text" id="DEPATRIMONIO" value="{{ $patrimonio->DEPATRIMONIO }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        {{-- Projeto e Local --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="CDPROJETO" value="Projeto" />
                                <input type="text" id="CDPROJETO" value="{{ $patrimonio->projeto?->NOMEPROJETO ?? '-' }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <x-input-label for="CDLOCAL" value="Local Físico" />
                                <input type="text" id="CDLOCAL" value="{{ $patrimonio->local?->delocal ?? '-' }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        {{-- Responsável --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="CDMATRFUNCIONARIO" value="Responsável (Matrícula)" />
                                <input type="text" id="CDMATRFUNCIONARIO" value="{{ $patrimonio->CDMATRFUNCIONARIO }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <x-input-label for="NOMEUSER" value="Responsável (Nome)" />
                                <input type="text" id="NOMEUSER" value="{{ $patrimonio->funcionario?->NMFUNCIONARIO ?? '-' }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="CDMATRGERENTE" value="Gerente Responsável (Matrícula)" />
                                <input type="text" id="CDMATRGERENTE" value="{{ $patrimonio->CDMATRGERENTE ?? '-' }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <x-input-label for="NOMEGERENTE" value="Gerente Responsável (Nome)" />
                                <input type="text" id="NOMEGERENTE" value="{{ $patrimonio->gerenteResponsavel?->NMFUNCIONARIO ?? '-' }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        {{-- Datas --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="DTAQUISICAO" value="Data da OC" />
                                <input type="text" id="DTAQUISICAO" value="{{ $patrimonio->DTAQUISICAO ? \Carbon\Carbon::parse($patrimonio->DTAQUISICAO)->format('d/m/Y') : '-' }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <x-input-label for="DTOPERACAO" value="Data de Operação" />
                                <input type="text" id="DTOPERACAO" value="{{ $patrimonio->DTOPERACAO ? \Carbon\Carbon::parse($patrimonio->DTOPERACAO)->format('d/m/Y H:i') : '-' }}" readonly 
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        {{-- Situação --}}
                        <div>
                            <x-input-label for="SITUACAO" value="Situação" />
                            <input type="text" id="SITUACAO" value="{{ $patrimonio->SITUACAO ?? '-' }}" readonly 
                                class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                        </div>

                        {{-- Criado por --}}
                        <div>
                            <x-input-label for="USUARIO" value="Criado por" />
                            <input type="text" id="USUARIO" value="{{ $patrimonio->cadastrado_por_nome ?? $patrimonio->USUARIO ?? '-' }}" readonly 
                                class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                        </div>

                        {{-- Voltagem --}}
                        <div>
                            <x-input-label for="VOLTAGEM" value="Voltagem" />
                            <input type="text" id="VOLTAGEM" value="{{ $patrimonio->VOLTAGEM ?? '-' }}" readonly 
                                class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                        </div>

                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Ações do termo</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        @if($termoGerado)
                                            Esse patrimônio já possui termo salvo para consulta futura.
                                        @else
                                            Gere o documento do responsável com base nos itens do mesmo projeto.
                                        @endif
                                    </p>
                                </div>
                                @if($podeBaixarTermo)
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <a
                                            href="{{ route('termos.responsabilidade.patrimonio.docx', $patrimonio->NUSEQPATR) }}"
                                            class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-slate-700 hover:bg-slate-800 text-white font-semibold transition"
                                        >
                                            {{ $termoGerado ? 'Baixar último PDF' : 'Gerar termo em PDF' }}
                                        </a>
                                        @if($termoGerado)
                                            <a
                                                href="{{ route('termos.responsabilidade.patrimonio.docx', ['id' => $patrimonio->NUSEQPATR, 'regenerar' => 1]) }}"
                                                class="inline-flex items-center justify-center px-4 py-2 rounded-md border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 font-semibold transition"
                                            >
                                                Regenerar PDF
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-3 py-2 rounded-md bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 text-sm font-medium">
                                        {{ $podeGerarTermo ? 'Seu perfil não pode gerar esse termo.' : 'Informe projeto e responsável para gerar o termo.' }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Peso e Tamanho --}}
                        <div>
                            <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-300 mb-2">Novos campos</p>
                            <div class="border-2 border-indigo-500 dark:border-indigo-400 rounded-lg p-2">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <div>
                                        <x-input-label for="PESO" value="Peso (kg)" />
                                        <input type="text" id="PESO" value="{{ $patrimonio->PESO ?? '-' }}" readonly 
                                            class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                                    </div>
                                    <div>
                                        <x-input-label for="TAMANHO" value="Dimensões" />
                                        <input type="text" id="TAMANHO" value="{{ $patrimonio->TAMANHO ?? '-' }}" readonly 
                                            class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-gray-900 dark:text-gray-100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Botões --}}
                    <div class="flex items-center justify-start mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <a href="{{ route('patrimonios.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">← Voltar para Lista</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
