<?php

namespace App\Livewire\Finance;

use App\Models\DescriptionCategoryRule;
use App\Models\Jurisdiction;
use App\Models\TransactionCategory;
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

    public function render(): View
    {
        $jurisdictions = Jurisdiction::orderBy('name')->get();

        $categories = $this->jurisdictionId
            ? TransactionCategory::where('jurisdiction_id', $this->jurisdictionId)
                ->orderBy('name')
                ->get()
            : collect();

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
