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

    <div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-3">
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" wire:model.live="isPublic" class="mt-1 rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-indigo-600 shadow-sm focus:ring-indigo-500">
            <span>
                <span class="block font-semibold text-gray-900 dark:text-gray-100">{{ __('Public link') }}</span>
                <span class="block text-sm text-gray-500 dark:text-gray-400">{{ __('Anyone with the link can play this screen without logging in. They cannot edit it.') }}</span>
            </span>
        </label>

        @if ($isPublic)
            <x-text-input type="text" readonly value="{{ route('screens.play', $screen) }}" x-data="" x-on:focus="$el.select()" class="block w-full text-sm" />

            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('To make this its own app icon: open the link in Chrome on his Android device, then menu → Install app. Each sound below also has its own single-song link that plays the moment it opens.') }}
            </p>

            <div class="flex items-center gap-3">
                @if ($screen->image_url)
                    <img src="{{ $screen->image_url }}" alt="" class="w-14 h-14 rounded-xl object-cover shrink-0">
                @endif
                <div class="min-w-0">
                    <x-input-label for="screenImage" :value="__('Home-screen icon')" />
                    <input id="screenImage" type="file" wire:model="screenImage" accept="image/*" class="block mt-1 text-sm text-gray-600 dark:text-gray-300">
                    <x-input-error :messages="$errors->get('screenImage')" class="mt-1" />
                </div>
                @if ($screen->image_url)
                    <x-secondary-button type="button" wire:click="removeScreenImage" class="shrink-0">{{ __('Remove') }}</x-secondary-button>
                @endif
            </div>
        @endif
    </div>

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
                    <x-icon.close />
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

    <x-modal name="trim-sound" maxWidth="lg">
        @if ($trimmingSound)
            <div wire:key="trim-{{ $trimmingSound->id }}" class="p-4 sm:p-6 space-y-4"
                 x-data="{
                    dur: 0, start: 0, end: 0, audioOnly: false, asNew: true,
                    fmt(s) { s = Math.max(0, +s || 0); return Math.floor(s / 60) + ':' + (s % 60).toFixed(1).padStart(4, '0'); },
                    clamp() { this.start = Math.min(+this.start, this.end - 0.1); this.end = Math.max(+this.end, +this.start + 0.1); },
                    stop() { $refs.m.pause(); },
                    cancel() { this.stop(); $dispatch('close'); $wire.cancelTrim(); },
                    async save() {
                        this.stop();
                        await $wire.trimSound(+this.start, +this.end, this.audioOnly, this.asNew);
                        if (! $wire.trimmingSoundId) $dispatch('close');
                    },
                 }">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ __('Trim') }}: {{ $trimmingSound->name }}</h3>
                    <button type="button" x-on:click="cancel()" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" aria-label="{{ __('Close') }}">
                        <x-icon.close />
                    </button>
                </div>

                @php($tag = $trimmingSound->is_video ? 'video' : 'audio')
                <{{ $tag }} x-ref="m" src="{{ $trimmingSound->url }}" controls preload="metadata" playsinline
                       @class(['w-full', 'max-h-64 rounded-lg bg-black' => $trimmingSound->is_video])
                       x-on:loadedmetadata="dur = end = $el.duration"
                       x-on:timeupdate="if ($el.currentTime >= end) $el.pause()"></{{ $tag }}>

                <div class="space-y-3" x-show="dur > 0">
                    <div>
                        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                            <span>{{ __('Start') }}: <span x-text="fmt(start)"></span></span>
                            <button type="button" class="text-indigo-600 dark:text-indigo-400 hover:underline" x-on:click="start = Math.min($refs.m.currentTime, end - 0.1)">{{ __('Use current time') }}</button>
                        </div>
                        <input type="range" min="0" :max="dur" step="0.1" x-model="start" x-on:input="clamp(); $refs.m.currentTime = start" class="w-full accent-indigo-600">
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                            <span>{{ __('End') }}: <span x-text="fmt(end)"></span></span>
                            <button type="button" class="text-indigo-600 dark:text-indigo-400 hover:underline" x-on:click="end = Math.max($refs.m.currentTime, +start + 0.1)">{{ __('Use current time') }}</button>
                        </div>
                        <input type="range" min="0" :max="dur" step="0.1" x-model="end" x-on:input="clamp()" class="w-full accent-indigo-600">
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Clip length') }}: <span x-text="fmt(end - start)"></span> · <button type="button" class="text-indigo-600 dark:text-indigo-400 hover:underline" x-on:click="stop(); $refs.m.currentTime = start; $refs.m.play()">{{ __('Preview range') }}</button></p>
                </div>

                @if ($trimmingSound->is_video)
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200 cursor-pointer">
                        <input type="checkbox" x-model="audioOnly" class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        {{ __('Keep audio only (strip the video)') }}
                    </label>
                @endif

                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200 cursor-pointer">
                    <input type="checkbox" x-model="asNew" class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    {{ __('Save as a new sound (keep the original)') }}
                </label>

                <x-input-error :messages="$errors->get('trim')" />

                <div class="flex items-center justify-end gap-2">
                    <x-secondary-button type="button" x-on:click="cancel()">{{ __('Cancel') }}</x-secondary-button>
                    <x-primary-button type="button" x-on:click="save()" wire:loading.attr="disabled" wire:target="trimSound">
                        <span wire:loading.remove wire:target="trimSound">{{ __('Convert') }}</span>
                        <span wire:loading wire:target="trimSound">{{ __('Converting…') }}</span>
                    </x-primary-button>
                </div>
            </div>
        @endif
    </x-modal>

    <div>
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('Sounds') }}</h3>

        @if ($sounds->isEmpty())
            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('No sounds yet — add one above.') }}</p>
        @else
            <ul class="space-y-2" x-sort="$wire.reorder($item, $position)">
                @foreach ($sounds as $sound)
                    <li wire:key="sound-{{ $sound->id }}" x-sort:item="{{ $sound->id }}" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        @if ($editingSoundId === $sound->id)
                            @include('livewire.partials.sound-edit-form', ['sound' => $sound])
                        @else
                            <x-sound-row :sound="$sound">
                                @if ($isPublic)
                                    <x-slot:subtitle>
                                        <a href="{{ route('sounds.play', [$screen, $sound]) }}" target="_blank" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('Single-song link') }}</a>
                                    </x-slot:subtitle>
                                @endif
                                <x-slot:handle>
                                    <span x-sort:handle class="sort-handle cursor-grab touch-none p-1 -ms-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="{{ __('Drag to reorder') }}" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M7 4a1.25 1.25 0 1 1-2.5 0A1.25 1.25 0 0 1 7 4Zm0 6a1.25 1.25 0 1 1-2.5 0A1.25 1.25 0 0 1 7 10Zm-1.25 7.25a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5ZM15.5 4a1.25 1.25 0 1 1-2.5 0 1.25 1.25 0 0 1 2.5 0ZM14.25 11.25a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5Zm1.25 4.5a1.25 1.25 0 1 1-2.5 0 1.25 1.25 0 0 1 2.5 0Z" /></svg>
                                    </span>
                                </x-slot:handle>
                                <x-slot:actions>
                                    <x-icon-button wire:click="startEditSound({{ $sound->id }})" :label="__('Edit')">
                                        <x-icon.edit />
                                    </x-icon-button>
                                    <x-icon-button wire:click="startTrim({{ $sound->id }})" x-data="" x-on:click="$dispatch('open-modal', 'trim-sound')" :label="$sound->is_video ? __('Trim / convert') : __('Trim')">
                                        <x-icon.scissors />
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
