<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <x-back-link :href="route('dashboard')">{{ __('All screens') }}</x-back-link>
        <x-link-button :href="route('screens.play', $screen)" wire:navigate>{{ __('Play this screen') }}</x-link-button>
    </div>

    <form wire:submit="updateScreenName" class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <x-input-label for="name" :value="__('Screen name')" />
        <div class="flex gap-2 mt-1">
            <x-text-input wire:model="name" id="name" type="text" class="block w-full" />
            <x-primary-button type="submit" class="shrink-0">{{ __('Save') }}</x-primary-button>
        </div>
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </form>

    <div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Add a sound') }}</h3>

        @include('livewire.partials.sound-upload-form', ['buttonLabel' => __('Add sound')])
    </div>

    <div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-between gap-4">
        <div class="min-w-0">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Shared library') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ __('Browse ready-made sounds anyone can add to a screen.') }}</p>
        </div>
        <x-secondary-button type="button" x-data="" x-on:click="$dispatch('open-modal', 'library-picker')" class="shrink-0">
            {{ __('Browse library') }}
        </x-secondary-button>
    </div>

    <x-modal name="library-picker" maxWidth="2xl" focusable>
        <div class="p-4 sm:p-6">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Shared library') }}</h3>
                <button type="button" x-on:click="$dispatch('close')" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" aria-label="{{ __('Close') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                </button>
            </div>

            <x-text-input wire:model.live.debounce.300ms="librarySearch" type="text" class="block w-full mb-4" placeholder="{{ __('Search the shared library...') }}" autofocus />

            <div class="max-h-[60vh] overflow-y-auto -mx-1 px-1">
                @if ($librarySounds->isEmpty())
                    <p class="text-gray-500 dark:text-gray-400 text-sm py-6 text-center">{{ __('Nothing found.') }}</p>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach ($librarySounds as $librarySound)
                            <div wire:key="library-sound-{{ $librarySound->id }}" class="flex flex-col items-center gap-2 p-3 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
                                <x-sound-icon :sound="$librarySound" class="w-14 h-14 text-3xl rounded-xl bg-gray-100 dark:bg-gray-700 shrink-0" />
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate w-full">{{ $librarySound->name }}</p>
                                <div class="flex items-center gap-1">
                                    <x-play-button :src="$librarySound->url" class="!w-8 !h-8" />
                                    <x-icon-button wire:click="addFromLibrary({{ $librarySound->id }})" wire:loading.attr="disabled" wire:target="addFromLibrary({{ $librarySound->id }})" :label="__('Add to screen')">
                                        <x-icon.plus />
                                    </x-icon-button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-modal>

    <div>
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('Sounds') }}</h3>

        @if ($sounds->isEmpty())
            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('No sounds yet — add one above.') }}</p>
        @else
            <ul class="space-y-2">
                @foreach ($sounds as $sound)
                    <li wire:key="sound-{{ $sound->id }}" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        @if ($editingSoundId === $sound->id)
                            @include('livewire.partials.sound-edit-form', ['sound' => $sound])
                        @else
                            <x-sound-row :sound="$sound">
                                <x-slot:actions>
                                    <x-icon-button wire:click="startEditSound({{ $sound->id }})" :label="__('Edit')">
                                        <x-icon.edit />
                                    </x-icon-button>
                                    <x-icon-button wire:click="move({{ $sound->id }}, -1)" :label="__('Move up')">
                                        <x-icon.chevron-up />
                                    </x-icon-button>
                                    <x-icon-button wire:click="move({{ $sound->id }}, 1)" :label="__('Move down')">
                                        <x-icon.chevron-down />
                                    </x-icon-button>
                                    <x-icon-button variant="danger" wire:click="deleteSound({{ $sound->id }})" wire:confirm="{{ __('Delete this sound?') }}" :label="__('Delete')">
                                        <x-icon.trash />
                                    </x-icon-button>
                                </x-slot:actions>
                            </x-sound-row>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
