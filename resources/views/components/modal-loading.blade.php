@props([
    'title' => 'Carregando',
    'subtitle' => 'Por favor, aguarde...',
])

<div {{ $attributes->merge(['class' => 'modal-loading-overlay']) }}>
    <div class="modal-loading-content">
        <svg class="modal-loading-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="modal-loading-spinner-track" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="modal-loading-spinner-head" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
        </svg>
        <div class="modal-loading-copy">
            <p class="modal-loading-title">{{ $title }}</p>
            <p class="modal-loading-subtitle">{{ $subtitle }}</p>
        </div>
    </div>
</div>
