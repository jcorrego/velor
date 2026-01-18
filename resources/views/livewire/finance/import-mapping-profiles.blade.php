<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Import Mapping Profiles') }}</flux:heading>
        <flux:subheading>{{ __('Save and reuse column mappings for recurring imports.') }}</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)]">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Select Account') }}</flux:heading>
            <flux:subheading>{{ __('Choose an account to manage its mapping profiles.') }}</flux:subheading>

            <div class="mt-4 space-y-2">
                @foreach ($accounts as $account)
                    <button
                        type="button"
                        wire:click="selectAccount({{ $account->id }})"
                        class="w-full rounded-lg px-4 py-2 text-left transition-colors {{ $accountId === $account->id ? 'bg-blue-100 text-blue-900 dark:bg-blue-900/30 dark:text-blue-200' : 'bg-zinc-100 text-zinc-900 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700' }}"
                    >
                        {{ $account->name }}
                    </button>
                @endforeach
            </div>
        </section>

        @if ($accountId)
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ $editingId ? __('Edit Profile') : __('Create Profile') }}</flux:heading>

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
                    <flux:input wire:model="name" :label="__('Profile name')" type="text" />
                    <flux:textarea wire:model="description" :label="__('Description (optional)')" :placeholder="__('e.g., My bank export format')" />

                    <div>
                        <flux:heading size="sm">{{ __('Column Mapping') }}</flux:heading>
                        <flux:subheading>{{ __('Map CSV columns to transaction fields.') }}</flux:subheading>

                        <div class="mt-4 space-y-3">
                            @if (count($columnMapping) === 0)
                                <div class="rounded-lg border border-dashed border-zinc-200 px-4 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                    {{ __('No mappings yet. Add the first mapping below.') }}
                                </div>
                            @else
                                <div class="space-y-2">
                                    @foreach ($columnMapping as $csvColumn => $field)
                                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
                                            <div>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $csvColumn }}</p>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">→ {{ $field }}</p>
                                            </div>
                                            <button
                                                type="button"
                                                wire:click="removeColumnMapping('{{ $csvColumn }}')"
                                                class="text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                {{ __('Remove') }}
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

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

            <div class="lg:col-span-full">
                <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="lg">{{ __('Saved Profiles') }}</flux:heading>

                    @if ($profiles->isEmpty())
                        <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                            {{ __('No profiles created yet. Create your first profile to get started.') }}
                        </div>
                    @else
                        <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <table class="w-full text-sm">
                                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                                    <tr>
                                        <th class="px-4 py-3">{{ __('Profile Name') }}</th>
                                        <th class="px-4 py-3">{{ __('Description') }}</th>
                                        <th class="px-4 py-3">{{ __('Mappings') }}</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach ($profiles as $profile)
                                        <tr wire:key="profile-{{ $profile->id }}">
                                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                                                {{ $profile->name }}
                                            </td>
                                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                                {{ $profile->description ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                                {{ count($profile->column_mapping) }}
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex items-center justify-end gap-3">
                                                    <button
                                                        type="button"
                                                        class="text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-white"
                                                        wire:click="edit({{ $profile->id }})"
                                                    >
                                                        {{ __('Edit') }}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                                        wire:click="delete({{ $profile->id }})"
                                                        wire:confirm="{{ __('Delete this profile?') }}"
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
        @else
            <div class="rounded-xl border border-dashed border-zinc-200 px-6 py-12 text-center dark:border-zinc-700">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Select an account to get started.') }}</p>
            </div>
        @endif
    </div>
</div>
