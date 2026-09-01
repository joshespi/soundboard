<div class="flex items-center justify-between gap-4 mb-6">
    <x-back-link :href="route('dashboard')">{{ __('My Screens') }}</x-back-link>

    <nav class="flex gap-6">
        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" wire:navigate>
            {{ __('Overview') }}
        </x-nav-link>
        <x-nav-link :href="route('admin.content')" :active="request()->routeIs('admin.content')" wire:navigate>
            {{ __('Content') }}
        </x-nav-link>
        <x-nav-link :href="route('admin.users')" :active="request()->routeIs('admin.users')" wire:navigate>
            {{ __('Users') }}
        </x-nav-link>
        <x-nav-link :href="route('admin.library')" :active="request()->routeIs('admin.library')" wire:navigate>
            {{ __('Shared library') }}
        </x-nav-link>
    </nav>
</div>
