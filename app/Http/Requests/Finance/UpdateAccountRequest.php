<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
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
                Rule::in(array_map(fn ($case) => $case->value, AccountType::cases())),
            ],
            'currency_id' => [
                'sometimes',
                'integer',
                'exists:currencies,id',
            ],
            'entity_id' => [
                'sometimes',
                'integer',
                'exists:entities,id',
            ],
            'closing_date' => [
                'nullable',
                'date',
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
            'name.string' => 'The account name must be a string.',
            'name.max' => 'The account name must not exceed 255 characters.',
            'type.in' => 'The selected account type is invalid.',
            'currency_id.exists' => 'The selected currency does not exist.',
            'entity_id.exists' => 'The selected entity does not exist.',
            'closing_date.date' => 'The closing date must be a valid date.',
            'integration_metadata.json' => 'The integration metadata must be valid JSON.',
        ];
    }
}
