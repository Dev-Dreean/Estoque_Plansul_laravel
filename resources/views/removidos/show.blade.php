<x-app-layout>
  <div class="py-6">
    <div class="w-full sm:px-6 lg:px-8">
      @php
        $label = $removido->model_label ?? ($removido->model_type . ' #' . $removido->model_id);
        $payload = is_array($removido->payload ?? null) ? $removido->payload : [];
        ksort($payload);
      @endphp

      <div class="mb-4 flex items-start justify-between gap-4">
        <div>
          <h2 class="text-xl font-bold text-app">
            {{ __('Visualizar Removido') }}:
            <span class="font-normal text-muted">{{ $label }}</span>
          </h2>
          <p class="text-sm mt-1" style="color: var(--accent-500)">Modo de visualização (somente leitura)</p>
        </div>
        <a href="{{ route('removidos.index') }}" class="btn btn-neutral whitespace-nowrap">
          Voltar para Removidos
        </a>
      </div>

      <div class="bg-surface border border-app shadow-sm sm:rounded-lg">
        <div class="p-6 text-app space-y-6">
          <div>
            <h3 class="text-lg font-semibold mb-4">Dados do registro</h3>

            @if(empty($payload))
            <div class="text-sm text-muted">Nenhum dado capturado no payload.</div>
            @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
              @foreach($payload as $key => $value)
                @php
                  $valueDisplay = '-';
                  if ($value === null) {
                    $valueDisplay = '-';
                  } elseif (is_bool($value)) {
                    $valueDisplay = $value ? 'true' : 'false';
                  } elseif (is_scalar($value)) {
                    $valueDisplay = (string) $value;
                    if ($valueDisplay === '') $valueDisplay = '-';
                  } else {
                    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $valueDisplay = $json !== false ? $json : print_r($value, true);
                  }

                  $isLong = strlen($valueDisplay) > 70 || str_contains($valueDisplay, "\n");
                  $fieldId = 'payload_' . $loop->index;
                @endphp

                <div>
                  <x-input-label :for="$fieldId" :value="(string) $key" />
                  @if($isLong)
                    <textarea
                      id="{{ $fieldId }}"
                      rows="2"
                      readonly
                      class="mt-1 input-base font-mono text-xs">{{ $valueDisplay }}</textarea>
                  @else
                    <input
                      id="{{ $fieldId }}"
                      type="text"
                      readonly
                      value="{{ $valueDisplay }}"
                      class="mt-1 input-base font-mono text-xs" />
                  @endif
                </div>
              @endforeach
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

@push('scripts')
<script>
  try {
    console.log('[Removidos] Payload (JSON completo):', @json($removido->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
  } catch (e) {
    console.warn('[Removidos] Falha ao imprimir payload no console:', e);
  }
</script>
@endpush

</x-app-layout>
