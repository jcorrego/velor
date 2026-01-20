<?php

use App\Finance\Services\ColombiaTaxReportingService;
use Livewire\Component;

new class extends Component
{
    public $filingId = '';

    public function mount(): void
    {
        $firstFiling = \App\Models\Filing::query()
            ->where('user_id', auth()->id())
            ->whereHas('filingType', fn ($query) => $query->where('code', 'RENTA'))
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
            ->whereHas('filingType', fn ($query) => $query->where('code', 'RENTA'))
            ->with(['taxYear', 'filingType'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filing = null;
        $summary = [
            'income_total' => 0,
            'expense_total' => 0,
            'net_income' => 0,
            'income_by_category' => [],
            'expense_by_category' => [],
        ];

        if ($this->filingId) {
            $filing = \App\Models\Filing::query()
                ->where('user_id', auth()->id())
                ->with(['taxYear', 'filingType'])
                ->find($this->filingId);
        }

        if ($filing) {
            $service = app(ColombiaTaxReportingService::class);
            $summary = $service->getIncomeExpenseSummary(auth()->user(), $filing->taxYear->year);
        }

        return [
            'filings' => $filings,
            'filing' => $filing,
            'summary' => $summary,
        ];
    }
};
?>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('Colombia Income Tax Summary') }}</flux:heading>
        <flux:subheading>{{ __('COP-native income and expense totals for Colombia reporting.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <flux:select wire:model.live="filingId" label="{{ __('Colombia Filing') }}" placeholder="{{ __('Select a filing') }}">
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
            {{ __('No Colombia filings found. Create a Renta filing in your tax year filings to get started.') }}
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total Income') }}</div>
                <div class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400">
                    {{ number_format($summary['income_total'], 2) }} COP
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Year') }}: {{ $filing->taxYear->year }}
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total Expenses') }}</div>
                <div class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">
                    {{ number_format($summary['expense_total'], 2) }} COP
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Status') }}: {{ ucfirst(str_replace('_', ' ', $filing->status->value)) }}
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Net Income') }}</div>
                <div class="mt-2 text-2xl font-bold {{ $summary['net_income'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ number_format($summary['net_income'], 2) }} COP
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('All mapped categories') }}
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Income Categories') }}</div>
                @if (count($summary['income_by_category']) > 0)
                    <div class="mt-3 grid gap-2">
                        @foreach($summary['income_by_category'] as $categoryName => $amount)
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-400">{{ $categoryName }}</span>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($amount, 2) }} COP</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('No income categories mapped for this filing.') }}
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Expense Categories') }}</div>
                @if (count($summary['expense_by_category']) > 0)
                    <div class="mt-3 grid gap-2">
                        @foreach($summary['expense_by_category'] as $categoryName => $amount)
                            <div class="flex justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-400">{{ $categoryName }}</span>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($amount, 2) }} COP</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('No expense categories mapped for this filing.') }}
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            <strong>{{ __('Note') }}:</strong> {{ __('All amounts are shown in COP for Colombia reporting.') }}
        </div>
    @endif
</div>
