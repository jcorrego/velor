<?php

use App\Finance\Services\RentalPropertyService;
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
            ->orderBy('name')
            ->get();

        $asset = null;
        $report = null;

        if ($this->assetId) {
            $asset = Asset::query()
                ->whereHas('entity', fn ($query) => $query->where('user_id', auth()->id()))
                ->with(['entity', 'jurisdiction'])
                ->find($this->assetId);
        }

        if ($asset) {
            $service = app(RentalPropertyService::class);
            $year = (int) $this->year;

            $report = [
                'income' => $service->getAnnualRentalIncome($asset, $year),
                'expenses' => $service->getAnnualRentalExpenses($asset, $year),
                'depreciation' => $service->getAnnualDepreciation($asset),
                'net' => $service->calculateNetRentalIncome($asset, $year),
            ];
        }

        return [
            'assets' => $assets,
            'asset' => $asset,
            'report' => $report,
        ];
    }
};
?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Rental Property Report') }}</flux:heading>
        <flux:subheading>{{ __('Schedule E summary for rental income, expenses, and depreciation.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <flux:select wire:model.live="assetId" label="{{ __('Rental Property') }}" placeholder="{{ __('Select an asset') }}">
            <option value="">{{ __('Select an asset') }}</option>
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
            {{ __('Select a rental property to view its report.') }}
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Rental Income') }}</div>
                <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ number_format($report['income'] ?? 0, 2) }}
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Asset') }}: {{ $asset->name }}
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Rental Expenses') }}</div>
                <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ number_format($report['expenses'] ?? 0, 2) }}
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Jurisdiction') }}: {{ $asset->jurisdiction->name }}
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Depreciation') }}</div>
                <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ number_format($report['depreciation'] ?? 0, 2) }}
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Method') }}: {{ $asset->depreciation_method ?? __('Not set') }}
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Net Income') }}</div>
                <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ number_format($report['net'] ?? 0, 2) }}
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Tax Year') }}: {{ $year }}
                </div>
            </div>
        </div>
    @endif
</div>
