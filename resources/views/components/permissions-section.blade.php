@props([
    'title',
    'description',
    'badge' => null,
])

<div class="flex flex-col rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
    <div class="flex items-start justify-between gap-3 border-b border-gray-100 dark:border-gray-700 pb-4">
        <div class="flex-1">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
        </div>
        @if($badge)
            <span class="inline-flex items-center rounded-full bg-violet-50 dark:bg-violet-900/30 px-3 py-1 text-xs font-medium text-violet-700 dark:text-violet-300">
                {{ $badge }}
            </span>
        @endif
    </div>

    {{ $slot }}
</div>
