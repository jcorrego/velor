<?php

namespace App\Livewire\Management;

use App\Models\Jurisdiction;
use App\Models\TaxYear;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class TaxYears extends Component
{
    public $jurisdiction_id = '';

    public $year = '';

    public function save(): void
    {
        $validated = $this->validate([
            'jurisdiction_id' => [
                'required',
                'exists:jurisdictions,id',
            ],
            'year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                Rule::unique('tax_years', 'year')->where('jurisdiction_id', $this->jurisdiction_id),
            ],
        ], [
            'jurisdiction_id.required' => 'The jurisdiction is required.',
            'jurisdiction_id.exists' => 'The selected jurisdiction does not exist.',
            'year.required' => 'The tax year is required.',
            'year.integer' => 'The tax year must be a valid year.',
            'year.min' => 'The tax year must be at least 2000.',
            'year.max' => 'The tax year must be 2100 or earlier.',
            'year.unique' => 'A tax year already exists for this jurisdiction.',
        ]);

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
}
