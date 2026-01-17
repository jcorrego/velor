<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\AssetType;
use App\Enums\Finance\OwnershipStructure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
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
                'sometimes',
                'string',
                'max:255',
            ],
            'type' => [
                'sometimes',
                'string',
                Rule::in(array_map(fn ($case) => $case->value, AssetType::cases())),
            ],
            'ownership_structure' => [
                'sometimes',
                'string',
                Rule::in(array_map(fn ($case) => $case->value, OwnershipStructure::cases())),
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
            'name.string' => 'The asset name must be a string.',
            'name.max' => 'The asset name must not exceed 255 characters.',
            'type.in' => 'The selected asset type is invalid.',
            'ownership_structure.in' => 'The selected ownership structure is invalid.',
            'depreciation_method.in' => 'The selected depreciation method is invalid.',
            'useful_life_years.integer' => 'The useful life years must be an integer.',
            'useful_life_years.min' => 'The useful life years must be at least 1.',
            'useful_life_years.max' => 'The useful life years must not exceed 100.',
            'annual_depreciation_amount.decimal' => 'The annual depreciation amount must be a valid decimal with up to 2 decimal places.',
        ];
    }
}
