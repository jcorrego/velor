<?php

namespace App\Http\Requests;

use App\EntityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntityRequest extends FormRequest
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
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'jurisdiction_id' => [
                'required',
                'integer',
                'exists:jurisdictions,id',
            ],
            'type' => [
                'required',
                'string',
                Rule::in(array_map(fn ($case) => $case->value, EntityType::cases())),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'ein_or_tax_id' => [
                'nullable',
                'string',
                'max:255',
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
            'user_id.required' => 'The user is required.',
            'user_id.exists' => 'The selected user does not exist.',
            'jurisdiction_id.required' => 'The jurisdiction is required.',
            'jurisdiction_id.exists' => 'The selected jurisdiction does not exist.',
            'type.required' => 'The entity type is required.',
            'type.in' => 'The selected entity type is invalid.',
            'name.required' => 'The entity name is required.',
            'name.max' => 'The entity name must not exceed 255 characters.',
            'ein_or_tax_id.max' => 'The EIN or Tax ID must not exceed 255 characters.',
        ];
    }
}
