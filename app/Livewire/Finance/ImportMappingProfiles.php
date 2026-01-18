<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\ImportMappingProfile;
use Illuminate\View\View;
use Livewire\Component;

class ImportMappingProfiles extends Component
{
    public ?int $accountId = null;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public array $columnMapping = [];

    public array $availableColumns = [];

    public function mount(?int $accountId = null): void
    {
        $this->accountId = $accountId;
        if ($this->accountId) {
            $this->loadAvailableColumns();
        }
    }

    public function selectAccount(int $accountId): void
    {
        $this->accountId = $accountId;
        $this->resetForm();
        $this->loadAvailableColumns();
    }

    public function edit(int $profileId): void
    {
        $profile = ImportMappingProfile::findOrFail($profileId);

        $this->editingId = $profile->id;
        $this->name = $profile->name;
        $this->description = $profile->description ?? '';
        $this->columnMapping = $profile->column_mapping;
    }

    public function save(): void
    {
        $this->resetErrorBag();

        $validated = validator([
            'account_id' => $this->accountId,
            'name' => $this->name,
            'description' => $this->description,
            'column_mapping' => $this->columnMapping,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'column_mapping' => ['required', 'array'],
        ])->validate();

        if ($this->editingId) {
            $profile = ImportMappingProfile::findOrFail($this->editingId);
            $profile->update($validated);
        } else {
            ImportMappingProfile::create($validated);
        }

        $this->resetForm();
        $this->dispatch('profile-saved');
    }

    public function delete(int $profileId): void
    {
        ImportMappingProfile::findOrFail($profileId)->delete();

        if ($this->editingId === $profileId) {
            $this->resetForm();
        }

        $this->dispatch('profile-deleted');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function updateColumnMapping(string $csvColumn, string $transactionField): void
    {
        if (empty($csvColumn) || empty($transactionField)) {
            return;
        }

        $this->columnMapping[$csvColumn] = $transactionField;
    }

    public function removeColumnMapping(string $csvColumn): void
    {
        unset($this->columnMapping[$csvColumn]);
    }

    public function render(): View
    {
        $accounts = Account::query()
            ->orderBy('name')
            ->get();

        $profiles = $this->accountId
            ? ImportMappingProfile::where('account_id', $this->accountId)
                ->orderBy('name')
                ->get()
            : collect();

        return view('livewire.finance.import-mapping-profiles', [
            'accounts' => $accounts,
            'profiles' => $profiles,
        ])->layout('layouts.app', [
            'title' => __('Import Mapping Profiles'),
        ]);
    }

    private function loadAvailableColumns(): void
    {
        $this->availableColumns = [
            'date' => 'Transaction Date',
            'description' => 'Description',
            'amount' => 'Amount',
            'category' => 'Category',
            'reference' => 'Reference/ID',
            'counterparty' => 'Counterparty Name',
            'memo' => 'Memo',
        ];
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->columnMapping = [];
    }
}
