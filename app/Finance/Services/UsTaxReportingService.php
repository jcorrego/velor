<?php

namespace App\Finance\Services;

use App\Enums\Finance\RelatedPartyType;
use App\Models\Asset;
use App\Models\Entity;
use App\Models\RelatedPartyTransaction;
use App\Models\Transaction;
use App\Models\User;

class UsTaxReportingService
{
    /**
     * Get owner-flow summary for Form 5472 reporting.
     *
     * @return array{contributions: float, draws: float, related_party_totals: float}
     */
    public function getOwnerFlowSummary(User $user, int $taxYear): array
    {
        $entities = Entity::where('user_id', $user->id)->pluck('id');

        $contributions = RelatedPartyTransaction::query()
            ->whereIn('account_id', function ($query) use ($entities) {
                $query->select('id')
                    ->from('accounts')
                    ->whereIn('entity_id', $entities);
            })
            ->where('owner_id', $user->id)
            ->where('type', RelatedPartyType::OwnerContribution)
            ->whereYear('transaction_date', $taxYear)
            ->sum('amount');

        $draws = RelatedPartyTransaction::query()
            ->whereIn('account_id', function ($query) use ($entities) {
                $query->select('id')
                    ->from('accounts')
                    ->whereIn('entity_id', $entities);
            })
            ->where('owner_id', $user->id)
            ->where('type', RelatedPartyType::OwnerDraw)
            ->whereYear('transaction_date', $taxYear)
            ->sum('amount');

        $relatedPartyTotals = RelatedPartyTransaction::query()
            ->whereIn('account_id', function ($query) use ($entities) {
                $query->select('id')
                    ->from('accounts')
                    ->whereIn('entity_id', $entities);
            })
            ->where('owner_id', $user->id)
            ->whereYear('transaction_date', $taxYear)
            ->sum('amount');

        return [
            'contributions' => (float) $contributions,
            'draws' => (float) $draws,
            'related_party_totals' => (float) $relatedPartyTotals,
        ];
    }

    /**
     * Get Schedule E rental summary for a US property.
     *
     * @return array{rental_income: float, expenses_by_category: array<string, float>, total_expenses: float, net_income: float}
     */
    public function getScheduleERentalSummary(Asset $asset, int $taxYear): array
    {
        // Get rental income
        $rentalIncome = (float) Transaction::query()
            ->whereIn('category_id', function ($query) use ($asset) {
                $query->select('id')
                    ->from('transaction_categories')
                    ->where('jurisdiction_id', $asset->entity->jurisdiction_id)
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
            ->sum('converted_amount');

        // Get expenses grouped by category
        $expenses = Transaction::query()
            ->join('transaction_categories', 'transactions.category_id', '=', 'transaction_categories.id')
            ->whereIn('transactions.category_id', function ($query) use ($asset) {
                $query->select('id')
                    ->from('transaction_categories')
                    ->where('jurisdiction_id', $asset->entity->jurisdiction_id)
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
            ->selectRaw('transaction_categories.name as category_name, SUM(transactions.converted_amount) as total')
            ->groupBy('transaction_categories.id', 'transaction_categories.name')
            ->get();

        $expensesByCategory = [];
        $totalExpenses = 0;

        foreach ($expenses as $expense) {
            $expensesByCategory[$expense->category_name] = (float) $expense->total;
            $totalExpenses += (float) $expense->total;
        }

        return [
            'rental_income' => $rentalIncome,
            'expenses_by_category' => $expensesByCategory,
            'total_expenses' => $totalExpenses,
            'net_income' => $rentalIncome - $totalExpenses,
        ];
    }
}
