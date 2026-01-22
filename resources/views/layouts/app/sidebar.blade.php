<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('People')" class="grid">
                    <flux:sidebar.item icon="user" :href="route('management.profiles')" :current="request()->routeIs('management.profiles')" wire:navigate>
                        {{ __('Profiles') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="map" :href="route('management.residency-periods')" :current="request()->routeIs('management.residency-periods')" wire:navigate>
                        {{ __('Residency') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-office-2" :href="route('management.entities')" :current="request()->routeIs('management.entities')" wire:navigate>
                        {{ __('Entities') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Operations')" class="grid">
                    <flux:sidebar.item icon="calendar" :href="route('management.tax-years')" :current="request()->routeIs('management.tax-years')" wire:navigate>
                        {{ __('Tax Years') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('management.filings')" :current="request()->routeIs('management.filings')" wire:navigate>
                        {{ __('Filings') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="folder-open" :href="route('management.documents')" :current="request()->routeIs('management.documents')" wire:navigate>
                        {{ __('Documents') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-down-tray" :href="route('management.import-review')" :current="request()->routeIs('management.import-review')" wire:navigate>
                        {{ __('Import Review') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="tag" :href="route('management.description-category-rules')" :current="request()->routeIs('management.description-category-rules')" wire:navigate>
                        {{ __('Category Rules') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="currency-dollar" :href="route('management.currencies')" :current="request()->routeIs('management.currencies')" wire:navigate>
                        {{ __('Currencies') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Records')" class="grid">
                    <flux:sidebar.item icon="currency-dollar" :href="route('finance.accounts')" :current="request()->routeIs('finance.accounts')" wire:navigate>
                        {{ __('Accounts') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('finance.transactions')" :current="request()->routeIs('finance.transactions')" wire:navigate>
                        {{ __('Transactions') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="home" :href="route('finance.assets')" :current="request()->routeIs('finance.assets')" wire:navigate>
                        {{ __('Assets') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('finance.year-end-values')" :current="request()->routeIs('finance.year-end-values')" wire:navigate>
                        {{ __('Year-End Values') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Tax Setup')" class="grid">
                    <flux:sidebar.item icon="tag" :href="route('finance.categories')" :current="request()->routeIs('finance.categories')" wire:navigate>
                        {{ __('Categories') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="map" :href="route('finance.mappings')" :current="request()->routeIs('finance.mappings')" wire:navigate>
                        {{ __('Mappings') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Reports')" class="grid">
                    <flux:sidebar.item icon="document-text" :href="route('finance.us-tax.owner-flow')" :current="request()->routeIs('finance.us-tax.owner-flow')" wire:navigate>
                        {{ __('Owner Flow (5472)') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('finance.us-tax.form-5472')" :current="request()->routeIs('finance.us-tax.form-5472')" wire:navigate>
                        {{ __('Form 5472 Guidance') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('finance.us-tax.form-4562')" :current="request()->routeIs('finance.us-tax.form-4562')" wire:navigate>
                        {{ __('Form 4562') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="home" :href="route('finance.us-tax.schedule-e')" :current="request()->routeIs('finance.us-tax.schedule-e')" wire:navigate>
                        {{ __('Schedule E') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('finance.us-tax.form-1040nr')" :current="request()->routeIs('finance.us-tax.form-1040nr')" wire:navigate>
                        {{ __('Form 1040-NR') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('finance.us-tax.form-1120')" :current="request()->routeIs('finance.us-tax.form-1120')" wire:navigate>
                        {{ __('Form 1120') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="globe-alt" :href="route('finance.colombia-tax.summary')" :current="request()->routeIs('finance.colombia-tax.summary')" wire:navigate>
                        {{ __('Colombia Summary') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('finance.spain-tax.irpf')" :current="request()->routeIs('finance.spain-tax.irpf')" wire:navigate>
                        {{ __('IRPF Summary') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('finance.spain-tax.modelo-720')" :current="request()->routeIs('finance.spain-tax.modelo-720')" wire:navigate>
                        {{ __('Modelo 720') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Resources')" class="grid">
                    <flux:sidebar.item icon="book-open-text" :href="route('docs')" :current="request()->routeIs('docs')" wire:navigate>
                        {{ __('Documentation') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="folder-git-2" href="https://github.com/jcorrego/velor" target="_blank">
                        {{ __('Repository') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
