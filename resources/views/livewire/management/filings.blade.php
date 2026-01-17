<div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Filings') }}</flux:heading>
            <flux:subheading>{{ __('Create filings and track their status by tax year.') }}</flux:subheading>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)]">
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ $editingId ? __('Edit filing') : __('Add filing') }}</flux:heading>
                <flux:subheading>{{ __('Filings are unique per tax year and form type.') }}</flux:subheading>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="filing-tax-year">{{ __('Tax year') }}</label>
                        <select
                            id="filing-tax-year"
                            wire:model="tax_year_id"
                            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <option value="">{{ __('Select tax year') }}</option>
                            @foreach ($taxYears as $taxYear)
                                <option value="{{ $taxYear->id }}">
                                    {{ $taxYear->year }} - {{ $taxYear->jurisdiction->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="filing-type">{{ __('Filing type') }}</label>
                        <select
                            id="filing-type"
                            wire:model="filing_type_id"
                            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <option value="">{{ __('Select filing type') }}</option>
                            @foreach ($filingTypes as $filingType)
                                <option value="{{ $filingType->id }}">
                                    {{ $filingType->name }} ({{ $filingType->jurisdiction->iso_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="filing-status">{{ __('Status') }}</label>
                        <select
                            id="filing-status"
                            wire:model="status"
                            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            @foreach ($statusOptions as $statusOption)
                                <option value="{{ $statusOption->value }}">
                                    {{ $statusLabel($statusOption) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-3">
                        <flux:button variant="primary" type="submit">
                            {{ $editingId ? __('Update filing') : __('Save filing') }}
                        </flux:button>
                        @if ($editingId)
                            <flux:button variant="ghost" type="button" wire:click="cancelEdit">
                                {{ __('Cancel') }}
                            </flux:button>
                        @endif
                        <x-action-message on="filing-saved">
                            {{ __('Saved.') }}
                        </x-action-message>
                    </div>
                </form>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Existing filings') }}</flux:heading>
                <flux:subheading>{{ __('Track status for each filing in the tax year.') }}</flux:subheading>

                @if ($filings->isEmpty())
                    <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                        {{ __('No filings yet. Add your first filing to get started.') }}
                    </div>
                @else
                    <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Tax year') }}</th>
                                    <th class="px-4 py-3">{{ __('Filing type') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach ($filings as $filing)
                                    <tr wire:key="filing-{{ $filing->id }}">
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                            {{ $filing->taxYear->year }} - {{ $filing->taxYear->jurisdiction->name }}
                                        </td>
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                            {{ $filing->filingType->name }}
                                        </td>
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                            {{ $statusLabel($filing->status) }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                class="text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-white"
                                                wire:click="edit({{ $filing->id }})"
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
