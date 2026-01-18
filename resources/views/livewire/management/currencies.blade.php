<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Currencies') }}</flux:heading>
        <flux:subheading>{{ __('Manage currency codes used for accounts, assets, and reporting.') }}</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)]">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ $editingId ? __('Edit currency') : __('Add currency') }}</flux:heading>
            <flux:subheading>{{ __('Use ISO 4217 codes for consistency.') }}</flux:subheading>

            @if ($errors->any())
                <div class="mt-4 rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800 dark:text-red-200">{{ __('There were errors with your submission') }}</h3>
                            <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                                <ul class="list-disc space-y-1 pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <form wire:submit="save" class="mt-5 space-y-4">
                <flux:input wire:model="code" :label="__('Currency code (ISO)')" type="text" maxlength="3" />
                <flux:input wire:model="name" :label="__('Currency name')" type="text" />
                <flux:input wire:model="symbol" :label="__('Symbol (optional)')" type="text" maxlength="10" />

                <div class="flex items-center gap-3">
                    <flux:switch wire:model="is_active" :label="__('Active')" />
                </div>

                <div class="flex items-center gap-3">
                    <flux:button variant="primary" type="submit">
                        {{ $editingId ? __('Update currency') : __('Save currency') }}
                    </flux:button>
                    @if ($editingId)
                        <flux:button variant="ghost" type="button" wire:click="cancelEdit">
                            {{ __('Cancel') }}
                        </flux:button>
                    @endif
                    <x-action-message on="currency-saved">
                        {{ __('Saved.') }}
                    </x-action-message>
                </div>
            </form>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Existing currencies') }}</flux:heading>
            <flux:subheading>{{ __('Enable or update currencies as needed.') }}</flux:subheading>

            @if ($currencies->isEmpty())
                <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                    {{ __('No currencies yet. Add your first currency to get started.') }}
                </div>
            @else
                <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                            <tr>
                                <th class="px-4 py-3">{{ __('Code') }}</th>
                                <th class="px-4 py-3">{{ __('Name') }}</th>
                                <th class="px-4 py-3">{{ __('Symbol') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($currencies as $currency)
                                <tr wire:key="currency-{{ $currency->id }}" data-in-use="{{ $currency->in_use ? 'true' : 'false' }}">
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                        {{ $currency->code }}
                                    </td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                        {{ $currency->name }}
                                    </td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                        {{ $currency->symbol ?? __('-') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <flux:badge size="sm" color="{{ $currency->is_active ? 'green' : 'zinc' }}">
                                            {{ $currency->is_active ? __('Active') : __('Inactive') }}
                                        </flux:badge>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <button
                                                data-action="edit"
                                                type="button"
                                                class="text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-white"
                                                wire:click="edit({{ $currency->id }})"
                                            >
                                                {{ __('Edit') }}
                                            </button>
                                            @if ($currency->is_active)
                                                <button
                                                    data-action="disable"
                                                    type="button"
                                                    class="text-sm font-medium text-amber-600 hover:text-amber-700 disabled:cursor-not-allowed disabled:opacity-50 dark:text-amber-400 dark:hover:text-amber-300"
                                                    wire:click="disable({{ $currency->id }})"
                                                    wire:confirm="{{ __('Disable this currency?') }}"
                                                    @if ($currency->in_use) data-disabled="true" @endif
                                                    @disabled($currency->in_use)
                                                    @if ($currency->in_use) title="{{ __('Currency is in use') }}" @endif
                                                >
                                                    {{ __('Disable') }}
                                                </button>
                                            @else
                                                <button
                                                    data-action="enable"
                                                    type="button"
                                                    class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
                                                    wire:click="enable({{ $currency->id }})"
                                                >
                                                    {{ __('Enable') }}
                                                </button>
                                            @endif
                                            <button
                                                data-action="delete"
                                                type="button"
                                                class="text-sm font-medium text-red-600 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-50 dark:text-red-400 dark:hover:text-red-300"
                                                wire:click="delete({{ $currency->id }})"
                                                wire:confirm="{{ __('Delete this currency?') }}"
                                                @if ($currency->in_use) data-disabled="true" @endif
                                                @disabled($currency->in_use)
                                                @if ($currency->in_use) title="{{ __('Currency is in use') }}" @endif
                                            >
                                                {{ __('Delete') }}
                                            </button>
                                        </div>
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
