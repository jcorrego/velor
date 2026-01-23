<?php

namespace App\Finance\Services;

use App\Enums\Finance\TaxFormCode;
use App\Models\Asset;
use App\Models\CategoryTaxMapping;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\TaxYear;
use App\Models\Transaction;
use App\Models\User;
use App\Models\YearEndValue;

class UsTaxReportingService
{
    public function __construct(
        private FxRateService $fxRateService
    ) {}

    /**
     * Get owner-flow summary for Form 5472 reporting.
     * All amounts are converted to USD.
     *
     * @return array{contributions: float, draws: float, related_party_totals: float}
     */
    public function getOwnerFlowSummary(User $user, int $taxYear): array
    {
        $entities = Entity::where('user_id', $user->id)->pluck('id');
        $usdCurrency = Currency::where('code', 'USD')->firstOrFail();

        $contributions = $this->getForm5472Transactions($entities, $taxYear, 'owner_contribution', $usdCurrency);
        $draws = $this->getForm5472Transactions($entities, $taxYear, 'owner_draw', $usdCurrency);
        $relatedPartyTotals = $this->getForm5472Transactions($entities, $taxYear, null, $usdCurrency);

        return [
            'contributions' => $contributions,
            'draws' => $draws,
            'related_party_totals' => $relatedPartyTotals,
        ];
    }

    /**
     * Get Form 5472 line item summary for a specific line item key.
     * All amounts are converted to USD.
     *
     * @return array{total: float, transaction_count: int, category_count: int}
     */
    public function getForm5472LineItemSummary(User $user, int $taxYear, string $lineItem): array
    {
        $entities = Entity::where('user_id', $user->id)->pluck('id');
        $usdCurrency = Currency::where('code', 'USD')->firstOrFail();

        $transactions = Transaction::query()
            ->join('category_tax_mappings', function ($join) use ($lineItem) {
                $join->on('category_tax_mappings.category_id', '=', 'transactions.category_id')
                    ->where('category_tax_mappings.tax_form_code', TaxFormCode::Form5472->value)
                    ->where('category_tax_mappings.country', 'USA')
                    ->where('category_tax_mappings.line_item', $lineItem);
            })
            ->whereIn('transactions.account_id', function ($subQuery) use ($entities) {
                $subQuery->select('id')
                    ->from('accounts')
                    ->whereIn('entity_id', $entities);
            })
            ->whereYear('transactions.transaction_date', $taxYear)
            ->select(
                'transactions.id',
                'transactions.original_amount',
                'transactions.original_currency_id',
                'transactions.transaction_date',
                'transactions.category_id'
            )
            ->with('originalCurrency')
            ->get();

        $total = $this->convertTransactionsToUSD($transactions, $usdCurrency);

        $categoryCount = CategoryTaxMapping::query()
            ->where('tax_form_code', TaxFormCode::Form5472)
            ->where('country', 'USA')
            ->where('line_item', $lineItem)
            ->distinct('category_id')
            ->count('category_id');

        return [
            'total' => $total,
            'transaction_count' => $transactions->count(),
            'category_count' => $categoryCount,
        ];
    }

    /**
     * Get Schedule E rental summary for a US property.
     * All amounts are converted to USD.
     *
     * @return array{rental_income: float, expenses_by_category: array<string, float>, total_expenses: float, net_income: float}
     */
    public function getScheduleERentalSummary(Asset $asset, int $taxYear): array
    {
        $usdCurrency = Currency::where('code', 'USD')->firstOrFail();
        $scheduleEMappingCategoryIds = CategoryTaxMapping::query()
            ->where('tax_form_code', 'schedule_e')
            ->where('country', 'USA')
            ->pluck('category_id');

        // Get rental income transactions
        $incomeTransactions = Transaction::query()
            ->whereIn('category_id', function ($query) use ($scheduleEMappingCategoryIds) {
                $query->select('id')
                    ->from('transaction_categories')
                    ->where('income_or_expense', 'income')
                    ->whereIn('id', $scheduleEMappingCategoryIds);
            })
            ->whereIn('account_id', function ($query) use ($asset) {
                $query->select('id')
                    ->from('accounts')
                    ->where('entity_id', $asset->entity_id);
            })
            ->whereYear('transaction_date', $taxYear)
            ->where('type', 'income')
            ->with('originalCurrency')
            ->get();

        $rentalIncome = $this->convertTransactionsToUSD($incomeTransactions, $usdCurrency);

        // Get expenses grouped by category
        $expenses = Transaction::query()
            ->join('transaction_categories', 'transactions.category_id', '=', 'transaction_categories.id')
            ->whereIn('transactions.category_id', function ($query) use ($scheduleEMappingCategoryIds) {
                $query->select('id')
                    ->from('transaction_categories')
                    ->where('income_or_expense', 'expense')
                    ->whereIn('id', $scheduleEMappingCategoryIds);
            })
            ->whereIn('transactions.account_id', function ($query) use ($asset) {
                $query->select('id')
                    ->from('accounts')
                    ->where('entity_id', $asset->entity_id);
            })
            ->whereYear('transactions.transaction_date', $taxYear)
            ->where('transactions.type', 'expense')
            ->select(
                'transactions.id',
                'transactions.original_amount',
                'transactions.original_currency_id',
                'transactions.transaction_date',
                'transaction_categories.id as category_id',
                'transaction_categories.name as category_name'
            )
            ->with('originalCurrency')
            ->get();

        $expensesByCategory = [];
        $totalExpenses = 0;

        foreach ($expenses as $expense) {
            $convertedAmount = $this->fxRateService->convert(
                $expense->original_amount,
                $expense->originalCurrency,
                $usdCurrency,
                $expense->transaction_date
            );

            if (! isset($expensesByCategory[$expense->category_name])) {
                $expensesByCategory[$expense->category_name] = 0;
            }
            $expensesByCategory[$expense->category_name] += $convertedAmount;
            $totalExpenses += $convertedAmount;
        }

        return [
            'rental_income' => $rentalIncome,
            'expenses_by_category' => $expensesByCategory,
            'total_expenses' => $totalExpenses,
            'net_income' => $rentalIncome + $totalExpenses,
        ];
    }

    /**
     * Get Form 1040-NR summary totals grouped by line item.
     * All amounts are converted to USD.
     *
     * @return array{line_items: array<string, float>, total: float}
     */
    public function getForm1040NrSummary(User $user, int $taxYear): array
    {
        return $this->getMappedFormSummary($user, $taxYear, 'form_1040_nr');
    }

    /**
     * Get Form 1120 summary totals grouped by line item.
     * All amounts are converted to USD.
     *
     * @return array{line_items: array<string, float>, total: float}
     */
    public function getForm1120Summary(User $user, int $taxYear): array
    {
        return $this->getMappedFormSummary($user, $taxYear, 'form_1120');
    }

    /**
     * Get year-end totals for Form 5472 by entity in the filing jurisdiction.
     *
     * @return array{total: float, entities: array<int, array{entity_id: int, entity_name: string, accounts_total: float, assets_total: float, total: float}>}
     */
    public function getForm5472YearEndTotals(User $user, TaxYear $taxYear): array
    {
        $entities = Entity::query()
            ->where('user_id', $user->id)
            ->where('jurisdiction_id', $taxYear->jurisdiction_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($entities->isEmpty()) {
            return [
                'total' => 0.0,
                'entities' => [],
            ];
        }

        $totals = YearEndValue::query()
            ->whereIn('entity_id', $entities->pluck('id'))
            ->where('tax_year_id', $taxYear->id)
            ->selectRaw('entity_id, SUM(CASE WHEN account_id IS NOT NULL THEN amount ELSE 0 END) as accounts_total')
            ->selectRaw('SUM(CASE WHEN asset_id IS NOT NULL THEN amount ELSE 0 END) as assets_total')
            ->groupBy('entity_id')
            ->get()
            ->keyBy('entity_id');

        $entityTotals = $entities->map(function (Entity $entity) use ($totals): array {
            $entityTotal = $totals->get($entity->id);
            $accountsTotal = (float) ($entityTotal?->accounts_total ?? 0);
            $assetsTotal = (float) ($entityTotal?->assets_total ?? 0);

            return [
                'entity_id' => $entity->id,
                'entity_name' => $entity->name,
                'accounts_total' => $accountsTotal,
                'assets_total' => $assetsTotal,
                'total' => $accountsTotal + $assetsTotal,
            ];
        })->values()->all();

        return [
            'total' => array_sum(array_column($entityTotals, 'total')),
            'entities' => $entityTotals,
        ];
    }

    /**
     * Get Form 5472 transactions by querying via category tax mappings.
     *
     * @param  \Illuminate\Support\Collection  $entities
     * @param  string|null  $lineItem  Specific line_item to filter (e.g., 'owner_contribution'), or null for all Form 5472 transactions
     */
    private function getForm5472Transactions($entities, int $taxYear, ?string $lineItem, Currency $usdCurrency): float
    {
        $query = Transaction::query()
            ->whereIn('account_id', function ($subQuery) use ($entities) {
                $subQuery->select('id')
                    ->from('accounts')
                    ->whereIn('entity_id', $entities);
            })
            ->whereExists(function ($subQuery) use ($lineItem) {
                $subQuery->select('id')
                    ->from('category_tax_mappings')
                    ->whereColumn('category_tax_mappings.category_id', 'transactions.category_id')
                    ->where('category_tax_mappings.tax_form_code', 'form_5472');

                if ($lineItem) {
                    $subQuery->where('category_tax_mappings.line_item', $lineItem);
                }
            })
            ->whereYear('transaction_date', $taxYear)
            ->with('originalCurrency');

        $transactions = $query->get();

        return $this->convertTransactionsToUSD($transactions, $usdCurrency);
    }

    /**
     * Get mapped summary totals for a given US tax form grouped by line item.
     *
     * @return array{line_items: array<string, float>, total: float}
     */
    private function getMappedFormSummary(User $user, int $taxYear, string $taxFormCode): array
    {
        $entities = Entity::where('user_id', $user->id)->pluck('id');
        $usdCurrency = Currency::where('code', 'USD')->firstOrFail();

        $transactions = Transaction::query()
            ->join('category_tax_mappings', function ($join) use ($taxFormCode) {
                $join->on('category_tax_mappings.category_id', '=', 'transactions.category_id')
                    ->where('category_tax_mappings.tax_form_code', $taxFormCode)
                    ->where('category_tax_mappings.country', 'USA');
            })
            ->whereIn('transactions.account_id', function ($subQuery) use ($entities) {
                $subQuery->select('id')
                    ->from('accounts')
                    ->whereIn('entity_id', $entities);
            })
            ->whereYear('transactions.transaction_date', $taxYear)
            ->select(
                'transactions.id',
                'transactions.original_amount',
                'transactions.original_currency_id',
                'transactions.transaction_date',
                'category_tax_mappings.line_item'
            )
            ->with('originalCurrency')
            ->get();

        $lineItems = [];
        $total = 0;

        foreach ($transactions as $transaction) {
            $convertedAmount = $this->fxRateService->convert(
                $transaction->original_amount,
                $transaction->originalCurrency,
                $usdCurrency,
                $transaction->transaction_date
            );

            if (! isset($lineItems[$transaction->line_item])) {
                $lineItems[$transaction->line_item] = 0;
            }

            $lineItems[$transaction->line_item] += $convertedAmount;
            $total += $convertedAmount;
        }

        return [
            'line_items' => $lineItems,
            'total' => $total,
        ];
    }

    /**
     * Convert a collection of Transactions to USD.
     * Uses original_amount (transaction's native currency) for US reporting.
     */
    private function convertTransactionsToUSD($transactions, Currency $usdCurrency): float
    {
        $total = 0;

        foreach ($transactions as $transaction) {
            $convertedAmount = $this->fxRateService->convert(
                $transaction->original_amount,
                $transaction->originalCurrency,
                $usdCurrency,
                $transaction->transaction_date
            );
            $total += $convertedAmount;
        }

        return $total;
    }
}
