<?php

namespace App\Finance\Services;

use App\Models\Asset;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\User;

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
     * Get Schedule E rental summary for a US property.
     * All amounts are converted to USD.
     *
     * @return array{rental_income: float, expenses_by_category: array<string, float>, total_expenses: float, net_income: float}
     */
    public function getScheduleERentalSummary(Asset $asset, int $taxYear): array
    {
        $usdCurrency = Currency::where('code', 'USD')->firstOrFail();

        // Get rental income transactions
        $incomeTransactions = Transaction::query()
            ->whereIn('category_id', function ($query) {
                $query->select('id')
                    ->from('transaction_categories')
                    ->where('income_or_expense', 'income')
                    ->where('name', 'like', '%rental%');
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
            ->whereIn('transactions.category_id', function ($query) {
                $query->select('id')
                    ->from('transaction_categories')
                    ->where('income_or_expense', 'expense')
                    ->where('name', 'like', '%rental%');
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
            'net_income' => $rentalIncome - $totalExpenses,
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
            ->join('transaction_categories', 'transactions.category_id', '=', 'transaction_categories.id')
            ->join('category_tax_mappings', 'transaction_categories.id', '=', 'category_tax_mappings.category_id')
            ->whereIn('transactions.account_id', function ($subQuery) use ($entities) {
                $subQuery->select('id')
                    ->from('accounts')
                    ->whereIn('entity_id', $entities);
            })
            ->where('category_tax_mappings.tax_form_code', 'form_5472')
            ->whereYear('transactions.transaction_date', $taxYear)
            ->with('originalCurrency');

        if ($lineItem) {
            $query->where('category_tax_mappings.line_item', $lineItem);
        }

        $transactions = $query->get();

        return $this->convertTransactionsToUSD($transactions, $usdCurrency);
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
