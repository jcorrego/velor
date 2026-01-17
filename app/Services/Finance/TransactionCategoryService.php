<?php

namespace App\Services\Finance;

use App\Enums\Finance\TaxFormCode;
use App\Models\Filing;
use App\Models\Transaction;
use App\Models\TransactionCategory;

class TransactionCategoryService
{
    /**
     * Aggregate transaction totals by category for a tax year and jurisdiction.
     *
     * @return array<int, array<string, mixed>>
     */
    public function aggregateByCategory(int $taxYear, int $jurisdictionId): array
    {
        $categories = TransactionCategory::query()
            ->where('jurisdiction_id', $jurisdictionId)
            ->withCount('taxMappings')
            ->get();

        if ($categories->isEmpty()) {
            return [];
        }

        $totals = Transaction::query()
            ->selectRaw('category_id, SUM(converted_amount) as total')
            ->whereYear('transaction_date', $taxYear)
            ->whereIn('category_id', $categories->pluck('id'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return $categories->map(function (TransactionCategory $category) use ($totals): array {
            $total = (float) ($totals[$category->id] ?? 0);

            return [
                'category_id' => $category->id,
                'name' => $category->name,
                'income_or_expense' => $category->income_or_expense,
                'total' => $total,
                'has_mappings' => $category->tax_mappings_count > 0,
            ];
        })->values()->all();
    }

    /**
     * Compute tax form line item amounts for a filing and tax year.
     *
     * @return array<string, mixed>
     */
    public function computeTaxFormAmounts(int $taxYear, int $filingId): array
    {
        $filing = Filing::query()
            ->with('taxYear')
            ->findOrFail($filingId);

        $jurisdictionId = $filing->taxYear->jurisdiction_id;

        $categories = TransactionCategory::query()
            ->where('jurisdiction_id', $jurisdictionId)
            ->with('taxMappings')
            ->get();

        if ($categories->isEmpty()) {
            return [
                'filing_id' => $filing->id,
                'tax_year' => $taxYear,
                'jurisdiction_id' => $jurisdictionId,
                'line_items' => [],
            ];
        }

        $totals = Transaction::query()
            ->selectRaw('category_id, SUM(converted_amount) as total')
            ->whereYear('transaction_date', $taxYear)
            ->whereIn('category_id', $categories->pluck('id'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $lineItems = [];

        foreach ($categories as $category) {
            $total = (float) ($totals[$category->id] ?? 0);

            if ($total === 0.0) {
                continue;
            }

            foreach ($category->taxMappings as $mapping) {
                $code = $mapping->tax_form_code instanceof TaxFormCode
                    ? $mapping->tax_form_code->value
                    : (string) $mapping->tax_form_code;
                $key = $code.'|'.$mapping->line_item;

                if (! isset($lineItems[$key])) {
                    $lineItems[$key] = [
                        'tax_form_code' => $code,
                        'line_item' => $mapping->line_item,
                        'amount' => 0.0,
                        'category_ids' => [],
                    ];
                }

                $lineItems[$key]['amount'] += $total;
                $lineItems[$key]['category_ids'][] = $category->id;
            }
        }

        return [
            'filing_id' => $filing->id,
            'tax_year' => $taxYear,
            'jurisdiction_id' => $jurisdictionId,
            'line_items' => array_values($lineItems),
        ];
    }

    /**
     * Validate that a category has valid tax mappings.
     */
    public function validateMappings(int $categoryId): bool
    {
        $category = TransactionCategory::query()
            ->with('taxMappings')
            ->find($categoryId);

        if (! $category || $category->taxMappings->isEmpty()) {
            return false;
        }

        $validCodes = array_map(
            fn (TaxFormCode $code) => $code->value,
            TaxFormCode::cases()
        );

        foreach ($category->taxMappings as $mapping) {
            $code = $mapping->tax_form_code instanceof TaxFormCode
                ? $mapping->tax_form_code->value
                : (string) $mapping->tax_form_code;

            if (! in_array($code, $validCodes, true)) {
                return false;
            }

            if (! $mapping->line_item || ! $mapping->country) {
                return false;
            }
        }

        return true;
    }
}
