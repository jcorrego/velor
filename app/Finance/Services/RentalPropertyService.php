<?php

namespace App\Finance\Services;

use App\Models\Asset;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class RentalPropertyService
{
    /**
     * Calculate annual rental income for an asset in a given year.
     */
    public function getAnnualRentalIncome(Asset $asset, int $year): float
    {
        return (float) Transaction::query()
            ->whereIn('category_id', $asset->getRentalIncomeCategories()->pluck('id'))
            ->whereYear('transaction_date', $year)
            ->where('type', 'income')
            ->sum('converted_amount');
    }

    /**
     * Calculate annual rental expenses for an asset in a given year.
     */
    public function getAnnualRentalExpenses(Asset $asset, int $year): float
    {
        return (float) Transaction::query()
            ->whereIn('category_id', $asset->getRentalExpenseCategories()->pluck('id'))
            ->whereYear('transaction_date', $year)
            ->where('type', 'expense')
            ->sum('converted_amount');
    }

    /**
     * Calculate annual depreciation allowance.
     */
    public function getAnnualDepreciation(Asset $asset): float
    {
        return (float) $asset->annual_depreciation_amount;
    }

    /**
     * Calculate net rental income (income - expenses - depreciation).
     */
    public function calculateNetRentalIncome(Asset $asset, int $year): float
    {
        $income = $this->getAnnualRentalIncome($asset, $year);
        $expenses = $this->getAnnualRentalExpenses($asset, $year);
        $depreciation = $this->getAnnualDepreciation($asset);

        return $income - $expenses - $depreciation;
    }

    /**
     * Calculate accumulated depreciation from acquisition to a given date.
     */
    public function calculateAccumulatedDepreciation(Asset $asset, Carbon $asOfDate): float
    {
        if (! $asset->useful_life_years || ! $asset->annual_depreciation_amount) {
            return 0;
        }

        $startDate = Carbon::parse($asset->acquisition_date);
        $years = (int) $startDate->diffInYears($asOfDate);

        // Add partial year if we haven't reached a full anniversary
        if ($asOfDate->month > $startDate->month ||
            ($asOfDate->month === $startDate->month && $asOfDate->day >= $startDate->day)) {
            $years += 1;
        }

        return round($asset->annual_depreciation_amount * $years, 2);
    }

    /**
     * Calculate depreciation percentage remaining.
     */
    public function getDepreciationPercentageRemaining(Asset $asset): float
    {
        if (! $asset->useful_life_years) {
            return 100;
        }

        $yearsUsed = (int) Carbon::parse($asset->acquisition_date)->diffInYears(now());
        $percentageUsed = ($yearsUsed / $asset->useful_life_years) * 100;

        return max(0, 100 - $percentageUsed);
    }

    /**
     * Get all rental income transactions for an asset in a given year.
     */
    public function getRentalIncomeTransactions(Asset $asset, int $year): Collection
    {
        return Transaction::query()
            ->whereIn('category_id', $asset->getRentalIncomeCategories()->pluck('id'))
            ->whereYear('transaction_date', $year)
            ->where('type', 'income')
            ->orderBy('transaction_date')
            ->get();
    }

    /**
     * Get all rental expense transactions for an asset in a given year.
     */
    public function getRentalExpenseTransactions(Asset $asset, int $year): Collection
    {
        return Transaction::query()
            ->whereIn('category_id', $asset->getRentalExpenseCategories()->pluck('id'))
            ->whereYear('transaction_date', $year)
            ->where('type', 'expense')
            ->orderBy('transaction_date')
            ->get();
    }
}
