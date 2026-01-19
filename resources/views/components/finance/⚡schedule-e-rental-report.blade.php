<?php

use App\Finance\Services\UsTaxReportingService;
use App\Models\Asset;
use Livewire\Component;

new class extends Component
{
    public $filingId = '';

    public function mount(): void
    {
        // Auto-select first Schedule E filing if available
        $firstFiling = \App\Models\Filing::query()
            ->where('user_id', auth()->id())
            ->whereHas('filingType', fn ($query) => $query->where('code', 'SCHEDULE-E'))
            ->with('taxYear')
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($firstFiling) {
            $this->filingId = $firstFiling->id;
        }
    }

    public function with(): array
    {
        $filings = \App\Models\Filing::query()
            ->where('user_id', auth()->id())
            ->whereHas('filingType', fn ($query) => $query->where('code', 'SCHEDULE-E'))
            ->with(['taxYear', 'filingType'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filing = null;
        $propertySummaries = [];
        $totals = ['rental_income' => 0, 'total_expenses' => 0, 'net_income' => 0];

        if ($this->filingId) {
            $filing = \App\Models\Filing::query()
                ->where('user_id', auth()->id())
                ->with(['taxYear', 'filingType'])
                ->find($this->filingId);
        }

        if ($filing) {
            $service = app(UsTaxReportingService::class);
            $year = $filing->taxYear->year;

            // Get all US rental properties
            $assets = Asset::query()
                ->whereHas('entity', fn ($query) => $query->where('user_id', auth()->id()))
                ->whereHas('jurisdiction', fn ($query) => $query->where('iso_code', 'USA'))
                ->with(['entity', 'jurisdiction'])
                ->orderBy('name')
                ->get();

            foreach ($assets as $asset) {
                $summary = $service->getScheduleERentalSummary($asset, $year);
                $summary['asset'] = $asset;
                $propertySummaries[] = $summary;
                
                $totals['rental_income'] += $summary['rental_income'];
                $totals['total_expenses'] += $summary['total_expenses'];
                $totals['net_income'] += $summary['net_income'];
            }
        }

        return [
            'filings' => $filings,
            'filing' => $filing,
            'propertySummaries' => $propertySummaries,
            'totals' => $totals,
        ];
    }
};
?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Schedule E - Supplemental Income and Loss') }}</flux:heading>
        <flux:subheading>{{ __('Rental real estate income and expenses for all US properties.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <flux:select wire:model.live="filingId" label="{{ __('Schedule E Filing') }}" placeholder="{{ __('Select a filing') }}">
            <option value="">{{ __('Select a filing') }}</option>
            @foreach($filings as $filingOption)
                <option value="{{ $filingOption->id }}">
                    {{ $filingOption->taxYear->year }} - {{ ucfirst(str_replace('_', ' ', $filingOption->status->value)) }}
                </option>
            @endforeach
        </flux:select>
    </div>

    @if (! $filing)
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            {{ __('No Schedule E filings found. Create a Schedule E filing in your tax year filings to get started.') }}
        </div>
    @else
        <!-- Summary Totals -->
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total Rental Income') }}</div>
                <div class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400">
                    ${{ number_format($totals['rental_income'], 2) }} USD
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('All properties') }}
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total Expenses') }}</div>
                <div class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">
                    ${{ number_format($totals['total_expenses'], 2) }} USD
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Year') }}: {{ $filing->taxYear->year }}
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Net Income') }}</div>
                <div class="mt-2 text-2xl font-bold {{ $totals['net_income'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    ${{ number_format($totals['net_income'], 2) }} USD
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Status') }}: {{ ucfirst(str_replace('_', ' ', $filing->status->value)) }}
                </div>
            </div>
        </div>

        <!-- Individual Properties -->
        @if (count($propertySummaries) > 0)
            <div class="space-y-4">
                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Properties') }}</div>
                
                @foreach($propertySummaries as $propSummary)
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="mb-4">
                            <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $propSummary['asset']->name }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $propSummary['asset']->entity->name }}</div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Income') }}</div>
                                <div class="text-lg font-semibold text-green-600 dark:text-green-400">
                                    ${{ number_format($propSummary['rental_income'], 2) }} USD
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Expenses') }}</div>
                                <div class="text-lg font-semibold text-red-600 dark:text-red-400">
                                    ${{ number_format($propSummary['total_expenses'], 2) }} USD
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Net') }}</div>
                                <div class="text-lg font-semibold {{ $propSummary['net_income'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    ${{ number_format($propSummary['net_income'], 2) }} USD
                                </div>
                            </div>
                        </div>

                        @if (!empty($propSummary['expenses_by_category']))
                            <div class="mt-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                                <div class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Expense Categories') }}</div>
                                <div class="grid gap-2 md:grid-cols-2">
                                    @foreach($propSummary['expenses_by_category'] as $categoryName => $amount)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-zinc-600 dark:text-zinc-400">{{ $categoryName }}</span>
                                            <span class="font-medium text-zinc-900 dark:text-zinc-100">${{ number_format($amount, 2) }} USD</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                {{ __('No US rental properties found for this tax year.') }}
            </div>
        @endif

        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            <strong>{{ __('Note') }}:</strong> {{ __('All amounts are shown in USD. This summary consolidates all rental properties for Schedule E filing.') }}
        </div>
    @endif
</div>
