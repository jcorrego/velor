<?php

namespace App\Finance\Services;

use App\Enums\Finance\TaxFormCode;
use App\Enums\Finance\TransactionType;
use App\Models\Account;
use App\Models\Asset;
use App\Models\CategoryTaxMapping;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\Jurisdiction;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class SpainTaxReportingService
{
    public function __construct(private FxRateService $fxRateService) {}

    /**
     * @return array{income_total: float, expense_total: float, net_income: float, income_by_category: array<string, float>, expense_by_category: array<string, float>, income_by_source: array<string, float>, expense_by_source: array<string, float>}
     */
    public function getIrpfSummary(User $user, int $taxYear): array
    {
        $entityIds = Entity::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        $eurCurrency = Currency::query()
            ->where('code', 'EUR')
            ->firstOrFail();

        $irpfCodes = collect([
            TaxFormCode::IRPF,
            TaxFormCode::IRPFCapitalGains,
            TaxFormCode::IRPFForeignIncome,
        ])->map(fn (TaxFormCode $code) => $code->value);

        $mappedCategoryIds = CategoryTaxMapping::query()
            ->whereIn('tax_form_code', $irpfCodes)
            ->where('country', 'Spain')
            ->pluck('category_id');

        $incomeTransactions = $this->getIrpfTransactions($entityIds, $mappedCategoryIds, $irpfCodes, $taxYear, TransactionType::Income);
        $expenseTransactions = $this->getIrpfTransactions($entityIds, $mappedCategoryIds, $irpfCodes, $taxYear, TransactionType::Expense);

        $incomeByCategory = $this->sumByCategory($incomeTransactions, $eurCurrency);
        $expenseByCategory = $this->sumByCategory($expenseTransactions, $eurCurrency);

        $incomeBySource = $this->sumBySource($incomeTransactions, $eurCurrency);
        $expenseBySource = $this->sumBySource($expenseTransactions, $eurCurrency);

        $incomeTotal = array_sum($incomeByCategory);
        $expenseTotal = array_sum($expenseByCategory);

        return [
            'income_total' => $incomeTotal,
            'expense_total' => $expenseTotal,
            'net_income' => $incomeTotal + $expenseTotal,
            'income_by_category' => $incomeByCategory,
            'expense_by_category' => $expenseByCategory,
            'income_by_source' => $incomeBySource,
            'expense_by_source' => $expenseBySource,
        ];
    }

    /**
     * @return array{threshold: float, total_assets: float, categories: array<string, array{total: float, threshold: float, status: string}>}
     */
    public function getModelo720Summary(User $user, int $taxYear): array
    {
        $entityIds = Entity::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        $eurCurrency = Currency::query()
            ->where('code', 'EUR')
            ->firstOrFail();

        $spain = Jurisdiction::query()
            ->where('iso_code', 'ESP')
            ->firstOrFail();

        $foreignAccountIds = Account::query()
            ->whereIn('entity_id', $entityIds)
            ->whereHas('entity', fn ($query) => $query->where('jurisdiction_id', '!=', $spain->id))
            ->pluck('id');

        $accountTransactions = Transaction::query()
            ->whereIn('account_id', $foreignAccountIds)
            ->whereYear('transaction_date', $taxYear)
            ->whereIn('type', [
                TransactionType::Income->value,
                TransactionType::Expense->value,
                TransactionType::Fee->value,
            ])
            ->with('originalCurrency')
            ->get();

        $bankAccountsTotal = $this->sumTransactionsToCurrency($accountTransactions, $eurCurrency);

        $yearEnd = Carbon::create($taxYear, 12, 31);
        $foreignAssets = Asset::query()
            ->whereIn('entity_id', $entityIds)
            ->where('jurisdiction_id', '!=', $spain->id)
            ->with(['acquisitionCurrency', 'valuations'])
            ->get();

        $realEstateTotal = 0.0;
        foreach ($foreignAssets as $asset) {
            $valuation = $asset->valuations
                ->where('valuation_date', '<=', $yearEnd)
                ->sortByDesc('valuation_date')
                ->first();

            $amount = $valuation?->amount ?? $asset->acquisition_cost;
            $date = $valuation?->valuation_date ?? $asset->acquisition_date;

            if (! $amount || ! $date || ! $asset->acquisitionCurrency) {
                continue;
            }

            $realEstateTotal += $this->fxRateService->convert(
                (float) $amount,
                $asset->acquisitionCurrency,
                $eurCurrency,
                $date
            );
        }

        $threshold = (float) config('finance.modelo_720_threshold', 50000.00);

        $categories = [
            'Bank Accounts' => [
                'total' => $bankAccountsTotal,
                'threshold' => $threshold,
                'status' => $bankAccountsTotal >= $threshold ? 'above' : 'below',
            ],
            'Real Estate' => [
                'total' => $realEstateTotal,
                'threshold' => $threshold,
                'status' => $realEstateTotal >= $threshold ? 'above' : 'below',
            ],
        ];

        return [
            'threshold' => $threshold,
            'total_assets' => $bankAccountsTotal + $realEstateTotal,
            'categories' => $categories,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection  $entityIds
     * @param  \Illuminate\Support\Collection  $mappedCategoryIds
     * @param  \Illuminate\Support\Collection  $irpfCodes
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    private function getIrpfTransactions($entityIds, $mappedCategoryIds, $irpfCodes, int $taxYear, TransactionType $type)
    {
        $mappingQuery = CategoryTaxMapping::query()
            ->selectRaw('category_id, MIN(line_item) as line_item, MIN(tax_form_code) as tax_form_code')
            ->whereIn('tax_form_code', $irpfCodes)
            ->where('country', 'Spain')
            ->groupBy('category_id');

        return Transaction::query()
            ->join('transaction_categories', 'transactions.category_id', '=', 'transaction_categories.id')
            ->joinSub($mappingQuery, 'category_tax_mappings', function ($join) {
                $join->on('transactions.category_id', '=', 'category_tax_mappings.category_id');
            })
            ->whereIn('transactions.account_id', function ($query) use ($entityIds) {
                $query->select('id')
                    ->from('accounts')
                    ->whereIn('entity_id', $entityIds);
            })
            ->whereIn('transactions.category_id', $mappedCategoryIds)
            ->whereYear('transactions.transaction_date', $taxYear)
            ->where('transactions.type', $type->value)
            ->where('transaction_categories.income_or_expense', $type->value)
            ->select(
                'transactions.id',
                'transactions.original_amount',
                'transactions.original_currency_id',
                'transactions.transaction_date',
                'transaction_categories.name as category_name',
                'category_tax_mappings.line_item',
                'category_tax_mappings.tax_form_code'
            )
            ->with('originalCurrency')
            ->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>  $transactions
     * @return array<string, float>
     */
    private function sumByCategory($transactions, Currency $targetCurrency): array
    {
        $totals = [];

        foreach ($transactions as $transaction) {
            $categoryName = $transaction->category_name;
            $amount = $this->fxRateService->convert(
                (float) $transaction->original_amount,
                $transaction->originalCurrency,
                $targetCurrency,
                $transaction->transaction_date
            );

            $totals[$categoryName] = ($totals[$categoryName] ?? 0) + $amount;
        }

        return $totals;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>  $transactions
     * @return array<string, float>
     */
    private function sumBySource($transactions, Currency $targetCurrency): array
    {
        $totals = [];

        foreach ($transactions as $transaction) {
            $sourceLabel = $this->formatSourceLabel(
                (string) $transaction->tax_form_code,
                (string) $transaction->line_item
            );

            $amount = $this->fxRateService->convert(
                (float) $transaction->original_amount,
                $transaction->originalCurrency,
                $targetCurrency,
                $transaction->transaction_date
            );

            $totals[$sourceLabel] = ($totals[$sourceLabel] ?? 0) + $amount;
        }

        return $totals;
    }

    private function formatSourceLabel(string $taxFormCode, string $lineItem): string
    {
        $form = TaxFormCode::tryFrom($taxFormCode);
        if (! $form) {
            return $lineItem;
        }

        return trim(sprintf('%s: %s', $form->label(), $lineItem));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>  $transactions
     */
    private function sumTransactionsToCurrency($transactions, Currency $targetCurrency): float
    {
        $total = 0.0;

        foreach ($transactions as $transaction) {
            $total += $this->fxRateService->convert(
                (float) $transaction->original_amount,
                $transaction->originalCurrency,
                $targetCurrency,
                $transaction->transaction_date
            );
        }

        return $total;
    }
}
