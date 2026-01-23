<?php

use App\Enums\Finance\AssetType;
use App\Enums\Finance\OwnershipStructure;
use App\Models\Address;
use App\Models\Asset;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\TaxYear;
use App\Models\YearEndValue;
use Livewire\Component;
use Livewire\Attributes\Validate;

new class extends Component
{
    public $assets;
    public $addresses;
    public $entities;
    
    public $editingId = null;
    
    #[Validate('required|string|max:255')]
    public $name = '';
    
    #[Validate('required')]
    public $type = '';
    
    #[Validate('required|exists:entities,id')]
    public $entity_id = '';
    
    #[Validate('required')]
    public $ownership_structure = '';
    
    #[Validate('required|date')]
    public $acquisition_date = '';
    
    #[Validate('required|numeric|min:0')]
    public $acquisition_cost = '';

    #[Validate('nullable|exists:addresses,id')]
    public $address_id = '';

    public bool $showAddressForm = false;

    #[Validate('nullable|string|max:255')]
    public $address_country = '';

    #[Validate('nullable|string|max:255')]
    public $address_state = '';

    #[Validate('nullable|string|max:255')]
    public $address_city = '';

    #[Validate('nullable|string|max:255')]
    public $address_postal_code = '';

    #[Validate('nullable|string|max:255')]
    public $address_line_1 = '';

    #[Validate('nullable|string|max:255')]
    public $address_line_2 = '';

    public $yearEndAssetId = null;
    public $yearEndAssetName = '';
    public $yearEndCurrencySymbol = '';
    public $yearEndTaxYears;

    /**
     * @var array<string, string>
     */
    public $yearEndValues = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->entities = Entity::where('user_id', auth()->id())->get();
        $this->addresses = Address::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();
        $this->assets = Asset::query()
            ->whereHas('entity', fn($q) => $q->where('user_id', auth()->id()))
            ->with(['address', 'entity.jurisdiction', 'yearEndValues.taxYear'])
            ->latest()
            ->get();

        $this->assets->each(function (Asset $asset): void {
            $defaultCurrency = Currency::query()
                ->where('code', $asset->entity->jurisdiction->default_currency)
                ->first();

            $latest = $asset->yearEndValues
                ->sortByDesc(fn (YearEndValue $value) => $value->taxYear?->year ?? 0)
                ->first();

            $asset->setAttribute(
                'display_currency_symbol',
                $defaultCurrency?->symbol ?? $asset->entity->jurisdiction->default_currency ?? ''
            );
            $asset->setAttribute('latest_year_end_value', $latest);
        });
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'entity_id' => $this->entity_id,
            'ownership_structure' => $this->ownership_structure,
            'acquisition_date' => $this->acquisition_date,
            'acquisition_cost' => $this->acquisition_cost,
            'address_id' => $this->address_id ?: null,
        ];

        if ($data['address_id']) {
            $address = Address::query()
                ->where('user_id', auth()->id())
                ->find($data['address_id']);

            if (! $address) {
                abort(403);
            }
        }

        if ($this->showAddressForm) {
            $this->validate($this->addressRules());

            $address = Address::create([
                'user_id' => auth()->id(),
                'country' => $this->address_country,
                'state' => $this->address_state,
                'city' => $this->address_city,
                'postal_code' => $this->address_postal_code,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2 ?: null,
            ]);

            $data['address_id'] = $address->id;
        }

        if ($this->editingId) {
            $asset = Asset::findOrFail($this->editingId);
            
            if ($asset->entity->user_id !== auth()->id()) {
                abort(403);
            }
            
            // Don't allow changing acquisition_date or acquisition_cost
            unset($data['acquisition_date'], $data['acquisition_cost']);
            
            $asset->update($data);
        } else {
            Asset::create($data);
        }

        $this->reset([
            'name',
            'type',
            'entity_id',
            'ownership_structure',
            'acquisition_date',
            'acquisition_cost',
            'address_id',
            'editingId',
            'showAddressForm',
            'address_country',
            'address_state',
            'address_city',
            'address_postal_code',
            'address_line_1',
            'address_line_2',
        ]);
        $this->loadData();
        
        session()->flash('message', $this->editingId ? 'Asset updated successfully.' : 'Asset created successfully.');
    }

    public function edit($id)
    {
        $asset = Asset::findOrFail($id);
        
        if ($asset->entity->user_id !== auth()->id()) {
            abort(403);
        }

        $this->editingId = $asset->id;
        $this->name = $asset->name;
        $this->type = $asset->type->value;
        $this->entity_id = $asset->entity_id;
        $this->ownership_structure = $asset->ownership_structure->value;
        $this->acquisition_date = $asset->acquisition_date->format('Y-m-d');
        $this->acquisition_cost = $asset->acquisition_cost;
        $this->address_id = $asset->address_id ? (string) $asset->address_id : '';
        $this->showAddressForm = false;
    }

    public function cancel()
    {
        $this->reset([
            'name',
            'type',
            'entity_id',
            'ownership_structure',
            'acquisition_date',
            'acquisition_cost',
            'address_id',
            'editingId',
            'showAddressForm',
            'address_country',
            'address_state',
            'address_city',
            'address_postal_code',
            'address_line_1',
            'address_line_2',
        ]);
        $this->resetValidation();
    }

    public function delete($id)
    {
        $asset = Asset::findOrFail($id);
        
        if ($asset->entity->user_id !== auth()->id()) {
            abort(403);
        }
        
        $asset->delete();
        $this->loadData();
        
        session()->flash('message', 'Asset deleted successfully.');
    }

    public function openYearEndValues(int $assetId): void
    {
        $asset = Asset::query()
            ->with(['entity.jurisdiction', 'yearEndValues.taxYear'])
            ->findOrFail($assetId);

        if ($asset->entity->user_id !== auth()->id()) {
            abort(403);
        }

        $this->yearEndAssetId = $asset->id;
        $this->yearEndAssetName = $asset->name;
        $this->yearEndCurrencySymbol = Currency::query()
            ->where('code', $asset->entity->jurisdiction->default_currency)
            ->value('symbol') ?? $asset->entity->jurisdiction->default_currency ?? '';

        $this->yearEndTaxYears = TaxYear::query()
            ->where('jurisdiction_id', $asset->entity->jurisdiction_id)
            ->orderByDesc('year')
            ->get();

        $existing = $asset->yearEndValues->keyBy('tax_year_id');

        $this->yearEndValues = $this->yearEndTaxYears
            ->mapWithKeys(fn (TaxYear $taxYear) => [
                (string) $taxYear->id => $existing->get($taxYear->id)?->amount ? (string) $existing->get($taxYear->id)->amount : '',
            ])
            ->toArray();

        $this->resetValidation();
        $this->dispatch('modal-show', name: 'asset-year-end-values');
    }

    public function saveYearEndValues(): void
    {
        if (! $this->yearEndAssetId) {
            return;
        }

        $this->validate($this->yearEndValueRules());

        $asset = Asset::query()
            ->with('entity')
            ->findOrFail($this->yearEndAssetId);

        if ($asset->entity->user_id !== auth()->id()) {
            abort(403);
        }

        $taxYearIds = TaxYear::query()
            ->where('jurisdiction_id', $asset->entity->jurisdiction_id)
            ->pluck('id')
            ->all();

        $existing = YearEndValue::query()
            ->where('asset_id', $asset->id)
            ->whereIn('tax_year_id', $taxYearIds)
            ->get()
            ->keyBy('tax_year_id');

        foreach ($this->yearEndValues as $taxYearId => $amount) {
            if (! in_array((int) $taxYearId, $taxYearIds, true)) {
                continue;
            }

            $normalizedAmount = is_string($amount) ? trim($amount) : $amount;

            if ($normalizedAmount === '' || $normalizedAmount === null) {
                if ($existing->has((int) $taxYearId)) {
                    $existing->get((int) $taxYearId)?->delete();
                }

                continue;
            }

            YearEndValue::updateOrCreate(
                [
                    'asset_id' => $asset->id,
                    'tax_year_id' => (int) $taxYearId,
                ],
                [
                    'entity_id' => $asset->entity_id,
                    'amount' => $normalizedAmount,
                    'account_id' => null,
                ]
            );
        }

        $this->loadData();
        $this->dispatch('modal-close', name: 'asset-year-end-values');

        session()->flash('message', __('Year-end values updated successfully.'));
    }

    public function closeYearEndValues(): void
    {
        $this->reset(['yearEndAssetId', 'yearEndAssetName', 'yearEndCurrencySymbol', 'yearEndTaxYears', 'yearEndValues']);
        $this->resetValidation();
        $this->dispatch('modal-close', name: 'asset-year-end-values');
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function yearEndValueRules(): array
    {
        return [
            'yearEndValues' => ['array'],
            'yearEndValues.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function addressRules(): array
    {
        return [
            'address_country' => ['required', 'string', 'max:255'],
            'address_state' => ['required', 'string', 'max:255'],
            'address_city' => ['required', 'string', 'max:255'],
            'address_postal_code' => ['required', 'string', 'max:255'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
        ];
    }
};
?>

<div class="space-y-6">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,400px)_minmax(0,1fr)]">
        <!-- Form Section -->
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ $editingId ? __('Edit Asset') : __('Add Asset') }}</flux:heading>
            <flux:subheading>{{ __('Manage rental properties and other assets.') }}</flux:subheading>

        @if (session()->has('message'))
            <div class="mt-4 rounded-md bg-green-50 p-4 dark:bg-green-900/20">
                <p class="text-sm text-green-800 dark:text-green-200">{{ session('message') }}</p>
            </div>
        @endif

            <form wire:submit="save" class="mt-5 space-y-4">
                <flux:input wire:model="name" label="{{ __('Asset Name') }}" type="text" />

            <flux:select wire:model="type" label="{{ __('Asset Type') }}" placeholder="{{ __('Select type') }}">
                @foreach(AssetType::cases() as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="entity_id" label="{{ __('Entity') }}" placeholder="{{ __('Select entity') }}">
                @foreach($entities as $entity)
                    <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                @endforeach
            </flux:select>

            @if($entity_id)
                @php
                    $selectedEntity = $entities->firstWhere('id', (int) $entity_id);
                @endphp
                @if($selectedEntity)
                    <div class="rounded-lg border border-dashed border-zinc-200 px-3 py-2 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                        {{ __('Jurisdiction') }}: {{ $selectedEntity->jurisdiction->name }} ({{ $selectedEntity->jurisdiction->iso_code }})
                    </div>
                @endif
            @endif

            <flux:select wire:model="ownership_structure" label="{{ __('Ownership Structure') }}" placeholder="{{ __('Select structure') }}">
                @foreach(OwnershipStructure::cases() as $structure)
                    <option value="{{ $structure->value }}">{{ $structure->label() }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="acquisition_date" label="{{ __('Acquisition Date') }}" type="date" :disabled="!!$editingId" />

            <flux:input wire:model="acquisition_cost" label="{{ __('Acquisition Cost') }}" type="number" step="0.01" :disabled="!!$editingId" />

            <div class="space-y-2">
                <flux:select wire:model="address_id" label="{{ __('Address (Optional)') }}" placeholder="{{ __('Select address') }}">
                    <option value="">{{ __('No address') }}</option>
                    @foreach($addresses as $address)
                        <option value="{{ $address->id }}">{{ $address->address_line_1 }}, {{ $address->city }}</option>
                    @endforeach
                </flux:select>
                <flux:button type="button" variant="ghost" size="sm" wire:click="$toggle('showAddressForm')">
                    {{ $showAddressForm ? __('Cancel new address') : __('Add new address') }}
                </flux:button>
            </div>

            @if($showAddressForm)
                <div class="grid gap-3 md:grid-cols-2">
                    <flux:input wire:model="address_line_1" label="{{ __('Address Line 1') }}" type="text" />
                    <flux:input wire:model="address_line_2" label="{{ __('Address Line 2') }}" type="text" />
                    <flux:input wire:model="address_city" label="{{ __('City') }}" type="text" />
                    <flux:input wire:model="address_state" label="{{ __('State / Province') }}" type="text" />
                    <flux:input wire:model="address_postal_code" label="{{ __('Postal / ZIP Code') }}" type="text" />
                    <flux:input wire:model="address_country" label="{{ __('Country') }}" type="text" />
                </div>
            @endif

                <div class="flex items-center gap-3">
                    <flux:button type="submit" variant="primary">
                        {{ $editingId ? __('Update Asset') : __('Create Asset') }}
                    </flux:button>
                    
                    @if($editingId)
                        <flux:button wire:click="cancel" variant="ghost">{{ __('Cancel') }}</flux:button>
                    @endif
                </div>
            </form>
        </section>

    <!-- List Section -->
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Your Assets') }}</flux:heading>
            <flux:subheading>{{ __('View and manage all your properties and assets.') }}</flux:subheading>

            @if($assets->isEmpty())
                <div class="mt-6 text-center">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No assets yet. Create your first one!') }}</p>
                </div>
            @else
                <div class="mt-6 space-y-3">
                    @foreach($assets as $asset)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-medium text-zinc-900 dark:text-zinc-100">{{ $asset->name }}</h3>
                                    <flux:badge size="sm" color="blue">{{ $asset->type->label() }}</flux:badge>
                                </div>
                                <div class="mt-1 flex items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                                    <span>{{ $asset->entity->name }}</span>
                                    <span>•</span>
                                    <span>{{ $asset->entity->jurisdiction->name }}</span>
                                    <span>•</span>
                                    <span>{{ $asset->ownership_structure->label() }}</span>
                                    @if($asset->latest_year_end_value)
                                        <span>•</span>
                                        <span>
                                            {{ __('Latest Year-End:') }}
                                            {{ $asset->display_currency_symbol }}{{ number_format($asset->latest_year_end_value->amount, 2) }}
                                            ({{ $asset->latest_year_end_value->taxYear->year }})
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    <span>Acquired: {{ $asset->acquisition_date->format('M Y') }}</span>
                                    <span class="mx-2">•</span>
                                    <span>Cost: {{ $asset->display_currency_symbol }}{{ number_format($asset->acquisition_cost, 2) }}</span>
                                    @if($asset->address)
                                        <span class="mx-2">•</span>
                                        <span>{{ $asset->address->address_line_1 }}, {{ $asset->address->city }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:button wire:click="openYearEndValues({{ $asset->id }})" size="sm" variant="ghost">
                                    {{ __('Year-End Values') }}
                                </flux:button>
                                <flux:button wire:click="edit({{ $asset->id }})" size="sm" variant="ghost">
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button 
                                    wire:click="delete({{ $asset->id }})" 
                                    wire:confirm="Are you sure you want to delete this asset?"
                                    size="sm" 
                                    variant="danger"
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

<flux:modal name="asset-year-end-values" focusable class="max-w-2xl">
    <form wire:submit="saveYearEndValues" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Year-End Values') }}</flux:heading>
            <flux:subheading>
                {{ __('Update year-end values for :asset.', ['asset' => $yearEndAssetName]) }}
            </flux:subheading>
        </div>

        <div class="space-y-3">
            @forelse($yearEndTaxYears ?? [] as $taxYear)
                <div class="flex items-center gap-3">
                    <div class="w-24 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ $taxYear->year }}
                    </div>
                    <flux:input
                        wire:model="yearEndValues.{{ $taxYear->id }}"
                        type="number"
                        step="0.01"
                        placeholder="{{ __('Value') }}"
                    />
                    @if($yearEndCurrencySymbol)
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $yearEndCurrencySymbol }}
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No tax years available for this entity.') }}
                </p>
            @endforelse
        </div>

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="filled" wire:click="closeYearEndValues">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" type="submit">{{ __('Save Values') }}</flux:button>
        </div>
    </form>
</flux:modal>
</div>