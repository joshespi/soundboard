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
                {{ __('Add sound') }}
            </x-primary-button>
        </form>
    </div>

    <div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Shared library') }}</h3>

        <x-text-input wire:model.live.debounce.300ms="librarySearch" type="text" class="block w-full mb-4" placeholder="{{ __('Search the shared library...') }}" />

        @if ($librarySounds->isEmpty())
            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('Nothing in the shared library yet.') }}</p>
        @else
            <ul class="space-y-2">
                @foreach ($librarySounds as $librarySound)
                    <li wire:key="library-sound-{{ $librarySound->id }}" class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700">
                        <x-sound-icon :sound="$librarySound" class="w-12 h-12 text-2xl rounded-lg bg-gray-100 dark:bg-gray-700 shrink-0" />
                        <x-play-button :src="$librarySound->url" />
                        <p class="flex-1 min-w-0 font-medium text-gray-900 dark:text-gray-100 truncate">{{ $librarySound->name }}</p>
                        <x-secondary-button type="button" wire:click="addFromLibrary({{ $librarySound->id }})" wire:loading.attr="disabled" wire:target="addFromLibrary({{ $librarySound->id }})" class="shrink-0">
                            {{ __('Add to screen') }}
                        </x-secondary-button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div>
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('Sounds') }}</h3>

        @if ($sounds->isEmpty())
            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('No sounds yet — add one above.') }}</p>
        @else
            <ul class="space-y-2">
                @foreach ($sounds as $sound)
                    <li wire:key="sound-{{ $sound->id }}" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        @if ($editingSoundId === $sound->id)
                            <form wire:submit="saveEditSound" class="p-3 sm:p-4 space-y-4">
                                <div class="flex gap-3 items-start">
                                    <x-sound-icon :sound="$sound" class="w-14 h-14 text-3xl rounded-lg bg-gray-100 dark:bg-gray-700 shrink-0" />

                                    <div class="flex-1 min-w-0">
                                        <x-input-label for="editName-{{ $sound->id }}" :value="__('Label')" />
                                        <x-text-input wire:model="editName" id="editName-{{ $sound->id }}" type="text" class="block w-full" />
                                        <x-input-error :messages="$errors->get('editName')" class="mt-1" />
                                    </div>
                                    <div class="w-28 shrink-0">
                                        <x-input-label for="editEmoji-{{ $sound->id }}" :value="__('Emoji')" />
                                        <x-emoji-input id="editEmoji-{{ $sound->id }}" wireModel="editEmoji" />
                                    </div>
                                </div>

                                <div>
                                    <x-input-label :value="__('Replace tile image')" />
                                    <div class="mt-1">
                                        <x-file-input id="editImage-{{ $sound->id }}" wireModel="editImage" accept="image/*" :label="__('Choose image')" />
                                    </div>
                                    <x-input-error :messages="$errors->get('editImage')" class="mt-2" />

                                    @if ($sound->image_path && ! $editImage)
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
                                <x-sound-icon :sound="$sound" class="w-12 h-12 text-2xl rounded-lg bg-gray-100 dark:bg-gray-700 shrink-0" />
                                <x-play-button :src="$sound->url" />
                                <p class="flex-1 min-w-0 font-medium text-gray-900 dark:text-gray-100 truncate">{{ $sound->name }}</p>
                                <div class="flex items-center gap-0.5 shrink-0">
                                    <x-icon-button wire:click="startEditSound({{ $sound->id }})" :label="__('Edit')">
                                        <x-icon.edit />
                                    </x-icon-button>
                                    <button wire:click="move({{ $sound->id }}, -1)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="{{ __('Move up') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .53.22l5.5 5.5a.75.75 0 1 1-1.06 1.06L10.75 5.56v10.69a.75.75 0 0 1-1.5 0V5.56L5.03 9.78a.75.75 0 0 1-1.06-1.06l5.5-5.5A.75.75 0 0 1 10 3Z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <button wire:click="move({{ $sound->id }}, 1)" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="{{ __('Move down') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.53-.22l-5.5-5.5a.75.75 0 1 1 1.06-1.06l4.22 4.22V3.75a.75.75 0 0 1 1.5 0v10.69l4.22-4.22a.75.75 0 1 1 1.06 1.06l-5.5 5.5A.75.75 0 0 1 10 17Z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <x-icon-button variant="danger" wire:click="deleteSound({{ $sound->id }})" wire:confirm="{{ __('Delete this sound?') }}" :label="__('Delete')">
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
