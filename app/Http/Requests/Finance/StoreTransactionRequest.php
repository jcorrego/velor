<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
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
            'transaction_date' => [
                'required',
                'date',
            ],
            'account_id' => [
                'required',
                'integer',
                'exists:accounts,id',
            ],
            'type' => [
                'required',
                'string',
                Rule::in(array_map(fn ($case) => $case->value, TransactionType::cases())),
            ],
            'original_amount' => [
                'required',
                'decimal:0,2',
                'min:0.01',
            ],
            'original_currency_id' => [
                'required',
                'integer',
                'exists:currencies,id',
            ],
            'category_id' => [
                'nullable',
                'integer',
                'exists:transaction_categories,id',
            ],
            'counterparty_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'tags' => [
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
            'transaction_date.required' => 'The transaction date is required.',
            'transaction_date.date' => 'The transaction date must be a valid date.',
            'account_id.required' => 'The account is required.',
            'account_id.exists' => 'The selected account does not exist.',
            'type.required' => 'The transaction type is required.',
            'type.in' => 'The selected transaction type is invalid.',
            'original_amount.required' => 'The amount is required.',
            'original_amount.decimal' => 'The amount must be a valid decimal with up to 2 decimal places.',
            'original_amount.min' => 'The amount must be at least 0.01.',
            'original_currency_id.required' => 'The currency is required.',
            'original_currency_id.exists' => 'The selected currency does not exist.',
            'category_id.exists' => 'The selected category does not exist.',
            'counterparty_name.string' => 'The counterparty name must be a string.',
            'counterparty_name.max' => 'The counterparty name must not exceed 255 characters.',
            'description.string' => 'The description must be a string.',
            'description.max' => 'The description must not exceed 1000 characters.',
            'tags.json' => 'The tags must be valid JSON.',
        ];
    }
}
