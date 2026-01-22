<?php

use App\Enums\Finance\TransactionType;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        $year = Carbon::now()->year - 1;

        $baseQuery = Transaction::query()
            ->whereYear('transaction_date', $year)
            ->whereNotNull('category_id')
            ->whereHas('account.entity', fn (Builder $query) => $query->where('user_id', auth()->id()));

        return [
            'year' => $year,
            'incomeTotals' => $this->categoryTotalsForType(clone $baseQuery, TransactionType::Income),
            'expenseTotals' => $this->categoryTotalsForType(clone $baseQuery, TransactionType::Expense),
            'categoryCountChart' => $this->categoryCountChart(clone $baseQuery),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function categoryTotalsForType(Builder $query, TransactionType $type)
    {
        return $query
            ->where('type', $type->value)
            ->join('transaction_categories', 'transactions.category_id', '=', 'transaction_categories.id')
            ->join('currencies', 'transactions.converted_currency_id', '=', 'currencies.id')
            ->select(
                'transaction_categories.name as category',
                'currencies.symbol as currency_symbol',
                DB::raw('SUM(transactions.converted_amount) as total')
            )
            ->groupBy('transaction_categories.name', 'currencies.symbol')
            ->orderByDesc(DB::raw('ABS(total)'))
            ->limit(6)
            ->get();
    }

    /**
     * @return array<int, array{label: string, count: int, percent: float}>
     */
    private function categoryCountChart(Builder $query): array
    {
        $totals = $query
            ->join('transaction_categories', 'transactions.category_id', '=', 'transaction_categories.id')
            ->select('transaction_categories.name as category')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('transaction_categories.name')
            ->orderByDesc('count')
            ->get();

        if ($totals->isEmpty()) {
            return [];
        }

        $top = $totals->take(10)->values();
        $otherCount = (int) $totals->slice(10)->sum('count');

        $items = $top->map(function (object $item): array {
            return [
                'label' => (string) $item->category,
                'count' => (int) $item->count,
            ];
        })->values();

        if ($otherCount > 0) {
            $items->push([
                'label' => (string) __('Other'),
                'count' => $otherCount,
            ]);
        }

        $totalCount = (int) $items->sum('count');

        return $items
            ->map(function (array $item) use ($totalCount): array {
                $percent = $totalCount > 0
                    ? round(($item['count'] / $totalCount) * 100, 2)
                    : 0.0;

                return [
                    'label' => $item['label'],
                    'count' => $item['count'],
                    'percent' => $percent,
                ];
            })
            ->all();
    }
};
?>

<div class="space-y-6">
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg">{{ __('Category totals') }}</flux:heading>
                <flux:subheading>{{ __('Income categories for :year.', ['year' => $year]) }}</flux:subheading>
            </div>

            @if ($incomeTotals->isEmpty())
                <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                    {{ __('No income totals yet.') }}
                </div>
            @else
                <div class="mt-5 space-y-3">
                    @foreach ($incomeTotals as $item)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                            <span>{{ $item->category }}</span>
                            <span class="font-medium text-zinc-900 dark:text-white">
                                {{ $item->currency_symbol }}{{ number_format((float) $item->total, 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg">{{ __('Category totals') }}</flux:heading>
                <flux:subheading>{{ __('Expense categories for :year.', ['year' => $year]) }}</flux:subheading>
            </div>

            @if ($expenseTotals->isEmpty())
                <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                    {{ __('No expense totals yet.') }}
                </div>
            @else
                <div class="mt-5 space-y-3">
                    @foreach ($expenseTotals as $item)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                            <span>{{ $item->category }}</span>
                            <span class="font-medium text-zinc-900 dark:text-white">
                                {{ $item->currency_symbol }}{{ number_format(abs((float) $item->total), 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div>
            <flux:heading size="lg">{{ __('Transaction counts by category') }}</flux:heading>
            <flux:subheading>{{ __('Top categories for :year, grouped after top 10.', ['year' => $year]) }}</flux:subheading>
        </div>

        @if (empty($categoryCountChart))
            <div class="mt-6 rounded-lg border border-dashed border-zinc-200 px-4 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-300">
                {{ __('No transactions yet.') }}
            </div>
        @else
            @php
                $pieColors = [
                    'oklch(0.74 0.17 266)',
                    'oklch(0.75 0.15 196)',
                    'oklch(0.78 0.16 145)',
                    'oklch(0.78 0.15 85)',
                    'oklch(0.73 0.18 35)',
                    'oklch(0.72 0.14 25)',
                    'oklch(0.7 0.12 310)',
                    'oklch(0.68 0.12 280)',
                    'oklch(0.66 0.12 240)',
                    'oklch(0.64 0.12 200)',
                    'oklch(0.6 0.1 240)',
                ];
                $pieStops = [];
                $offset = 0.0;

                foreach ($categoryCountChart as $index => $item) {
                    $percent = (float) $item['percent'];
                    $color = $pieColors[$index % count($pieColors)];
                    $start = $offset;
                    $end = $offset + $percent;
                    $pieStops[] = $color.' '.$start.'% '.$end.'%';
                    $offset = $end;
                }

                $pieGradient = 'conic-gradient('.implode(', ', $pieStops).')';
            @endphp

            <div class="mt-6 grid gap-6 lg:grid-cols-[240px,1fr]">
                <div class="flex items-center justify-center">
                    <div class="h-48 w-48 rounded-full" style="background: {{ $pieGradient }}"></div>
                </div>
                <div class="space-y-3">
                    @foreach ($categoryCountChart as $index => $item)
                        <div class="flex items-center justify-between gap-4 text-sm text-zinc-700 dark:text-zinc-200">
                            <div class="flex items-center gap-3">
                                <span class="h-3 w-3 rounded-full" style="background: {{ $pieColors[$index % count($pieColors)] }}"></span>
                                <span class="truncate">{{ $item['label'] }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                                <span>{{ $item['count'] }}</span>
                                <span>{{ number_format($item['percent'], 1) }}%</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</div>
