<div class="space-y-6">
    @include('livewire.admin._nav')

    <ul class="space-y-2">
        @foreach ($users as $user)
            <li wire:key="admin-user-{{ $user->id }}" class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $user->name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</p>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 shrink-0">
                    {{ trans_choice(':count screen|:count screens', $user->screens_count, ['count' => $user->screens_count]) }},
                    {{ trans_choice(':count sound|:count sounds', $user->sounds_count, ['count' => $user->sounds_count]) }},
                    {{ number_format($user->storage_bytes / 1048576, 1) }} MB
                </p>
                @if ($user->id !== auth()->id())
                    <x-link-button
                        variant="danger"
                        href="#"
                        wire:click.prevent="deleteUser({{ $user->id }})"
                        wire:confirm="{{ __('Delete this user and all their screens and sounds?') }}"
                        class="shrink-0"
                    >
                        {{ __('Delete') }}
                    </x-link-button>
                @endif
            </li>
        @endforeach
    </ul>
</div>
