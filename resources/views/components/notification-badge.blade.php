@props([
    'count' => 0,
    'title' => null,
    'max' => 99,
])

@php
  $safeCount = (int) ($count ?? 0);
  $maxValue = (int) ($max ?? 99);
  $label = $title ?? ($safeCount . ' novos');
  $display = $safeCount > $maxValue ? ($maxValue . '+') : (string) $safeCount;
@endphp

@if($safeCount > 0)
  <span
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-[10px] font-semibold bg-red-600 text-white shadow-sm']) }}
    title="{{ $label }}"
    aria-label="{{ $label }}"
  >
    {{ $display }}
  </span>
@endif
