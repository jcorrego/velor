<?php

namespace App\Livewire\Finance;

use App\Models\DescriptionCategoryRule;
use App\Models\Jurisdiction;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;

class DescriptionCategoryRules extends Component
{
    public ?int $jurisdictionId = null;

    public ?int $editingId = null;

    public string $descriptionPattern = '';

    public ?int $categoryId = null;

    public string $notes = '';

    public bool $isActive = true;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $previewTransactions = [];

    public ?int $previewRuleId = null;

    public function mount(?int $jurisdictionId = null): void
    {
        $this->jurisdictionId = $jurisdictionId;
    }

    public function selectJurisdiction(int $jurisdictionId): void
    {
        $this->jurisdictionId = $jurisdictionId;
        $this->resetForm();
    }

    public function edit(int $ruleId): void
    {
        $rule = DescriptionCategoryRule::findOrFail($ruleId);

        $this->editingId = $rule->id;
        $this->descriptionPattern = $rule->description_pattern;
        $this->categoryId = $rule->category_id;
        $this->notes = $rule->notes ?? '';
        $this->isActive = $rule->is_active;
    }

    public function save(): void
    {
        $this->resetErrorBag();

        $validated = $this->validate([
            'descriptionPattern' => ['required', 'string', 'max:255'],
            'categoryId' => ['required', 'integer', 'exists:transaction_categories,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'isActive' => ['boolean'],
        ]);

        $validated['description_pattern'] = $validated['descriptionPattern'];
        $validated['category_id'] = $validated['categoryId'];
        $validated['is_active'] = $validated['isActive'];
        $validated['jurisdiction_id'] = $this->jurisdictionId;

        unset($validated['descriptionPattern'], $validated['categoryId'], $validated['isActive']);

        if ($this->editingId) {
            $rule = DescriptionCategoryRule::findOrFail($this->editingId);
            $rule->update($validated);
        } else {
            DescriptionCategoryRule::create($validated);
        }

        $this->resetForm();
        $this->dispatch('rule-saved');
    }

    public function delete(int $ruleId): void
    {
        DescriptionCategoryRule::findOrFail($ruleId)->delete();

        if ($this->editingId === $ruleId) {
            $this->resetForm();
        }

        $this->dispatch('rule-deleted');
    }

    public function toggleActive(int $ruleId): void
    {
        $rule = DescriptionCategoryRule::findOrFail($ruleId);
        $rule->update(['is_active' => ! $rule->is_active]);

        if ($this->editingId === $ruleId) {
            $this->isActive = ! $this->isActive;
        }
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function previewExisting(int $ruleId): void
    {
        $rule = DescriptionCategoryRule::findOrFail($ruleId);
        $this->previewRuleId = $rule->id;
        $this->previewTransactions = $this->buildPreviewTransactions($rule);
    }

    public function clearPreview(): void
    {
        $this->previewRuleId = null;
        $this->previewTransactions = [];
    }

    public function applyPreviewTransaction(int $transactionId): void
    {
        if (! $this->previewRuleId) {
            return;
        }

        $rule = DescriptionCategoryRule::findOrFail($this->previewRuleId);

        $transaction = $this->buildPreviewQuery($rule)
            ->where('transactions.id', $transactionId)
            ->first();

        if (! $transaction) {
            $this->previewTransactions = array_values(array_filter(
                $this->previewTransactions,
                fn (array $item) => $item['id'] !== $transactionId
            ));

            return;
        }

        $transaction->update([
            'category_id' => $rule->category_id,
        ]);

        $this->previewTransactions = array_values(array_filter(
            $this->previewTransactions,
            fn (array $item) => $item['id'] !== $transactionId
        ));
    }

    public function applyAllPreviewTransactions(): void
    {
        if (! $this->previewRuleId) {
            return;
        }

        $rule = DescriptionCategoryRule::findOrFail($this->previewRuleId);

        $this->buildPreviewQuery($rule)->update([
            'category_id' => $rule->category_id,
        ]);

        $this->previewTransactions = [];
    }

    public function render(): View
    {
        $jurisdictions = Jurisdiction::orderBy('name')->get();

        $categories = TransactionCategory::orderBy('name')->get();

        $rules = $this->jurisdictionId
            ? DescriptionCategoryRule::where('jurisdiction_id', $this->jurisdictionId)
                ->with('category')
                ->orderBy('description_pattern')
                ->get()
            : collect();

        return view('livewire.finance.description-category-rules', [
            'jurisdictions' => $jurisdictions,
            'categories' => $categories,
            'rules' => $rules,
        ])->layout('layouts.app', [
            'title' => __('Description Category Rules'),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPreviewTransactions(DescriptionCategoryRule $rule): array
    {
        return $this->buildPreviewQuery($rule)
            ->with(['category', 'originalCurrency'])
            ->orderByDesc('transactions.transaction_date')
            ->get()
            ->map(function (Transaction $transaction) use ($rule) {
                return [
                    'id' => $transaction->id,
                    'description' => $transaction->description,
                    'transaction_date' => $transaction->transaction_date?->format('Y-m-d'),
                    'amount' => $transaction->original_amount,
                    'currency' => $transaction->originalCurrency?->code,
                    'current_category' => $transaction->category?->name,
                    'new_category' => $rule->category?->name,
                ];
            })
            ->values()
            ->all();
    }

    private function buildPreviewQuery(DescriptionCategoryRule $rule): Builder
    {
        $pattern = strtolower(trim($rule->description_pattern));

        return Transaction::query()
            ->whereHas('account.entity', function ($query) use ($rule) {
                $query->where('jurisdiction_id', $rule->jurisdiction_id)
                    ->where('user_id', auth()->id());
            })
            ->whereNotNull('description')
            ->whereRaw('LOWER(description) LIKE ?', ["{$pattern}%"])
            ->where(function ($query) use ($rule) {
                $query->whereNull('category_id')
                    ->orWhere('category_id', '!=', $rule->category_id);
            });
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->descriptionPattern = '';
        $this->categoryId = null;
        $this->notes = '';
        $this->isActive = true;
        $this->resetErrorBag();
    }
}
