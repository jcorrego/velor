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
};
?>

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
