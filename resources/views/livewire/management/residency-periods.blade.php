<div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Residency Periods') }}</flux:heading>
            <flux:subheading>{{ __('Track when you were resident in each jurisdiction.') }}</flux:subheading>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)]">
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ $editingId ? __('Edit residency period') : __('Add residency period') }}</flux:heading>
                <flux:subheading>{{ __('Overlaps across jurisdictions are allowed.') }}</flux:subheading>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="residency-jurisdiction">{{ __('Jurisdiction') }}</label>
                        <select
                            id="residency-jurisdiction"
                            wire:model="jurisdiction_id"
                            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <option value="">{{ __('Select jurisdiction') }}</option>
                            @foreach ($jurisdictions as $jurisdiction)
                                <option value="{{ $jurisdiction->id }}">
                                    {{ $jurisdiction->name }} ({{ $jurisdiction->iso_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="start-date">{{ __('Start date') }}</label>
                            <input
                                id="start-date"
                                type="date"
                                wire:model="start_date"
                                class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="end-date">{{ __('End date') }}</label>
                            <input
                                id="end-date"
                                type="date"
                                wire:model="end_date"
                                class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <flux:button variant="primary" type="submit">
                            {{ $editingId ? __('Update period') : __('Save period') }}
                        </flux:button>
                        @if ($editingId)
                            <flux:button variant="ghost" type="button" wire:click="cancelEdit">
                                {{ __('Cancel') }}
                            </flux:button>
                        @endif
                        <x-action-message on="residency-saved">
                            {{ __('Saved.') }}
                        </x-action-message>
                    </div>
                </form>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Residency timeline') }}</flux:heading>
                <flux:subheading>{{ __('See the periods recorded for each jurisdiction.') }}</flux:subheading>

                @if ($periods->isEmpty())
                    <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                        {{ __('No residency periods yet. Add your first period to get started.') }}
                    </div>
                @else
                    <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Jurisdiction') }}</th>
                                    <th class="px-4 py-3">{{ __('Start') }}</th>
                                    <th class="px-4 py-3">{{ __('End') }}</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach ($periods as $period)
                                    <tr wire:key="residency-period-{{ $period->id }}">
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                            {{ $period->jurisdiction->name }} ({{ $period->jurisdiction->iso_code }})
                                        </td>
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                            {{ $period->start_date->format('Y-m-d') }}
                                        </td>
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                            {{ $period->end_date?->format('Y-m-d') ?? __('Current') }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                class="text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-white"
                                                wire:click="edit({{ $period->id }})"
                                            >
                                                {{ __('Edit') }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
