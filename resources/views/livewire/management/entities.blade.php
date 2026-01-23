<div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Entities') }}</flux:heading>
            <flux:subheading>{{ __('Manage individuals and LLCs tied to a jurisdiction.') }}</flux:subheading>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)]">
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ $editingId ? __('Edit entity') : __('Add entity') }}</flux:heading>
                <flux:subheading>{{ __('Entities can be updated as details change.') }}</flux:subheading>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="entity-jurisdiction">{{ __('Jurisdiction') }}</label>
                        <select
                            id="entity-jurisdiction"
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

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="entity-type">{{ __('Entity type') }}</label>
                        <select
                            id="entity-type"
                            wire:model="type"
                            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            @foreach ($entityTypes as $entityType)
                                <option value="{{ $entityType->value }}">
                                    {{ $entityType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <flux:input wire:model="name" :label="__('Entity name')" type="text" />
                    <flux:input wire:model="ein_or_tax_id" :label="__('EIN or tax ID (optional)')" type="text" />

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200" for="entity-address">{{ __('Address (optional)') }}</label>
                        <select
                            id="entity-address"
                            wire:model="address_id"
                            class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <option value="">{{ __('No address') }}</option>
                            @foreach ($addresses as $address)
                                <option value="{{ $address->id }}">{{ $address->address_line_1 }}, {{ $address->city }}</option>
                            @endforeach
                        </select>
                        <flux:button variant="ghost" size="sm" type="button" wire:click="openAddressModal">
                            {{ __('Add new address') }}
                        </flux:button>
                    </div>

                    <div class="flex items-center gap-3">
                        <flux:button variant="primary" type="submit">
                            {{ $editingId ? __('Update entity') : __('Save entity') }}
                        </flux:button>
                        @if ($editingId)
                            <flux:button variant="ghost" type="button" wire:click="cancelEdit">
                                {{ __('Cancel') }}
                            </flux:button>
                        @endif
                        <x-action-message on="entity-saved">
                            {{ __('Saved.') }}
                        </x-action-message>
                    </div>
                </form>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Existing entities') }}</flux:heading>
                <flux:subheading>{{ __('Review entities and update their details.') }}</flux:subheading>

                @if ($entities->isEmpty())
                    <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                        {{ __('No entities yet. Add your first entity to get started.') }}
                    </div>
                @else
                    <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Name') }}</th>
                                    <th class="px-4 py-3">{{ __('Type') }}</th>
                                    <th class="px-4 py-3">{{ __('Jurisdiction') }}</th>
                                    <th class="px-4 py-3">{{ __('Address') }}</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach ($entities as $entity)
                                    <tr wire:key="entity-{{ $entity->id }}">
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                            {{ $entity->name }}
                                        </td>
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                            {{ $entity->type->name }}
                                        </td>
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                            {{ $entity->jurisdiction->name }} ({{ $entity->jurisdiction->iso_code }})
                                        </td>
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                            @if ($entity->address)
                                                {{ $entity->address->address_line_1 }}, {{ $entity->address->city }}
                                            @else
                                                <span class="text-zinc-400">{{ __('No address') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                class="text-sm font-medium text-zinc-700 hover:text-zinc-900 dark:text-zinc-200 dark:hover:text-white"
                                                wire:click="edit({{ $entity->id }})"
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

        <flux:modal name="entity-address-create" focusable class="max-w-2xl">
            <form wire:submit="saveAddress" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Add address') }}</flux:heading>
                    <flux:subheading>{{ __('Save a reusable address for this entity.') }}</flux:subheading>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <flux:input wire:model="address_line_1" label="{{ __('Address Line 1') }}" type="text" />
                    <flux:input wire:model="address_line_2" label="{{ __('Address Line 2') }}" type="text" />
                    <flux:input wire:model="address_city" label="{{ __('City') }}" type="text" />
                    <flux:input wire:model="address_state" label="{{ __('State / Province') }}" type="text" />
                    <flux:input wire:model="address_postal_code" label="{{ __('Postal / ZIP Code') }}" type="text" />
                    <flux:input wire:model="address_country" label="{{ __('Country') }}" type="text" />
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled" type="button" wire:click="closeAddressModal">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">{{ __('Save Address') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    </div>
</div>
