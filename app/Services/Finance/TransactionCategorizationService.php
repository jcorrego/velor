<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\TransactionCategory;
use Illuminate\Support\Collection;

class TransactionCategorizationService
{
    /**
     * Resolve a category id for imported transaction data.
     */
    public function resolveCategoryId(array $transaction, Account $account, ?array $rules = null): ?int
    {
        $match = $this->resolveRuleMatch($transaction, $account, $rules);

        return $match['category_id'] ?? null;
    }

    /**
     * Resolve matched rule attributes for imported transaction data.
     *
     * @return array{category_id: int|null, counterparty: string|null}
     */
    public function resolveRuleMatch(array $transaction, Account $account, ?array $rules = null): array
    {
        $categories = TransactionCategory::all();

        if ($categories->isEmpty()) {
            return ['category_id' => null, 'counterparty' => null];
        }

        $manualId = $this->resolveManualCategoryId($transaction, $categories);
        if ($manualId) {
            return ['category_id' => $manualId, 'counterparty' => null];
        }

        $rules ??= config('finance.transaction_categorization_rules', []);

        return $this->resolveRuleMatchFromRules($transaction, $categories, $rules);
    }

    /**
     * @param  Collection<int, TransactionCategory>  $categories
     */
    private function resolveManualCategoryId(array $transaction, Collection $categories): ?int
    {
        $categoryId = $transaction['category_id'] ?? null;
        if ($categoryId) {
            return $categories->firstWhere('id', (int) $categoryId)?->id;
        }

        $categoryName = $transaction['category_name'] ?? null;
        if (! $categoryName) {
            return null;
        }

        $normalized = strtolower(trim((string) $categoryName));

        return $categories
            ->first(fn (TransactionCategory $category) => strtolower($category->name) === $normalized)
            ?->id;
    }

    /**
     * @param  Collection<int, TransactionCategory>  $categories
     * @param  array<int, array<string, mixed>>  $rules
     */
    /**
     * @param  Collection<int, TransactionCategory>  $categories
     * @param  array<int, array<string, mixed>>  $rules
     * @return array{category_id: int|null, counterparty: string|null}
     */
    private function resolveRuleMatchFromRules(array $transaction, Collection $categories, array $rules): array
    {
        if ($rules === []) {
            return ['category_id' => null, 'counterparty' => null];
        }

        $description = (string) ($transaction['description'] ?? '');
        $counterparty = (string) ($transaction['counterparty'] ?? '');
        $bankDescription = (string) ($transaction['bank_description'] ?? '');

        foreach ($rules as $rule) {
            $pattern = $rule['pattern'] ?? null;
            if (! $pattern || ! is_string($pattern)) {
                continue;
            }

            $fields = $rule['fields'] ?? ['description'];
            $haystack = $this->buildHaystack($fields, $description, $counterparty, $bankDescription);

            if ($haystack === '' || ! preg_match($pattern, $haystack)) {
                continue;
            }

            $categoryId = $rule['category_id'] ?? null;
            if ($categoryId) {
                return [
                    'category_id' => $categories->firstWhere('id', (int) $categoryId)?->id,
                    'counterparty' => $rule['counterparty'] ?? null,
                ];
            }

            $categoryName = $rule['category_name'] ?? null;
            if ($categoryName) {
                $normalized = strtolower(trim((string) $categoryName));

                return [
                    'category_id' => $categories
                        ->first(fn (TransactionCategory $category) => strtolower($category->name) === $normalized)
                        ?->id,
                    'counterparty' => $rule['counterparty'] ?? null,
                ];
            }
        }

        return ['category_id' => null, 'counterparty' => null];
    }

    /**
     * @param  array<int, string>  $fields
     */
    private function buildHaystack(array $fields, string $description, string $counterparty, string $bankDescription): string
    {
        $values = [];

        foreach ($fields as $field) {
            $values[] = match ($field) {
                'counterparty' => $counterparty,
                'bank_description' => $bankDescription,
                default => $description,
            };
        }

        return trim(implode(' ', array_filter($values)));
    }
}
