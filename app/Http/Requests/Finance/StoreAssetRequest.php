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
        ];
    }
}
