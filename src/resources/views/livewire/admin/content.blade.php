<div class="space-y-6">
    @include('livewire.admin._nav')

    <x-text-input wire:model.live.debounce.300ms="search" type="text" class="block w-full" placeholder="{{ __('Search by sound name or owner email...') }}" />

    @if ($sounds->isEmpty())
        <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('No sounds found.') }}</p>
    @else
        <ul class="space-y-2">
            @foreach ($sounds as $sound)
                <li wire:key="content-sound-{{ $sound->id }}" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <x-sound-row :sound="$sound">
                        <x-slot:subtitle>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                {{ $sound->screen->name }} &middot; {{ $sound->screen->user->email }}
                            </p>
                        </x-slot:subtitle>
                        <x-slot:actions>
                            <x-icon-button variant="danger" :label="__('Delete')" wire:click="deleteSound({{ $sound->id }})" wire:confirm="{{ __('Delete this sound?') }}">
                                <x-icon.trash />
                            </x-icon-button>
                        </x-slot:actions>
                    </x-sound-row>
                </li>
            @endforeach
        </ul>

        {{ $sounds->links() }}
    @endif
</div>
