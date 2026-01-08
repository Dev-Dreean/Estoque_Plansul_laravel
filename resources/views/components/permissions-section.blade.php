@props([
    'title',
    'description',
    'badge' => null,
])

<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-800 dark:bg-gray-950 p-4 flex flex-col">
    <div class="flex items-start justify-between gap-3 pb-3">
        <div class="flex-1">
            <h3 class="text-sm font-semibold text-gray-100">{{ $title }}</h3>
            <p class="text-xs text-gray-400 mt-1">{{ $description }}</p>
        </div>
        @if($badge)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                {{ $badge }}
            </span>
        @endif
    </div>

    {{ $slot }}
</div>
