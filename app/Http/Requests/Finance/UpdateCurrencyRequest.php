<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurrencyRequest extends FormRequest
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
            'currency_id' => [
                'required',
                'integer',
                'exists:currencies,id',
            ],
            'code' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
                Rule::unique('currencies', 'code')->ignore($this->input('currency_id')),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'symbol' => [
                'nullable',
                'string',
                'max:10',
            ],
            'is_active' => [
                'required',
                'boolean',
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
            'currency_id.required' => 'The currency is required.',
            'currency_id.exists' => 'The selected currency does not exist.',
            'code.required' => 'The currency code is required.',
            'code.size' => 'The currency code must be exactly 3 characters.',
            'code.regex' => 'The currency code must be uppercase ISO 4217 format.',
            'code.unique' => 'The currency code has already been taken.',
            'name.required' => 'The currency name is required.',
            'name.max' => 'The currency name must not exceed 255 characters.',
            'symbol.max' => 'The symbol must not exceed 10 characters.',
            'is_active.required' => 'The active status is required.',
            'is_active.boolean' => 'The active status must be true or false.',
        ];
    }
}
