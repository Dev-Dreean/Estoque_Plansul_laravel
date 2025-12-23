<x-app-layout>
  {{-- Abas de navegaÇõÇœo do patrimÇïnio --}}
  <x-patrimonio-nav-tabs />

  @include('patrimonios.partials.form-edit', [
    'patrimonio' => $patrimonio,
    'ultimaVerificacao' => $ultimaVerificacao,
  ])
</x-app-layout>

@push('scripts')
  @include('patrimonios.partials.edit-form-script')
@endpush
