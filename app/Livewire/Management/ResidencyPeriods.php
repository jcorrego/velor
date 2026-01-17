<?php

namespace App\Livewire\Management;

use App\Http\Requests\StoreResidencyPeriodRequest;
use App\Http\Requests\UpdateResidencyPeriodRequest;
use App\Models\Jurisdiction;
use App\Models\ResidencyPeriod;
use Illuminate\View\View;
use Livewire\Component;

class ResidencyPeriods extends Component
{
    public ?int $editingId = null;

    public ?int $jurisdiction_id = null;

    public string $start_date = '';

    public string $end_date = '';

    public function edit(int $periodId): void
    {
        $period = ResidencyPeriod::query()
            ->where('user_id', auth()->id())
            ->with('jurisdiction')
            ->findOrFail($periodId);

        $this->editingId = $period->id;
        $this->jurisdiction_id = $period->jurisdiction_id;
        $this->start_date = $period->start_date->format('Y-m-d');
        $this->end_date = $period->end_date?->format('Y-m-d') ?? '';
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
            $period = ResidencyPeriod::query()
                ->where('user_id', auth()->id())
                ->findOrFail($this->editingId);

            $period->update($validated);
        } else {
            ResidencyPeriod::create($validated);
        }

        $this->resetForm();
        $this->dispatch('residency-saved');
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        return view('livewire.management.residency-periods', [
            'periods' => ResidencyPeriod::query()
                ->where('user_id', auth()->id())
                ->with('jurisdiction')
                ->orderBy('start_date')
                ->get(),
            'jurisdictions' => Jurisdiction::query()
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.app', [
            'title' => __('Residency Periods'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'user_id' => auth()->id(),
            'jurisdiction_id' => $this->jurisdiction_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date !== '' ? $this->end_date : null,
            'residency_period_id' => $this->editingId,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rulesForSave(array $data): array
    {
        $request = $this->editingId
            ? UpdateResidencyPeriodRequest::create('/', 'PATCH', $data)
            : StoreResidencyPeriodRequest::create('/', 'POST', $data);

        return $request->rules();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function messagesForSave(array $data): array
    {
        $request = $this->editingId
            ? UpdateResidencyPeriodRequest::create('/', 'PATCH', $data)
            : StoreResidencyPeriodRequest::create('/', 'POST', $data);

        return $request->messages();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->jurisdiction_id = null;
        $this->start_date = '';
        $this->end_date = '';
    }
}
