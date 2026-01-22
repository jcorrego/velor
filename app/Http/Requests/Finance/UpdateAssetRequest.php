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
        ];
    }
}
