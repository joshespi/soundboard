<div class="space-y-6">
    @include('livewire.admin._nav')

    <div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Add a library sound') }}</h3>

        <form wire:submit="uploadSound" class="space-y-4">
            <div class="flex gap-3">
                <div class="flex-1 min-w-0">
                    <x-input-label for="newSoundName" :value="__('Label (optional)')" />
                    <x-text-input wire:model="newSoundName" id="newSoundName" type="text" class="block w-full" placeholder="e.g. Creeper" />
                </div>
                <div class="w-28 shrink-0">
                    <x-input-label for="newSoundEmoji" :value="__('Emoji')" />
                    <x-emoji-input id="newSoundEmoji" wireModel="newSoundEmoji" />
                </div>
            </div>

            <div class="flex flex-wrap gap-x-8 gap-y-4">
                <div>
                    <x-input-label :value="__('Audio file (mp3, wav, ogg, m4a, aac — max 20MB)')" />
                    <div class="mt-1">
                        <x-file-input id="newSound" wireModel="newSound" accept="audio/*" :label="__('Choose audio file')" />
                    </div>
                    <x-input-error :messages="$errors->get('newSound')" class="mt-2" />
                    <div wire:loading wire:target="newSound" class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Uploading...') }}</div>
                </div>

                <div>
                    <x-input-label :value="__('Tile image (optional — used instead of the emoji)')" />
                    <div class="mt-1 flex items-center gap-3">
                        <x-file-input id="newSoundImage" wireModel="newSoundImage" accept="image/*" :label="__('Choose image')" />
                        @if ($newSoundImage)
                            <img src="{{ $newSoundImage->temporaryUrl() }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-700">
                        @endif
                    </div>
                    <x-input-error :messages="$errors->get('newSoundImage')" class="mt-2" />
                    <div wire:loading wire:target="newSoundImage" class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Uploading...') }}</div>
                </div>
            </div>

            <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="uploadSound,newSound,newSoundImage">
                {{ __('Add to library') }}
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
                            <form wire:submit="saveEditSound" class="p-3 sm:p-4 space-y-4">
                                <div class="flex gap-3 items-start">
                                    <x-sound-icon :sound="$librarySound" class="w-14 h-14 text-3xl rounded-lg bg-gray-100 dark:bg-gray-700 shrink-0" />

                                    <div class="flex-1 min-w-0">
                                        <x-input-label for="editName-{{ $librarySound->id }}" :value="__('Label')" />
                                        <x-text-input wire:model="editName" id="editName-{{ $librarySound->id }}" type="text" class="block w-full" />
                                        <x-input-error :messages="$errors->get('editName')" class="mt-1" />
                                    </div>
                                    <div class="w-28 shrink-0">
                                        <x-input-label for="editEmoji-{{ $librarySound->id }}" :value="__('Emoji')" />
                                        <x-emoji-input id="editEmoji-{{ $librarySound->id }}" wireModel="editEmoji" />
                                    </div>
                                </div>

                                <div>
                                    <x-input-label :value="__('Replace tile image')" />
                                    <div class="mt-1">
                                        <x-file-input id="editImage-{{ $librarySound->id }}" wireModel="editImage" accept="image/*" :label="__('Choose image')" />
                                    </div>
                                    <x-input-error :messages="$errors->get('editImage')" class="mt-2" />

                                    @if ($librarySound->image_path && ! $editImage)
                                        <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                            <input type="checkbox" wire:model="editRemoveImage" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                            {{ __('Remove current image (fall back to emoji)') }}
                                        </label>
                                    @endif
                                </div>

                                <div class="flex gap-2">
                                    <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="saveEditSound,editImage">{{ __('Save') }}</x-primary-button>
                                    <x-secondary-button type="button" wire:click="cancelEditSound">{{ __('Cancel') }}</x-secondary-button>
                                </div>
                            </form>
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
