<div class="space-y-6">
    @include('livewire.admin._nav')

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Users') }}</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $userCount }}</p>
        </div>
        <div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Screens') }}</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $screenCount }}</p>
        </div>
        <div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Sounds') }}</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $soundCount }}</p>
        </div>
        <div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Library sounds') }}</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $librarySoundCount }}</p>
        </div>
        <div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Storage used') }}</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($totalBytes / 1048576, 1) }} MB</p>
        </div>
    </div>
</div>
