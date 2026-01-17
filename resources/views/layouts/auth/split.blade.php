<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:via-slate-900 dark:to-neutral-800">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div class="bg-muted relative hidden h-full flex-col p-10 text-white lg:flex dark:border-e dark:border-white/10">
                <div class="absolute inset-0 bg-linear-to-br from-slate-950 via-slate-900 to-slate-800"></div>
                <a href="{{ route('home') }}" class="relative z-20 flex items-center text-lg font-medium" wire:navigate>
                    <span class="flex h-10 w-10 items-center justify-center rounded-md">
                        <x-app-logo-icon class="me-2 h-7 fill-current text-white" />
                    </span>
                    {{ config('app.name', 'Laravel') }}
                </a>

                <div class="relative z-20 mt-auto">
                    <div class="max-w-md rounded-3xl border border-white/15 bg-white/8 p-6 shadow-xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-300">Multi-country coverage</p>
                        <div class="mt-4 space-y-4">
                            <flux:heading size="xl" class="text-white">A single workspace for taxes across borders.</flux:heading>
                            <p class="text-sm leading-relaxed text-white/80">
                                Velor is a tax assistant built for professionals with income and expenses in multiple
                                countries and currencies. Organize your financial details, keep conversions consistent,
                                and prepare filings with clarity.
                            </p>
                        </div>
                        <div class="mt-5 flex flex-wrap gap-2 text-xs text-white/80">
                            <span class="rounded-full border border-white/15 px-3 py-1">Multi-currency tracking</span>
                            <span class="rounded-full border border-white/15 px-3 py-1">Jurisdiction summaries</span>
                            <span class="rounded-full border border-white/15 px-3 py-1">Clean reporting exports</span>
                        </div>
                        <div class="mt-6 rounded-2xl border border-white/15 bg-white/5 p-4">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/10">
                                    <x-app-logo-icon class="size-6 text-white" />
                                </span>
                                <div>
                                    <p class="text-[10px] uppercase tracking-[0.25em] text-emerald-200">Overview</p>
                                    <flux:heading size="sm" class="text-white">Tax Assistant</flux:heading>
                                </div>
                            </div>
                            <p class="mt-3 text-xs leading-relaxed text-white/80">
                                A focused space to capture income, expenses, and tax obligations across countries. Keep
                                everything in one view so you can move from records to ready-to-file reports without
                                spreadsheets.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-full lg:p-8">
                <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                        <span class="flex h-9 w-9 items-center justify-center rounded-md">
                            <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                        </span>

                        <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                    </a>
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
