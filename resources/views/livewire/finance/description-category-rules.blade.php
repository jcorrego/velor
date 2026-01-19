<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Description Category Rules') }}</flux:heading>
        <flux:subheading>{{ __('Create rules to automatically assign categories based on transaction description patterns.') }}</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)]">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Select Jurisdiction') }}</flux:heading>
            <flux:subheading>{{ __('Choose a jurisdiction to manage its rules.') }}</flux:subheading>

            <div class="mt-4 space-y-2">
                @foreach ($jurisdictions as $jurisdiction)
                    <button
                        type="button"
                        wire:click="selectJurisdiction({{ $jurisdiction->id }})"
                        class="w-full rounded-lg px-4 py-2 text-left transition-colors {{ $jurisdictionId === $jurisdiction->id ? 'bg-blue-100 text-blue-900 dark:bg-blue-900/30 dark:text-blue-200' : 'bg-zinc-100 text-zinc-900 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700' }}"
                    >
                        {{ $jurisdiction->name }}
                    </button>
                @endforeach
            </div>
        </section>

        @if ($jurisdictionId)
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ $editingId ? __('Edit Rule') : __('Create Rule') }}</flux:heading>

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
                    <flux:input 
                        wire:model="descriptionPattern" 
                        :label="__('Description Pattern')"
                        :placeholder="__('e.g., STARBUCKS, AWS, PAYROLL')"
                        type="text" 
                    />
                    <flux:error name="descriptionPattern" />

                    <flux:select 
                        wire:model="categoryId"
                        :label="__('Category')"
                    >
                        <option value="">{{ __('Select a category') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="categoryId" />

                    <flux:textarea 
                        wire:model="notes"
                        :label="__('Notes (Optional)')"
                        :placeholder="__('e.g., Used for coffee expense reimbursements')"
                    />
                    <flux:error name="notes" />

                    <div class="flex items-center gap-3">
                        <flux:switch wire:model="isActive" :label="__('Active')" />
                    </div>

                    <div class="flex items-center gap-3">
                        <flux:button variant="primary" type="submit">
                            {{ $editingId ? __('Update rule') : __('Save rule') }}
                        </flux:button>
                        @if ($editingId)
                            <flux:button variant="ghost" type="button" wire:click="cancelEdit">
                                {{ __('Cancel') }}
                            </flux:button>
                        @endif
                        <x-action-message on="rule-saved">
                            {{ __('Saved.') }}
                        </x-action-message>
                    </div>
                </form>
            </section>

            <div class="lg:col-span-full">
                <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="lg">{{ __('Active Rules') }}</flux:heading>

                    @if ($rules->isEmpty())
                        <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                            {{ __('No rules created yet. Create your first rule to get started.') }}
                        </div>
                    @else
                        <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <table class="w-full text-sm">
                                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                                    <tr>
                                        <th class="px-4 py-3">{{ __('Pattern') }}</th>
                                        <th class="px-4 py-3">{{ __('Category') }}</th>
                                        <th class="px-4 py-3">{{ __('Notes') }}</th>
                                        <th class="px-4 py-3">{{ __('Status') }}</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach ($rules as $rule)
                                        <tr wire:key="rule-{{ $rule->id }}">
                                            <td class="px-4 py-3 font-mono text-zinc-900 dark:text-white">
                                                {{ $rule->description_pattern }}
                                            </td>
                                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                                {{ $rule->category->name }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                                {{ $rule->notes ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <flux:badge 
                                                    :color="$rule->is_active ? 'green' : 'zinc'"
                                                    class="cursor-pointer"
                                                    wire:click="toggleActive({{ $rule->id }})"
                                                >
                                                    {{ $rule->is_active ? __('Active') : __('Inactive') }}
                                                </flux:badge>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex items-center justify-end gap-3">
                                                    <button
                                                        type="button"
                                                        class="text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-white"
                                                        wire:click="edit({{ $rule->id }})"
                                                    >
                                                        {{ __('Edit') }}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                                        wire:click="delete({{ $rule->id }})"
                                                        wire:confirm="{{ __('Delete this rule?') }}"
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
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Select a jurisdiction to get started.') }}</p>
            </div>
        @endif
    </div>
</div>
