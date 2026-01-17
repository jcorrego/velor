<div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Profiles') }}</flux:heading>
            <flux:subheading>{{ __('Manage jurisdiction-specific profile details and display currencies.') }}</flux:subheading>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)]">
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ $editingId ? __('Edit profile') : __('Add profile') }}</flux:heading>
                <flux:subheading>{{ __('Profiles are unique per jurisdiction.') }}</flux:subheading>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="profile-jurisdiction">{{ __('Jurisdiction') }}</label>
                        <select
                            id="profile-jurisdiction"
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

                    <flux:input wire:model="name" :label="__('Name')" type="text" autocomplete="name" />
                    <flux:input wire:model="tax_id" :label="__('Tax ID')" type="text" />
                    <flux:input wire:model="default_currency" :label="__('Default currency (ISO)')" type="text" maxlength="3" />
                    <flux:input wire:model="display_currency" :label="__('Display currency override (ISO)')" type="text" maxlength="3" />

                    <div class="flex items-center gap-3">
                        <flux:button variant="primary" type="submit">
                            {{ $editingId ? __('Update profile') : __('Save profile') }}
                        </flux:button>
                        @if ($editingId)
                            <flux:button variant="ghost" type="button" wire:click="cancelEdit">
                                {{ __('Cancel') }}
                            </flux:button>
                        @endif
                        <x-action-message on="profile-saved">
                            {{ __('Saved.') }}
                        </x-action-message>
                    </div>
                </form>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Existing profiles') }}</flux:heading>
                <flux:subheading>{{ __('Review and edit profiles tied to each jurisdiction.') }}</flux:subheading>

                @if ($profiles->isEmpty())
                    <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                        {{ __('No profiles yet. Add your first profile to get started.') }}
                    </div>
                @else
                    <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Jurisdiction') }}</th>
                                    <th class="px-4 py-3">{{ __('Name') }}</th>
                                    <th class="px-4 py-3">{{ __('Default currency') }}</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach ($profiles as $profile)
                                    <tr wire:key="profile-{{ $profile->id }}">
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                            {{ $profile->jurisdiction->name }} ({{ $profile->jurisdiction->iso_code }})
                                        </td>
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                            {{ $profile->name }}
                                        </td>
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                            {{ $profile->default_currency ?? __('-') }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                class="text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-white"
                                                wire:click="edit({{ $profile->id }})"
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
