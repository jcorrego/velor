<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\AssetType;
use App\Enums\Finance\OwnershipStructure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'type' => [
                'required',
                'string',
                Rule::in(array_map(fn ($case) => $case->value, AssetType::cases())),
            ],
            'jurisdiction_id' => [
                'required',
                'integer',
                'exists:jurisdictions,id',
            ],
            'entity_id' => [
                'required',
                'integer',
                'exists:entities,id',
            ],
            'ownership_structure' => [
                'required',
                'string',
                Rule::in(array_map(fn ($case) => $case->value, OwnershipStructure::cases())),
            ],
            'acquisition_date' => [
                'required',
                'date',
            ],
            'acquisition_cost' => [
                'required',
                'decimal:0,2',
                'min:0.01',
            ],
            'acquisition_currency_id' => [
                'required',
                'integer',
                'exists:currencies,id',
            ],
            'depreciation_method' => [
                'nullable',
                'string',
                Rule::in('straight-line'),
            ],
            'useful_life_years' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'annual_depreciation_amount' => [
                'nullable',
                'decimal:0,2',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The asset name is required.',
            'name.string' => 'The asset name must be a string.',
            'name.max' => 'The asset name must not exceed 255 characters.',
            'type.required' => 'The asset type is required.',
            'type.in' => 'The selected asset type is invalid.',
            'jurisdiction_id.required' => 'The jurisdiction is required.',
            'jurisdiction_id.exists' => 'The selected jurisdiction does not exist.',
            'entity_id.required' => 'The entity is required.',
            'entity_id.exists' => 'The selected entity does not exist.',
            'ownership_structure.required' => 'The ownership structure is required.',
            'ownership_structure.in' => 'The selected ownership structure is invalid.',
            'acquisition_date.required' => 'The acquisition date is required.',
            'acquisition_date.date' => 'The acquisition date must be a valid date.',
            'acquisition_cost.required' => 'The acquisition cost is required.',
            'acquisition_cost.decimal' => 'The acquisition cost must be a valid decimal with up to 2 decimal places.',
            'acquisition_cost.min' => 'The acquisition cost must be at least 0.01.',
            'acquisition_currency_id.required' => 'The acquisition currency is required.',
            'acquisition_currency_id.exists' => 'The selected acquisition currency does not exist.',
            'depreciation_method.in' => 'The selected depreciation method is invalid.',
            'useful_life_years.integer' => 'The useful life years must be an integer.',
            'useful_life_years.min' => 'The useful life years must be at least 1.',
            'useful_life_years.max' => 'The useful life years must not exceed 100.',
            'annual_depreciation_amount.decimal' => 'The annual depreciation amount must be a valid decimal with up to 2 decimal places.',
        ];
    }
}
