<!-- @php -->
  $isModal = $isModal ?? false;
@endphp

<div class="{{ $isModal ? 'p-4 sm:p-6' : 'py-12' }}">
  <div class="w-full {{ $isModal ? '' : 'sm:px-6 lg:px-8' }}">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
      <div class="p-4 sm:p-6">
        <form method="POST" action="{{ route('patrimonios.store') }}" autocomplete="off" @if($isModal) data-modal-form="create" @endif>
          @csrf
          @if($isModal)
            <input type="hidden" name="modal" value="1">
          @endif

          <x-patrimonio-form />

          <div class="flex flex-wrap items-center justify-end gap-2 mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
            @if($isModal)
              <button type="button" data-modal-close class="mr-4">Cancelar</button>
            @else
              <a href="{{ route('patrimonios.index') }}" class="mr-4">Cancelar</a>
            @endif
            <x-primary-button>{{ __('Salvar Patrimônio') }}</x-primary-button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
