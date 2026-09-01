<div class="space-y-6">
    @include('livewire.admin._nav')

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach ([
            [__('Users'), $userCount],
            [__('Screens'), $screenCount],
            [__('Sounds'), $soundCount],
            [__('Library sounds'), $librarySoundCount],
            [__('Storage used'), number_format($totalBytes / 1048576, 1).' MB'],
        ] as [$label, $value])
            <div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $value }}</p>
            </div>
        @endforeach
    </div>
</div>
