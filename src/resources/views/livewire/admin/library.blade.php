<div class="space-y-6">
    @include('livewire.admin._nav')

    <div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Add a library sound') }}</h3>

        @include('livewire.partials.sound-upload-form', ['buttonLabel' => __('Add to library')])
    </div>

    <div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ __('Mass upload') }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Add several sounds at once — each is labeled from its file name. Edit emoji/image individually afterward.') }}</p>

        <form wire:submit="uploadMassSounds" class="space-y-4">
            <div>
                <x-file-input id="massUpload" wireModel="massUpload" accept="audio/*" multiple :label="__('Choose audio files')" />
                <x-input-error :messages="$errors->get('massUpload.*')" class="mt-2" />
            </div>

            <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="uploadMassSounds,massUpload" loadingTarget="uploadMassSounds" :loadingLabel="__('Adding sounds...')">
                {{ __('Add all to library') }}
            </x-primary-button>
        </form>
    </div>

    <div>
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('Library sounds') }}</h3>

        @if ($librarySounds->isEmpty())
            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('No library sounds yet — add one above.') }}</p>
        @else
            <ul class="space-y-2">
                @foreach ($librarySounds as $librarySound)
                    <li wire:key="library-sound-{{ $librarySound->id }}" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        @if ($editingSoundId === $librarySound->id)
                            @include('livewire.partials.sound-edit-form', ['sound' => $librarySound])
                        @else
                            <div class="flex items-center gap-3 p-3">
                                <x-sound-icon :sound="$librarySound" class="w-12 h-12 text-2xl rounded-lg bg-gray-100 dark:bg-gray-700 shrink-0" />
                                <x-play-button :src="$librarySound->url" />
                                <p class="flex-1 min-w-0 font-medium text-gray-900 dark:text-gray-100 truncate">{{ $librarySound->name }}</p>
                                <div class="flex items-center gap-0.5 shrink-0">
                                    <x-icon-button wire:click="startEditSound({{ $librarySound->id }})" :label="__('Edit')">
                                        <x-icon.edit />
                                    </x-icon-button>
                                    <x-icon-button variant="danger" wire:click="deleteSound({{ $librarySound->id }})" wire:confirm="{{ __('Delete this library sound? Copies already added to screens are unaffected.') }}" :label="__('Delete')">
                                        <x-icon.trash />
                                    </x-icon-button>
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
