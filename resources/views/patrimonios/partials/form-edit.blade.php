@php
  $isModal = $isModal ?? false;
  $ultimaVerificacao = $ultimaVerificacao ?? null;
@endphp

@php
  // Carregar o nome do projeto
  $nomeProjetoOriginal = '';
  if ($patrimonio->CDPROJETO) {
    try {
      $projeto = App\Models\Tabfant::where('CDPROJETO', $patrimonio->CDPROJETO)->first();
      // Tentar NMFANTASIA primeiro, depois NOMEPROJETO
      $nomeProjetoOriginal = $projeto?->NMFANTASIA ?? $projeto?->NOMEPROJETO ?? '';
    } catch (\Exception $e) {
      $nomeProjetoOriginal = '';
    }
  }

  // Carregar o nome do local
  $nomeLocalOriginal = '';
  if ($patrimonio->CDLOCAL) {
    try {
      // Usar a query com lowercase 'cdlocal' pois a tabela usa lowercase
      $local = App\Models\LocalProjeto::where('id', $patrimonio->CDLOCAL)->first();
      if (!$local) {
        // Tentar também com CDLOCAL em uppercase
        $local = App\Models\LocalProjeto::where('cdlocal', $patrimonio->CDLOCAL)->first();
      }
      $nomeLocalOriginal = $local?->delocal ?? $local?->DELOCAL ?? '';
    } catch (\Exception $e) {
      $nomeLocalOriginal = '';
    }
  }

  // Carregar o nome do funcionário (responsável)
  $nomeFuncionarioOriginal = '';
  if ($patrimonio->CDMATRFUNCIONARIO) {
    try {
      $funcionario = App\Models\Funcionario::where('CDMATRFUNCIONARIO', $patrimonio->CDMATRFUNCIONARIO)->first();
      $nomeFuncionarioOriginal = $funcionario?->NMFUNCIONARIO ?? '';
      // Truncar a 25 caracteres se for muito grande
      if (strlen($nomeFuncionarioOriginal) > 25) {
        $nomeFuncionarioOriginal = substr($nomeFuncionarioOriginal, 0, 25) . '...';
      }
    } catch (\Exception $e) {
      $nomeFuncionarioOriginal = '';
    }
  }

  $dadosOriginais = [
    'NUPATRIMONIO' => $patrimonio->NUPATRIMONIO ?? '',
    'NUSEQOBJ' => $patrimonio->CODOBJETO ?? '',
    'DEPATRIMONIO' => $patrimonio->DEPATRIMONIO ?? '',
    'CDPROJETO' => $patrimonio->CDPROJETO ?? '',
    'NMPROJETOORIGINAL' => $nomeProjetoOriginal,
    'CDLOCAL' => $patrimonio->CDLOCAL ?? '',
    'DENOMELOCAL' => $nomeLocalOriginal,
    'CDMATRFUNCIONARIO' => $patrimonio->CDMATRFUNCIONARIO ?? '',
    'NOMEFUNCIONARIOORIGINAL' => $nomeFuncionarioOriginal,
    'SITUACAO' => $patrimonio->SITUACAO ?? '',
    'FLCONFERIDO' => $patrimonio->FLCONFERIDO ?? '',
    'MARCA' => $patrimonio->MARCA ?? '',
    'MODELO' => $patrimonio->MODELO ?? '',
    'DTAQUISICAO' => $patrimonio->DTAQUISICAO ?? '',
    'DTBAIXA' => $patrimonio->DTBAIXA ?? '',
    'DEHISTORICO' => $patrimonio->DEHISTORICO ?? '',
    'NUMOF' => $patrimonio->NUMOF ?? '',
    'NMPLANTA' => $patrimonio->NMPLANTA ?? '',
    'PESO' => $patrimonio->PESO ?? '',
    'TAMANHO' => $patrimonio->TAMANHO ?? '',
  ];
@endphp

<div class="{{ $isModal ? 'p-3 sm:p-4' : 'py-6' }}" data-patrimonio-edit>
  <div class="w-full {{ $isModal ? '' : 'sm:px-6 lg:px-8' }}">
    @unless($isModal)

    <div class="mb-4">
      <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
        {{ __('Editar Patrimônio') }}: <span class="font-normal text-gray-600 dark:text-gray-400">{{ $patrimonio->DEPATRIMONIO }}</span>
      </h2>
    </div>
    @endunless
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
      <div class="{{ $isModal ? 'p-3 sm:p-4' : 'p-4 sm:p-6' }} text-gray-900 dark:text-gray-100">
        <form method="POST" action="{{ route('patrimonios.update', $patrimonio) }}" id="editPatrimonioForm" data-dados-originais='@json($dadosOriginais)' data-require-codobjeto="{{ env('PATRIMONIO_REQUIRE_CODOBJETO', '') }}" autocomplete="off" @if($isModal) data-modal-form="edit" @endif>
          @csrf
          @method('PUT')
          @if($isModal)
            <input type="hidden" name="modal" value="1">
          @endif

          <x-patrimonio-form :patrimonio="$patrimonio" :ultima-verificacao="$ultimaVerificacao" />

          <div class="flex flex-wrap items-center gap-2 {{ $isModal ? 'mt-4 pt-4' : 'mt-6 pt-6' }} border-t border-gray-200 dark:border-gray-700">
            @if($isModal)
              <button type="button" data-modal-close class="mr-4">Cancelar</button>
            @else
              <a href="{{ route('patrimonios.index') }}" class="mr-4">Cancelar</a>
            @endif
            <button type="button" id="btnAtualizar" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">Atualizar Patrimônio</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- MODAL DE CONFIRMAÇÎÇŸO --}}
  <div id="modalConfirmacao" class="fixed inset-0 bg-black/60 dark:bg-black/80 {{ $isModal ? 'z-[70]' : 'z-50' }} hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-2xl p-6 max-w-md w-full border border-gray-200 dark:border-gray-700" @click.stop>
      {{-- Header com Çðcone de aviso --}}
      <div class="flex items-start mb-4">
        <div class="flex-shrink-0">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/30">
            <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c.866 1.5 2.537 2.912 4.583 2.912h10.84c2.046 0 3.717-1.412 4.583-2.912M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <div class="ml-4 flex-1">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confirmar Alterações</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Revise as mudanças antes de atualizar</p>
        </div>
      </div>

      {{-- Conteúdo --}}
      <div id="alteracoes" class="max-h-64 overflow-y-auto mb-6 pb-2 bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
        <!-- Alterações serão inseridas aqui dinamicamente -->
      </div>

      {{-- Footer --}}
      <div class="flex gap-3 justify-end">
        <button type="button" id="btnCancelarModal" class="px-4 py-2 text-sm font-medium rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:focus:ring-offset-gray-800">
          Cancelar
        </button>
        <button type="button" id="btnConfirmarAtualizacao" class="px-4 py-2 text-sm font-medium rounded-md bg-indigo-600 hover:bg-indigo-700 text-white transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
          Confirmar
        </button>
      </div>
    </div>
  </div>
</div>
