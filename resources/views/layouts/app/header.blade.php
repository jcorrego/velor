<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                <flux:navbar.item icon="user" :href="route('management.profiles')" :current="request()->routeIs('management.profiles')" wire:navigate>
                    {{ __('Profiles') }}
                </flux:navbar.item>
                <flux:navbar.item icon="map" :href="route('management.residency-periods')" :current="request()->routeIs('management.residency-periods')" wire:navigate>
                    {{ __('Residency') }}
                </flux:navbar.item>
                <flux:navbar.item icon="building-office-2" :href="route('management.entities')" :current="request()->routeIs('management.entities')" wire:navigate>
                    {{ __('Entities') }}
                </flux:navbar.item>
                <flux:navbar.item icon="document-text" :href="route('management.filings')" :current="request()->routeIs('management.filings')" wire:navigate>
                    {{ __('Filings') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Repository')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="folder-git-2"
                        href="https://github.com/jcorrego/velor"
                        target="_blank"
                        :label="__('Repository')"
                    />
                </flux:tooltip>
                <flux:tooltip :content="__('Documentation')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="book-open-text"
                        :href="route('docs')"
                        wire:navigate
                        label="Documentation"
                    />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Management')">
                    <flux:sidebar.item icon="user" :href="route('management.profiles')" :current="request()->routeIs('management.profiles')" wire:navigate>
                        {{ __('Profiles') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="map" :href="route('management.residency-periods')" :current="request()->routeIs('management.residency-periods')" wire:navigate>
                        {{ __('Residency') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-office-2" :href="route('management.entities')" :current="request()->routeIs('management.entities')" wire:navigate>
                        {{ __('Entities') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('management.filings')" :current="request()->routeIs('management.filings')" wire:navigate>
                        {{ __('Filings') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/jcorrego/velor" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="book-open-text" :href="route('docs')" :current="request()->routeIs('docs')" wire:navigate>
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
