<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
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
                Rule::in(array_map(fn ($case) => $case->value, AccountType::cases())),
            ],
            'currency_id' => [
                'required',
                'integer',
                'exists:currencies,id',
            ],
            'entity_id' => [
                'required',
                'integer',
                'exists:entities,id',
            ],
            'opening_date' => [
                'required',
                'date',
            ],
            'closing_date' => [
                'nullable',
                'date',
                'after:opening_date',
            ],
            'integration_metadata' => [
                'nullable',
                'json',
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
            'name.required' => 'The account name is required.',
            'name.string' => 'The account name must be a string.',
            'name.max' => 'The account name must not exceed 255 characters.',
            'type.required' => 'The account type is required.',
            'type.in' => 'The selected account type is invalid.',
            'currency_id.required' => 'The currency is required.',
            'currency_id.exists' => 'The selected currency does not exist.',
            'entity_id.required' => 'The entity is required.',
            'entity_id.exists' => 'The selected entity does not exist.',
            'opening_date.required' => 'The opening date is required.',
            'opening_date.date' => 'The opening date must be a valid date.',
            'closing_date.date' => 'The closing date must be a valid date.',
            'closing_date.after' => 'The closing date must be after the opening date.',
            'integration_metadata.json' => 'The integration metadata must be valid JSON.',
        ];
    }
}
