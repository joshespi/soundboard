@props(['number', 'title'])

<div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
    <div class="flex items-start gap-3">
        <div class="w-8 h-8 shrink-0 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center">{{ $number }}</div>
        <div class="min-w-0 flex-1">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
            {{ $slot }}
        </div>
    </div>
</div>
