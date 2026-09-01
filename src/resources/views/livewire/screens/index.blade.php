<div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <p class="text-gray-600 dark:text-gray-400">
            {{ __('Your soundboards. Make one for each thing you\'re into.') }}
        </p>
        <x-primary-button wire:click="$set('showCreateForm', true)" class="shrink-0 self-start">
            {{ __('New Screen') }}
        </x-primary-button>
    </div>

    @if ($showCreateForm)
        <form wire:submit="createScreen" class="mb-6 p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <x-input-label for="newScreenName" :value="__('Screen name')" />
            <x-text-input
                wire:model="newScreenName"
                id="newScreenName"
                type="text"
                class="mt-1 block w-full"
                placeholder="e.g. Minecraft, Memes, Movie Quotes"
                autofocus
            />
            <x-input-error :messages="$errors->get('newScreenName')" class="mt-2" />

            <div class="flex gap-2 mt-4">
                <x-primary-button type="submit">{{ __('Create') }}</x-primary-button>
                <x-secondary-button type="button" wire:click="$set('showCreateForm', false)">
                    {{ __('Cancel') }}
                </x-secondary-button>
            </div>
        </form>
    @endif

    @if ($screens->isEmpty())
        <x-empty-state>{{ __('No screens yet. Create your first one!') }}</x-empty-state>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($screens as $screen)
                <div class="p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-950 flex items-center justify-center shrink-0">
                            <x-application-logo class="w-5 h-5 fill-current text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $screen->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ trans_choice(':count sound|:count sounds', $screen->sounds_count, ['count' => $screen->sounds_count]) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <x-link-button :href="route('screens.play', $screen)" wire:navigate>{{ __('Play') }}</x-link-button>
                        <x-link-button :href="route('screens.manage', $screen)" variant="secondary" wire:navigate>{{ __('Edit') }}</x-link-button>
                        <x-link-button
                            variant="danger"
                            href="#"
                            wire:click.prevent="deleteScreen({{ $screen->id }})"
                            wire:confirm="{{ __('Delete this screen and all its sounds?') }}"
                            class="ms-auto"
                        >
                            {{ __('Delete') }}
                        </x-link-button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
