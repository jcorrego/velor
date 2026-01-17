<?php

namespace App\Livewire\Management;

use App\FilingStatus;
use App\Http\Requests\StoreFilingRequest;
use App\Http\Requests\UpdateFilingRequest;
use App\Models\Filing;
use App\Models\FilingType;
use App\Models\TaxYear;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class Filings extends Component
{
    public ?int $editingId = null;

    public ?int $tax_year_id = null;

    public ?int $filing_type_id = null;

    public string $status = '';

    public function mount(): void
    {
        $this->status = FilingStatus::Planning->value;
    }

    public function edit(int $filingId): void
    {
        $filing = Filing::query()
            ->where('user_id', auth()->id())
            ->with(['taxYear.jurisdiction', 'filingType.jurisdiction'])
            ->findOrFail($filingId);

        $this->editingId = $filing->id;
        $this->tax_year_id = $filing->tax_year_id;
        $this->filing_type_id = $filing->filing_type_id;
        $this->status = $filing->status->value;
    }

    public function save(): void
    {
        $data = $this->formData();
        $validated = validator(
            $data,
            $this->rulesForSave($data),
            $this->messagesForSave($data),
        )->validate();

        if ($this->editingId) {
            $filing = Filing::query()
                ->where('user_id', auth()->id())
                ->findOrFail($this->editingId);

            $filing->update($validated);
        } else {
            Filing::create($validated);
        }

        $this->resetForm();
        $this->dispatch('filing-saved');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        return view('livewire.management.filings', [
            'filings' => Filing::query()
                ->where('user_id', auth()->id())
                ->with(['taxYear.jurisdiction', 'filingType.jurisdiction'])
                ->orderByDesc('created_at')
                ->get(),
            'taxYears' => TaxYear::query()
                ->with('jurisdiction')
                ->orderByDesc('year')
                ->get(),
            'filingTypes' => FilingType::query()
                ->with('jurisdiction')
                ->orderBy('name')
                ->get(),
            'statusOptions' => FilingStatus::cases(),
            'statusLabel' => fn (FilingStatus $status) => Str::headline($status->value),
        ])->layout('layouts.app', [
            'title' => __('Filings'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'user_id' => auth()->id(),
            'tax_year_id' => $this->tax_year_id,
            'filing_type_id' => $this->filing_type_id,
            'status' => $this->status,
            'filing_id' => $this->editingId,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rulesForSave(array $data): array
    {
        $request = $this->editingId
            ? UpdateFilingRequest::create('/', 'PATCH', $data)
            : StoreFilingRequest::create('/', 'POST', $data);

        return $request->rules();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function messagesForSave(array $data): array
    {
        $request = $this->editingId
            ? UpdateFilingRequest::create('/', 'PATCH', $data)
            : StoreFilingRequest::create('/', 'POST', $data);

        return $request->messages();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->tax_year_id = null;
        $this->filing_type_id = null;
        $this->status = FilingStatus::Planning->value;
    }
}
