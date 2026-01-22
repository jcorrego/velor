<?php

use App\Finance\Services\FxRateService;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\TaxYear;
use App\Models\YearEndValue;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;

new class extends Component
{
    public $values;
    public $entities;
    public $taxYears;
    public $accounts;
    public $assets;
    public $currencies;

    public $editingId = null;
    public $valueType = 'account';

    public $entity_id = '';
    public $tax_year_id = '';
    public $account_id = '';
    public $asset_id = '';
    public $currency_id = '';
    public $amount = '';
    public $as_of_date = '';

    public float $totalAssets = 0.0;
    public string $totalCurrencyCode = 'EUR';
    public string $totalCurrencySymbol = '€';

    public function mount(): void
    {
        $this->loadData();
    }

    public function updatedEntityId(): void
    {
        $this->tax_year_id = '';
        $this->account_id = '';
        $this->asset_id = '';
        $this->loadData();
    }

    public function updatedTaxYearId(): void
    {
        if ($this->tax_year_id && ! $this->editingId) {
            $taxYear = TaxYear::query()->find($this->tax_year_id);

            if ($taxYear) {
                $this->as_of_date = Carbon::create($taxYear->year, 12, 31)->format('Y-m-d');
            }
        }

        $this->loadData();
    }

    public function updatedValueType(): void
    {
        $this->account_id = '';
        $this->asset_id = '';
        $this->loadData();
    }

    public function updatedAccountId(): void
    {
        if (! $this->account_id) {
            return;
        }

        $account = Account::query()->find($this->account_id);

        if ($account) {
            $this->currency_id = (string) $account->currency_id;
        }
    }

    public function updatedAssetId(): void
    {
        if (! $this->asset_id) {
            return;
        }

        $asset = Asset::query()->find($this->asset_id);

        if ($asset) {
            $this->currency_id = (string) $asset->acquisition_currency_id;
        }
    }

    public function loadData(): void
    {
        $this->entities = Entity::query()
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        $this->currencies = Currency::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $this->taxYears = $this->loadTaxYears();
        $this->accounts = $this->loadAccounts();
        $this->assets = $this->loadAssets();

        $valuesQuery = YearEndValue::query()
            ->whereHas('entity', fn ($query) => $query->where('user_id', auth()->id()))
            ->with(['entity', 'taxYear', 'account', 'asset', 'currency'])
            ->orderByDesc('as_of_date');

        if ($this->entity_id) {
            $valuesQuery->where('entity_id', $this->entity_id);
        }

        if ($this->tax_year_id) {
            $valuesQuery->where('tax_year_id', $this->tax_year_id);
        }

        $this->values = $valuesQuery->get();
        $this->recalculateTotals();
    }

    public function save(): void
    {
        $rules = [
            'entity_id' => ['required', 'integer', 'exists:entities,id'],
            'tax_year_id' => ['required', 'integer', 'exists:tax_years,id'],
            'valueType' => ['required', Rule::in(['account', 'asset'])],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'as_of_date' => ['required', 'date'],
        ];

        if ($this->valueType === 'account') {
            $rules['account_id'] = [
                'required',
                'integer',
                'exists:accounts,id',
                $this->uniqueAccountRule(),
            ];
        } else {
            $rules['asset_id'] = [
                'required',
                'integer',
                'exists:assets,id',
                $this->uniqueAssetRule(),
            ];
        }

        $validated = $this->validate($rules, [
            'entity_id.required' => __('The entity is required.'),
            'entity_id.exists' => __('The selected entity is invalid.'),
            'tax_year_id.required' => __('The tax year is required.'),
            'tax_year_id.exists' => __('The selected tax year is invalid.'),
            'account_id.required' => __('The account is required.'),
            'account_id.exists' => __('The selected account is invalid.'),
            'account_id.unique' => __('A year-end value already exists for this account and tax year.'),
            'asset_id.required' => __('The asset is required.'),
            'asset_id.exists' => __('The selected asset is invalid.'),
            'asset_id.unique' => __('A year-end value already exists for this asset and tax year.'),
            'currency_id.required' => __('The currency is required.'),
            'currency_id.exists' => __('The selected currency is invalid.'),
            'amount.required' => __('The amount is required.'),
            'amount.numeric' => __('The amount must be a number.'),
            'amount.min' => __('The amount must be greater than zero.'),
            'as_of_date.required' => __('The as-of date is required.'),
            'as_of_date.date' => __('The as-of date must be a valid date.'),
        ]);

        $entity = Entity::query()
            ->where('user_id', auth()->id())
            ->findOrFail($validated['entity_id']);

        $taxYear = TaxYear::query()->findOrFail($validated['tax_year_id']);

        if ($taxYear->jurisdiction_id !== $entity->jurisdiction_id) {
            $this->addError('tax_year_id', __('The tax year must match the entity jurisdiction.'));

            return;
        }

        $accountId = null;
        $assetId = null;

        if ($this->valueType === 'account') {
            $account = Account::query()->with('entity')->findOrFail($validated['account_id']);

            if ($account->entity->user_id !== auth()->id() || $account->entity_id !== $entity->id) {
                abort(403);
            }

            $accountId = $account->id;
        }

        if ($this->valueType === 'asset') {
            $asset = Asset::query()->with('entity')->findOrFail($validated['asset_id']);

            if ($asset->entity->user_id !== auth()->id() || $asset->entity_id !== $entity->id) {
                abort(403);
            }

            $assetId = $asset->id;
        }

        $payload = [
            'entity_id' => $entity->id,
            'tax_year_id' => $taxYear->id,
            'account_id' => $accountId,
            'asset_id' => $assetId,
            'currency_id' => $validated['currency_id'],
            'amount' => $validated['amount'],
            'as_of_date' => $validated['as_of_date'],
        ];

        $isEditing = (bool) $this->editingId;

        if ($this->editingId) {
            $value = YearEndValue::query()->with('entity')->findOrFail($this->editingId);

            if ($value->entity->user_id !== auth()->id()) {
                abort(403);
            }

            $value->update($payload);
        } else {
            YearEndValue::create($payload);
        }

        $this->reset(['account_id', 'asset_id', 'currency_id', 'amount', 'as_of_date', 'editingId']);
        $this->loadData();

        session()->flash('message', $isEditing ? __('Year-end value updated successfully.') : __('Year-end value created successfully.'));
    }

    public function edit(int $id): void
    {
        $value = YearEndValue::query()->with(['entity', 'taxYear'])->findOrFail($id);

        if ($value->entity->user_id !== auth()->id()) {
            abort(403);
        }

        $this->editingId = $value->id;
        $this->entity_id = (string) $value->entity_id;
        $this->tax_year_id = (string) $value->tax_year_id;
        $this->valueType = $value->account_id ? 'account' : 'asset';
        $this->account_id = $value->account_id ? (string) $value->account_id : '';
        $this->asset_id = $value->asset_id ? (string) $value->asset_id : '';
        $this->currency_id = (string) $value->currency_id;
        $this->amount = (string) $value->amount;
        $this->as_of_date = $value->as_of_date->format('Y-m-d');

        $this->loadData();
    }

    public function cancel(): void
    {
        $this->reset(['account_id', 'asset_id', 'currency_id', 'amount', 'as_of_date', 'editingId']);
        $this->resetValidation();
    }

    public function delete(int $id): void
    {
        $value = YearEndValue::query()->with('entity')->findOrFail($id);

        if ($value->entity->user_id !== auth()->id()) {
            abort(403);
        }

        $value->delete();
        $this->loadData();

        session()->flash('message', __('Year-end value deleted successfully.'));
    }

    /**
     * @return Collection<int, TaxYear>
     */
    private function loadTaxYears(): Collection
    {
        if (! $this->entity_id) {
            return TaxYear::query()->with('jurisdiction')->orderByDesc('year')->get();
        }

        $entity = Entity::query()->where('user_id', auth()->id())->find($this->entity_id);

        if (! $entity) {
            return collect();
        }

        return TaxYear::query()
            ->with('jurisdiction')
            ->where('jurisdiction_id', $entity->jurisdiction_id)
            ->orderByDesc('year')
            ->get();
    }

    /**
     * @return Collection<int, Account>
     */
    private function loadAccounts(): Collection
    {
        $query = Account::query()
            ->whereHas('entity', fn ($builder) => $builder->where('user_id', auth()->id()))
            ->with(['entity', 'currency'])
            ->orderBy('name');

        if ($this->entity_id) {
            $query->where('entity_id', $this->entity_id);
        }

        return $query->get();
    }

    /**
     * @return Collection<int, Asset>
     */
    private function loadAssets(): Collection
    {
        $query = Asset::query()
            ->whereHas('entity', fn ($builder) => $builder->where('user_id', auth()->id()))
            ->with(['entity', 'jurisdiction'])
            ->orderBy('name');

        if ($this->entity_id) {
            $query->where('entity_id', $this->entity_id);
        }

        return $query->get();
    }

    private function recalculateTotals(): void
    {
        $this->totalAssets = 0.0;

        $baseCurrency = Currency::query()->where('code', 'EUR')->first();
        $this->totalCurrencyCode = $baseCurrency?->code ?? 'EUR';
        $this->totalCurrencySymbol = $baseCurrency?->symbol ?? '€';

        if (! $this->entity_id || ! $this->tax_year_id || $this->values->isEmpty()) {
            return;
        }

        $fxRateService = app(FxRateService::class);

        foreach ($this->values as $value) {
            if (! $value->currency || ! $baseCurrency) {
                $this->totalAssets += (float) $value->amount;
                continue;
            }

            $converted = $fxRateService->convert(
                (float) $value->amount,
                $value->currency,
                $baseCurrency,
                $value->as_of_date
            );

            $this->totalAssets += $converted;
        }
    }

    private function uniqueAccountRule(): Unique
    {
        return Rule::unique('year_end_values', 'account_id')
            ->where(fn ($query) => $query
                ->where('entity_id', $this->entity_id)
                ->where('tax_year_id', $this->tax_year_id)
                ->where('account_id', $this->account_id))
            ->ignore($this->editingId);
    }

    private function uniqueAssetRule(): Unique
    {
        return Rule::unique('year_end_values', 'asset_id')
            ->where(fn ($query) => $query
                ->where('entity_id', $this->entity_id)
                ->where('tax_year_id', $this->tax_year_id)
                ->where('asset_id', $this->asset_id))
            ->ignore($this->editingId);
    }
};
?>

<div class="grid gap-6 lg:grid-cols-[minmax(0,420px)_minmax(0,1fr)]">
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ $editingId ? __('Edit Year-End Value') : __('Add Year-End Value') }}</flux:heading>
        <flux:subheading>{{ __('Record year-end account and asset values for reporting.') }}</flux:subheading>

        @if (session()->has('message'))
            <div class="mt-4 rounded-md bg-green-50 p-4 dark:bg-green-900/20">
                <p class="text-sm text-green-800 dark:text-green-200">{{ session('message') }}</p>
            </div>
        @endif

        <form wire:submit="save" class="mt-5 space-y-4">
            <flux:select wire:model.live="entity_id" label="{{ __('Entity') }}" placeholder="{{ __('Select entity') }}">
                <option value="">{{ __('Select entity') }}</option>
                @foreach ($entities as $entity)
                    <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="tax_year_id" wire:key="year-end-tax-year-{{ $entity_id ?: 'all' }}" label="{{ __('Tax Year') }}" placeholder="{{ __('Select tax year') }}">
                <option value="">{{ __('Select tax year') }}</option>
                @foreach ($taxYears as $taxYear)
                    <option value="{{ $taxYear->id }}">{{ $taxYear->year }} — {{ $taxYear->jurisdiction->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="valueType" label="{{ __('Value Type') }}">
                <option value="account">{{ __('Account') }}</option>
                <option value="asset">{{ __('Asset') }}</option>
            </flux:select>

            @if ($valueType === 'account')
                <flux:select wire:model.live="account_id" wire:key="year-end-account-{{ $entity_id ?: 'all' }}" label="{{ __('Account') }}" placeholder="{{ __('Select account') }}">
                    <option value="">{{ __('Select account') }}</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }} — {{ $account->entity->name }}</option>
                    @endforeach
                </flux:select>
            @else
                <flux:select wire:model.live="asset_id" wire:key="year-end-asset-{{ $entity_id ?: 'all' }}" label="{{ __('Asset') }}" placeholder="{{ __('Select asset') }}">
                    <option value="">{{ __('Select asset') }}</option>
                    @foreach ($assets as $asset)
                        <option value="{{ $asset->id }}">{{ $asset->name }} — {{ $asset->entity->name }}</option>
                    @endforeach
                </flux:select>
            @endif

            <flux:select wire:model="currency_id" label="{{ __('Currency') }}" placeholder="{{ __('Select currency') }}">
                <option value="">{{ __('Select currency') }}</option>
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}">{{ $currency->code }} — {{ $currency->name }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="amount" label="{{ __('Amount') }}" type="number" step="0.01" />

            <flux:input wire:model="as_of_date" label="{{ __('As-of Date') }}" type="date" />

            <div class="flex items-center gap-3">
                <flux:button type="submit" variant="primary">
                    {{ $editingId ? __('Update Value') : __('Create Value') }}
                </flux:button>

                @if ($editingId)
                    <flux:button wire:click="cancel" variant="ghost">{{ __('Cancel') }}</flux:button>
                @endif
            </div>
        </form>
    </section>

    <section class="space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Totals') }}</flux:heading>
            <flux:subheading>{{ __('Total year-end assets for the selected entity and tax year.') }}</flux:subheading>

            <div class="mt-4 rounded-lg border border-dashed border-zinc-200 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                @if (! $entity_id || ! $tax_year_id)
                    {{ __('Select an entity and tax year to see totals.') }}
                @else
                    <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total Assets') }}</div>
                    <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $totalCurrencySymbol }}{{ number_format($totalAssets, 2) }} {{ $totalCurrencyCode }}
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Year-End Values') }}</flux:heading>
            <flux:subheading>{{ __('Review and manage stored year-end values.') }}</flux:subheading>

            @if ($values->isEmpty())
                <div class="mt-6 text-center">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No year-end values yet.') }}</p>
                </div>
            @else
                <div class="mt-6 space-y-3">
                    @foreach ($values as $value)
                        <div wire:key="year-end-value-{{ $value->id }}" class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $value->account?->name ?? $value->asset?->name ?? __('Value') }}
                                    </h3>
                                    <flux:badge size="sm" color="zinc">
                                        {{ $value->account_id ? __('Account') : __('Asset') }}
                                    </flux:badge>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                    <span>{{ $value->entity->name }}</span>
                                    <span>•</span>
                                    <span>{{ $value->taxYear->year }}</span>
                                    <span>•</span>
                                    <span>{{ $value->as_of_date->format('M d, Y') }}</span>
                                    <span>•</span>
                                    <span>{{ $value->currency?->symbol }}{{ number_format($value->amount, 2) }} {{ $value->currency?->code }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:button wire:click="edit({{ $value->id }})" size="sm" variant="ghost">
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button
                                    wire:click="delete({{ $value->id }})"
                                    wire:confirm="{{ __('Are you sure you want to delete this value?') }}"
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
        </div>
    </section>
</div>
