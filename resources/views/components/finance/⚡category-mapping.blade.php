<?php

use App\Enums\Finance\TaxFormCode;
use App\Models\CategoryTaxMapping;
use App\Models\TransactionCategory;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public $categories;
    public $mappings;

    public $filterCategoryId = '';
    public $filterTaxFormCode = '';

    public $category_id = '';
    public $tax_form_code = '';
    public $line_item = '';
    public $country = '';

    public function mount()
    {
        $this->loadData();
    }

    public function updatedTaxFormCode()
    {
        if (! $this->tax_form_code) {
            $this->country = '';

            return;
        }

        $code = TaxFormCode::tryFrom($this->tax_form_code);
        $this->country = $code?->country() ?? $this->country;
    }

    public function updatedFilterCategoryId()
    {
        $this->loadData();
    }

    public function updatedFilterTaxFormCode()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->categories = TransactionCategory::query()
            ->orderBy('name')
            ->get();

        $query = CategoryTaxMapping::query()
            ->with('transactionCategory');

        if ($this->filterCategoryId) {
            $query->where('category_id', $this->filterCategoryId);
        }

        if ($this->filterTaxFormCode) {
            $query->where('tax_form_code', $this->filterTaxFormCode);
        }

        $this->mappings = $query->orderBy('tax_form_code')->orderBy('line_item')->get();
    }

    public function save()
    {
        $this->validate([
            'category_id' => ['required', 'integer', 'exists:transaction_categories,id'],
            'tax_form_code' => [
                'required',
                'string',
                Rule::in(array_map(
                    fn (TaxFormCode $code) => $code->value,
                    TaxFormCode::cases()
                )),
                Rule::unique('category_tax_mappings', 'tax_form_code')
                    ->where('category_id', $this->category_id)
                    ->where('line_item', $this->line_item),
            ],
            'line_item' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:100'],
        ], [
            'tax_form_code.unique' => 'This tax form mapping already exists for the selected category.',
        ]);

        CategoryTaxMapping::create([
            'category_id' => $this->category_id,
            'tax_form_code' => $this->tax_form_code,
            'line_item' => $this->line_item,
            'country' => $this->country,
        ]);

        $this->reset(['tax_form_code', 'line_item', 'country']);
        $this->loadData();

        session()->flash('message', 'Tax mapping created successfully.');
    }

    public function delete($id)
    {
        $mapping = CategoryTaxMapping::query()->findOrFail($id);

        $mapping->delete();
        $this->loadData();

        session()->flash('message', 'Tax mapping deleted successfully.');
    }
};
?>

<div class="grid gap-6 lg:grid-cols-[minmax(0,420px)_minmax(0,1fr)]">
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('Add Tax Mapping') }}</flux:heading>
        <flux:subheading>{{ __('Map categories to Schedule E, IRPF, and other tax forms.') }}</flux:subheading>

        @if (session()->has('message'))
            <div class="mt-4 rounded-md bg-green-50 p-4 dark:bg-green-900/20">
                <p class="text-sm text-green-800 dark:text-green-200">{{ session('message') }}</p>
            </div>
        @endif

        <form wire:submit="save" class="mt-5 space-y-4">
            <flux:select wire:model="category_id" label="{{ __('Category') }}" placeholder="{{ __('Select category') }}">
                <option value="">{{ __('Select category') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="tax_form_code" label="{{ __('Tax Form') }}" placeholder="{{ __('Select form') }}">
                <option value="">{{ __('Select form') }}</option>
                @foreach(TaxFormCode::cases() as $code)
                    <option value="{{ $code->value }}">{{ $code->label() }}</option>
                @endforeach
            </flux:select>

            <flux:input wire:model="line_item" label="{{ __('Line Item') }}" type="text" placeholder="{{ __('e.g. Repairs and maintenance') }}" />

            <flux:input wire:model="country" label="{{ __('Country') }}" type="text" placeholder="{{ __('USA') }}" />

            <flux:button type="submit" variant="primary">
                {{ __('Create Mapping') }}
            </flux:button>
        </form>
    </section>

    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="lg">{{ __('Tax Mappings') }}</flux:heading>
                <flux:subheading>{{ __('Review and manage existing mappings.') }}</flux:subheading>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <flux:select wire:model.live="filterCategoryId" label="{{ __('Filter by Category') }}" placeholder="{{ __('All categories') }}">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="filterTaxFormCode" label="{{ __('Filter by Form') }}" placeholder="{{ __('All forms') }}">
                    <option value="">{{ __('All forms') }}</option>
                    @foreach(TaxFormCode::cases() as $code)
                        <option value="{{ $code->value }}">{{ $code->label() }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        @if($mappings->isEmpty())
            <div class="mt-6 text-center">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No mappings found.') }}</p>
            </div>
        @else
            <div class="mt-6 space-y-3">
                @foreach($mappings as $mapping)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-medium text-zinc-900 dark:text-zinc-100">{{ $mapping->transactionCategory->name }}</h3>
                                <flux:badge size="sm" color="zinc">
                                    {{ $mapping->tax_form_code->label() }}
                                </flux:badge>
                                <flux:badge size="sm" color="blue">{{ $mapping->country }}</flux:badge>
                            </div>
                            <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $mapping->line_item }}
                            </div>
                        </div>
                        <flux:button
                            wire:click="delete({{ $mapping->id }})"
                            wire:confirm="Are you sure you want to delete this mapping?"
                            size="sm"
                            variant="danger"
                        >
                            {{ __('Delete') }}
                        </flux:button>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
