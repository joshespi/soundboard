<div class="space-y-6">
    @include('livewire.admin._nav')

    <x-text-input wire:model.live.debounce.300ms="search" type="text" class="block w-full" placeholder="{{ __('Search by sound name or owner email...') }}" />

    @if ($sounds->isEmpty())
        <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('No sounds found.') }}</p>
    @else
        <ul class="space-y-2">
            @foreach ($sounds as $sound)
                <li wire:key="content-sound-{{ $sound->id }}" class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <x-sound-icon :sound="$sound" class="w-12 h-12 text-2xl rounded-lg bg-gray-100 dark:bg-gray-700 shrink-0" />
                    <x-play-button :src="$sound->url" />
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $sound->name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                            {{ $sound->screen->name }} &middot; {{ $sound->screen->user->email }}
                        </p>
                    </div>
                    <x-icon-button variant="danger" :label="__('Delete')" wire:click="deleteSound({{ $sound->id }})" wire:confirm="{{ __('Delete this sound?') }}" class="shrink-0">
                        <x-icon.trash />
                    </x-icon-button>
                </li>
            @endforeach
        </ul>

        {{ $sounds->links() }}
    @endif
</div>
