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
