<?php

namespace App\Finance\Services;

use App\Enums\Finance\TaxFormCode;
use App\Enums\Finance\TransactionType;
use App\Models\CategoryTaxMapping;
use App\Models\Currency;
use App\Models\Entity;
use App\Models\Transaction;
use App\Models\User;

class ColombiaTaxReportingService
{
    /**
     * Get Colombia income and expense summary in COP.
     *
     * @return array{income_total: float, expense_total: float, net_income: float, income_by_category: array<string, float>, expense_by_category: array<string, float>}
     */
    public function getIncomeExpenseSummary(User $user, int $taxYear): array
    {
        $entityIds = Entity::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        $copCurrency = Currency::query()
            ->where('code', 'COP')
            ->firstOrFail();

        $mappedCategoryIds = CategoryTaxMapping::query()
            ->where('tax_form_code', TaxFormCode::ColombianDeclaration)
            ->where('country', 'Colombia')
            ->pluck('category_id');

        $incomeTransactions = $this->getTransactionsByType(
            $entityIds,
            $mappedCategoryIds,
            $taxYear,
            TransactionType::Income,
            $copCurrency->id
        );

        $expenseTransactions = $this->getTransactionsByType(
            $entityIds,
            $mappedCategoryIds,
            $taxYear,
            TransactionType::Expense,
            $copCurrency->id
        );

        $incomeByCategory = $this->sumByCategory($incomeTransactions);
        $expenseByCategory = $this->sumByCategory($expenseTransactions);

        $incomeTotal = array_sum($incomeByCategory);
        $expenseTotal = array_sum($expenseByCategory);

        return [
            'income_total' => $incomeTotal,
            'expense_total' => $expenseTotal,
            'net_income' => $incomeTotal + $expenseTotal,
            'income_by_category' => $incomeByCategory,
            'expense_by_category' => $expenseByCategory,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection  $entityIds
     * @param  \Illuminate\Support\Collection  $mappedCategoryIds
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    private function getTransactionsByType($entityIds, $mappedCategoryIds, int $taxYear, TransactionType $type, int $currencyId)
    {
        return Transaction::query()
            ->join('transaction_categories', 'transactions.category_id', '=', 'transaction_categories.id')
            ->whereIn('transactions.account_id', function ($query) use ($entityIds) {
                $query->select('id')
                    ->from('accounts')
                    ->whereIn('entity_id', $entityIds);
            })
            ->whereIn('transactions.category_id', $mappedCategoryIds)
            ->whereYear('transactions.transaction_date', $taxYear)
            ->where('transactions.type', $type->value)
            ->where('transaction_categories.income_or_expense', $type->value)
            ->where('transactions.original_currency_id', $currencyId)
            ->select(
                'transactions.id',
                'transactions.original_amount',
                'transaction_categories.name as category_name'
            )
            ->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>  $transactions
     * @return array<string, float>
     */
    private function sumByCategory($transactions): array
    {
        $totals = [];

        foreach ($transactions as $transaction) {
            $categoryName = $transaction->category_name;
            $totals[$categoryName] = ($totals[$categoryName] ?? 0) + $transaction->original_amount;
        }

        return $totals;
    }
}
