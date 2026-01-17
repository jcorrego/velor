<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
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
            // Fields that CAN be updated
            'category_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:transaction_categories,id',
            ],
            'counterparty_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
            'tags' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'reconciled_at' => [
                'sometimes',
                'nullable',
                'date_format:Y-m-d H:i:s',
            ],
            // Note: transaction_date, account_id, type, amounts, currencies are NOT updatable
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
            'category_id.exists' => 'The selected category does not exist.',
            'counterparty_name.string' => 'The counterparty name must be a string.',
            'counterparty_name.max' => 'The counterparty name must not exceed 255 characters.',
            'description.string' => 'The description must be a string.',
            'description.max' => 'The description must not exceed 1000 characters.',
            'reconciled_at.date_format' => 'The reconciled date must be in format Y-m-d H:i:s.',
        ];
    }
}
