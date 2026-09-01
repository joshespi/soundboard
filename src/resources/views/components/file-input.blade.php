@props(['id', 'wireModel', 'accept' => null, 'label' => 'Choose file', 'multiple' => false])
<div x-data="{ fileName: '', uploading: false, progress: 0, error: '' }">
    <label
        for="{{ $id }}"
        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition"
    >
        <svg x-show="!uploading" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 shrink-0"><path fill-rule="evenodd" d="M9.25 13.25a.75.75 0 0 0 1.5 0V4.636l2.955 3.129a.75.75 0 0 0 1.09-1.03l-4.25-4.5a.75.75 0 0 0-1.09 0l-4.25 4.5a.75.75 0 1 0 1.09 1.03L9.25 4.636v8.614Z" clip-rule="evenodd" /><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" /></svg>
        <x-icon.spinner x-show="uploading" x-cloak class="w-4 h-4 shrink-0 animate-spin" />
        <span x-text="uploading ? @js(__('Uploading...')) + ' ' + progress + '%' : (fileName || @js($label))"></span>
    </label>
    <input
        wire:model="{{ $wireModel }}"
        id="{{ $id }}"
        type="file"
        @if ($accept) accept="{{ $accept }}" @endif
        @if ($multiple) multiple @endif
        x-on:change="fileName = $event.target.files.length > 1 ? $event.target.files.length + ' files selected' : ($event.target.files[0]?.name ?? ''); error = ''"
        x-on:livewire-upload-start="uploading = true; progress = 0; error = ''"
        x-on:livewire-upload-finish="uploading = false"
        x-on:livewire-upload-error="uploading = false; error = @js(__('Upload failed — the file(s) may be too large or the connection dropped. Try again.'))"
        x-on:livewire-upload-progress="progress = $event.detail.progress"
        class="sr-only"
    >
    <p x-show="error" x-cloak x-text="error" class="mt-1 text-sm text-red-600 dark:text-red-400"></p>
</div>
