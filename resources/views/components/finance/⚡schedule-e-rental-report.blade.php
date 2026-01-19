<?php

use App\Finance\Services\UsTaxReportingService;
use App\Models\Asset;
use Livewire\Component;

new class extends Component
{
    public $assetId = '';
    public $year;

    public function mount(): void
    {
        $this->year = now()->year;
    }

    public function with(): array
    {
        $assets = Asset::query()
            ->whereHas('entity', fn ($query) => $query->where('user_id', auth()->id()))
            ->whereHas('jurisdiction', fn ($query) => $query->where('iso_code', 'US'))
            ->orderBy('name')
            ->get();

        $asset = null;
        $summary = null;

        if ($this->assetId) {
            $asset = Asset::query()
                ->whereHas('entity', fn ($query) => $query->where('user_id', auth()->id()))
                ->whereHas('jurisdiction', fn ($query) => $query->where('iso_code', 'US'))
                ->with(['entity', 'jurisdiction'])
                ->find($this->assetId);
        }

        if ($asset) {
            $service = app(UsTaxReportingService::class);
            $year = (int) $this->year;

            $summary = $service->getScheduleERentalSummary($asset, $year);
        }

        return [
            'assets' => $assets,
            'asset' => $asset,
            'summary' => $summary,
        ];
    }
};
?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Schedule E Rental Summary') }}</flux:heading>
        <flux:subheading>{{ __('Rental income and expenses by category for US properties.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <flux:select wire:model.live="assetId" label="{{ __('US Rental Property') }}" placeholder="{{ __('Select a property') }}">
            <option value="">{{ __('Select a property') }}</option>
            @foreach($assets as $assetOption)
                <option value="{{ $assetOption->id }}">{{ $assetOption->name }}</option>
            @endforeach
        </flux:select>

        <flux:input
            wire:model.live="year"
            label="{{ __('Tax Year') }}"
            type="number"
            min="1900"
            max="{{ now()->year + 1 }}"
        />
    </div>

    @if (! $asset)
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            {{ __('Select a US rental property to view its Schedule E summary.') }}
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Rental Income') }}</div>
                <div class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400">
                    ${{ number_format($summary['rental_income'] ?? 0, 2) }}
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Property') }}: {{ $asset->name }}
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total Expenses') }}</div>
                <div class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">
                    ${{ number_format($summary['total_expenses'] ?? 0, 2) }}
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Year') }}: {{ $year }}
                </div>
            </div>
        </div>

        @if (!empty($summary['expenses_by_category']))
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">{{ __('Expenses by Category') }}</div>
                <div class="space-y-2">
                    @foreach($summary['expenses_by_category'] as $categoryName => $amount)
                        <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $categoryName }}</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">${{ number_format($amount, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Net Rental Income') }}</div>
            <div class="mt-2 text-3xl font-bold {{ ($summary['net_income'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                ${{ number_format($summary['net_income'] ?? 0, 2) }}
            </div>
            <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Income minus expenses') }}
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            <strong>{{ __('Note') }}:</strong> {{ __('All amounts are shown in USD. This summary follows Schedule E format for US tax reporting.') }}
        </div>
    @endif
</div>
