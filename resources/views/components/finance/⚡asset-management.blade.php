<?php

use App\Enums\Finance\AssetType;
use App\Enums\Finance\OwnershipStructure;
use App\Models\Asset;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\Jurisdiction;
use App\Models\TaxYear;
use App\Models\YearEndValue;
use Livewire\Component;
use Livewire\Attributes\Validate;

new class extends Component
{
    public $assets;
    public $entities;
    public $jurisdictions;
    public $currencies;
    
    public $editingId = null;
    
    #[Validate('required|string|max:255')]
    public $name = '';
    
    #[Validate('required')]
    public $type = '';
    
    #[Validate('required|exists:jurisdictions,id')]
    public $jurisdiction_id = '';
    
    #[Validate('required|exists:entities,id')]
    public $entity_id = '';
    
    #[Validate('required')]
    public $ownership_structure = '';
    
    #[Validate('required|date')]
    public $acquisition_date = '';
    
    #[Validate('required|numeric|min:0')]
    public $acquisition_cost = '';
    
    #[Validate('required|exists:currencies,id')]
    public $acquisition_currency_id = '';
    
    #[Validate('nullable|string')]
    public $depreciation_method = 'straight-line';
    
    #[Validate('nullable|integer|min:1')]
    public $useful_life_years = '';
    
    #[Validate('nullable|numeric|min:0')]
    public $annual_depreciation_amount = '';

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
        $this->jurisdictions = Jurisdiction::all();
        $this->currencies = Currency::where('is_active', true)->get();
        $this->assets = Asset::query()
            ->whereHas('entity', fn($q) => $q->where('user_id', auth()->id()))
            ->with(['entity', 'jurisdiction', 'acquisitionCurrency', 'yearEndValues.taxYear'])
            ->latest()
            ->get();

        $this->assets->each(function (Asset $asset): void {
            $latest = $asset->yearEndValues
                ->sortByDesc(fn (YearEndValue $value) => $value->taxYear?->year ?? 0)
                ->first();

            $asset->setAttribute('latest_year_end_value', $latest);
        });
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'jurisdiction_id' => $this->jurisdiction_id,
            'entity_id' => $this->entity_id,
            'ownership_structure' => $this->ownership_structure,
            'acquisition_date' => $this->acquisition_date,
            'acquisition_cost' => $this->acquisition_cost,
            'acquisition_currency_id' => $this->acquisition_currency_id,
            'depreciation_method' => $this->depreciation_method,
            'useful_life_years' => $this->useful_life_years,
            'annual_depreciation_amount' => $this->annual_depreciation_amount,
        ];

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

        $this->reset(['name', 'type', 'jurisdiction_id', 'entity_id', 'ownership_structure', 
                     'acquisition_date', 'acquisition_cost', 'acquisition_currency_id', 
                     'depreciation_method', 'useful_life_years', 'annual_depreciation_amount', 'editingId']);
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
        $this->jurisdiction_id = $asset->jurisdiction_id;
        $this->entity_id = $asset->entity_id;
        $this->ownership_structure = $asset->ownership_structure->value;
        $this->acquisition_date = $asset->acquisition_date->format('Y-m-d');
        $this->acquisition_cost = $asset->acquisition_cost;
        $this->acquisition_currency_id = $asset->acquisition_currency_id;
        $this->depreciation_method = $asset->depreciation_method;
        $this->useful_life_years = $asset->useful_life_years;
        $this->annual_depreciation_amount = $asset->annual_depreciation_amount;
    }

    public function cancel()
    {
        $this->reset(['name', 'type', 'jurisdiction_id', 'entity_id', 'ownership_structure', 
                     'acquisition_date', 'acquisition_cost', 'acquisition_currency_id', 
                     'depreciation_method', 'useful_life_years', 'annual_depreciation_amount', 'editingId']);
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
            ->with(['entity', 'acquisitionCurrency', 'yearEndValues.taxYear'])
            ->findOrFail($assetId);

        if ($asset->entity->user_id !== auth()->id()) {
            abort(403);
        }

        $this->yearEndAssetId = $asset->id;
        $this->yearEndAssetName = $asset->name;
        $this->yearEndCurrencySymbol = $asset->acquisitionCurrency?->symbol ?? '';

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

            <flux:select wire:model="jurisdiction_id" label="{{ __('Jurisdiction') }}" placeholder="{{ __('Select jurisdiction') }}">
                @foreach($jurisdictions as $jurisdiction)
                    <option value="{{ $jurisdiction->id }}">{{ $jurisdiction->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="ownership_structure" label="{{ __('Ownership Structure') }}" placeholder="{{ __('Select structure') }}">
                @foreach(OwnershipStructure::cases() as $structure)
                    <option value="{{ $structure->value }}">{{ $structure->label() }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="acquisition_date" label="{{ __('Acquisition Date') }}" type="date" :disabled="!!$editingId" />

            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="acquisition_cost" label="{{ __('Acquisition Cost') }}" type="number" step="0.01" :disabled="!!$editingId" />
                
                <flux:select wire:model="acquisition_currency_id" label="{{ __('Currency') }}" :disabled="!!$editingId">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}">{{ $currency->code }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:separator />

            <flux:input wire:model="useful_life_years" label="{{ __('Useful Life (Years)') }}" type="number" />

            <flux:input wire:model="annual_depreciation_amount" label="{{ __('Annual Depreciation') }}" type="number" step="0.01" />

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
                                    <span>{{ $asset->jurisdiction->name }}</span>
                                    <span>•</span>
                                    <span>{{ $asset->ownership_structure->label() }}</span>
                                    @if($asset->latest_year_end_value)
                                        <span>•</span>
                                        <span>
                                            {{ __('Latest Year-End:') }}
                                            {{ $asset->acquisitionCurrency->symbol }}{{ number_format($asset->latest_year_end_value->amount, 2) }}
                                            ({{ $asset->latest_year_end_value->taxYear->year }})
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    <span>Acquired: {{ $asset->acquisition_date->format('M Y') }}</span>
                                    <span class="mx-2">•</span>
                                    <span>Cost: {{ $asset->acquisitionCurrency->symbol }}{{ number_format($asset->acquisition_cost, 2) }}</span>
                                    @if($asset->annual_depreciation_amount)
                                        <span class="mx-2">•</span>
                                        <span>Depreciation: {{ $asset->acquisitionCurrency->symbol }}{{ number_format($asset->annual_depreciation_amount, 2) }}/yr</span>
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