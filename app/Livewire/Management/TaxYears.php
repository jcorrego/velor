<?php

namespace App\Livewire\Management;

use App\Http\Requests\StoreTaxYearRequest;
use App\Models\Jurisdiction;
use App\Models\TaxYear;
use Illuminate\View\View;
use Livewire\Component;

class TaxYears extends Component
{
    public $jurisdiction_id = '';

    public $year = '';

    public function save(): void
    {
        $data = $this->formData();
        $validated = validator(
            $data,
            $this->rulesForSave($data),
            $this->messagesForSave($data),
        )->validate();

        TaxYear::create($validated);

        $this->reset(['jurisdiction_id', 'year']);

        session()->flash('message', 'Tax year created successfully.');
    }

    public function render(): View
    {
        return view('livewire.management.tax-years', [
            'jurisdictions' => Jurisdiction::query()
                ->orderBy('name')
                ->get(),
            'taxYears' => TaxYear::query()
                ->with('jurisdiction')
                ->orderByDesc('year')
                ->get()
                ->groupBy(fn (TaxYear $taxYear) => $taxYear->jurisdiction->name),
        ])->layout('layouts.app', [
            'title' => __('Tax Years'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'jurisdiction_id' => $this->jurisdiction_id,
            'year' => $this->year,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rulesForSave(array $data): array
    {
        $request = StoreTaxYearRequest::create('/', 'POST', $data);

        return $request->rules();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function messagesForSave(array $data): array
    {
        $request = StoreTaxYearRequest::create('/', 'POST', $data);

        return $request->messages();
    }
}
