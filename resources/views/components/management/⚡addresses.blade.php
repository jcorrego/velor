<?php

use App\Models\Address;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public $addresses;

    public $editingId = null;

    #[Validate('required|string|max:255')]
    public $country = '';

    #[Validate('required|string|max:255')]
    public $state = '';

    #[Validate('required|string|max:255')]
    public $city = '';

    #[Validate('required|string|max:255')]
    public $postal_code = '';

    #[Validate('required|string|max:255')]
    public $address_line_1 = '';

    #[Validate('nullable|string|max:255')]
    public $address_line_2 = '';

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->addresses = Address::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'user_id' => auth()->id(),
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2 ?: null,
        ];

        if ($this->editingId) {
            $address = Address::query()->where('user_id', auth()->id())->findOrFail($this->editingId);
            $address->update($data);
        } else {
            Address::create($data);
        }

        $this->reset([
            'country',
            'state',
            'city',
            'postal_code',
            'address_line_1',
            'address_line_2',
            'editingId',
        ]);

        $this->loadData();
        session()->flash('message', $this->editingId ? __('Address updated successfully.') : __('Address created successfully.'));
    }

    public function edit(int $id): void
    {
        $address = Address::query()->where('user_id', auth()->id())->findOrFail($id);

        $this->editingId = $address->id;
        $this->country = $address->country;
        $this->state = $address->state;
        $this->city = $address->city;
        $this->postal_code = $address->postal_code;
        $this->address_line_1 = $address->address_line_1;
        $this->address_line_2 = $address->address_line_2;
    }

    public function cancel(): void
    {
        $this->reset([
            'country',
            'state',
            'city',
            'postal_code',
            'address_line_1',
            'address_line_2',
            'editingId',
        ]);
        $this->resetValidation();
    }

    public function delete(int $id): void
    {
        $address = Address::query()->where('user_id', auth()->id())->findOrFail($id);
        $address->delete();
        $this->loadData();

        session()->flash('message', __('Address deleted successfully.'));
    }
};
?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Addresses') }}</flux:heading>
        <flux:subheading>{{ __('Manage reusable addresses for assets and entities.') }}</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)]">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ $editingId ? __('Edit address') : __('Add address') }}</flux:heading>
            <flux:subheading>{{ __('Addresses can be reused across assets.') }}</flux:subheading>

            @if (session()->has('message'))
                <div class="mt-4 rounded-md bg-green-50 p-4 dark:bg-green-900/20">
                    <p class="text-sm text-green-800 dark:text-green-200">{{ session('message') }}</p>
                </div>
            @endif

            <form wire:submit="save" class="mt-5 space-y-4">
                <flux:input wire:model="address_line_1" label="{{ __('Address Line 1') }}" type="text" />
                <flux:input wire:model="address_line_2" label="{{ __('Address Line 2') }}" type="text" />

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="city" label="{{ __('City') }}" type="text" />
                    <flux:input wire:model="state" label="{{ __('State / Province') }}" type="text" />
                    <flux:input wire:model="postal_code" label="{{ __('Postal / ZIP Code') }}" type="text" />
                    <flux:input wire:model="country" label="{{ __('Country') }}" type="text" />
                </div>

                <div class="flex items-center gap-3">
                    <flux:button variant="primary" type="submit">
                        {{ $editingId ? __('Update address') : __('Save address') }}
                    </flux:button>
                    @if ($editingId)
                        <flux:button variant="ghost" type="button" wire:click="cancel">
                            {{ __('Cancel') }}
                        </flux:button>
                    @endif
                </div>
            </form>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Existing addresses') }}</flux:heading>
            <flux:subheading>{{ __('Review and update saved addresses.') }}</flux:subheading>

            @if ($addresses->isEmpty())
                <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                    {{ __('No addresses yet. Add your first address to get started.') }}
                </div>
            @else
                <div class="mt-6 space-y-3">
                    @foreach ($addresses as $address)
                        <div class="flex items-start justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700" wire:key="address-{{ $address->id }}">
                            <div class="space-y-1 text-sm text-zinc-700 dark:text-zinc-200">
                                <div class="font-medium">{{ $address->address_line_1 }}</div>
                                @if ($address->address_line_2)
                                    <div>{{ $address->address_line_2 }}</div>
                                @endif
                                <div>{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</div>
                                <div>{{ $address->country }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:button variant="ghost" size="sm" wire:click="edit({{ $address->id }})">
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button
                                    variant="danger"
                                    size="sm"
                                    wire:click="delete({{ $address->id }})"
                                    wire:confirm="{{ __('Are you sure you want to delete this address?') }}"
                                >
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
