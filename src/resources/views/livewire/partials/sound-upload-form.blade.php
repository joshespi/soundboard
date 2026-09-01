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
        </div>
    </div>

    <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="uploadSound,newSound,newSoundImage" loadingTarget="uploadSound" :loadingLabel="__('Saving...')">
        {{ $buttonLabel }}
    </x-primary-button>
</form>
