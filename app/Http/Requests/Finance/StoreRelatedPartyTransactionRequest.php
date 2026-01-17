<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\RelatedPartyType;
use App\Models\Entity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRelatedPartyTransactionRequest extends FormRequest
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
        $userId = $this->user()?->id;

        return [
            'transaction_date' => [
                'required',
                'date',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0',
            ],
            'type' => [
                'required',
                'string',
                Rule::in(array_map(
                    fn (RelatedPartyType $type) => $type->value,
                    RelatedPartyType::cases()
                )),
            ],
            'owner_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::in(array_filter([$userId])),
            ],
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->whereIn(
                        'entity_id',
                        Entity::query()
                            ->where('user_id', $userId)
                            ->select('id')
                    ),
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
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
            'amount.required' => 'The amount is required.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.min' => 'The amount must be at least 0.',
            'type.required' => 'The related party type is required.',
            'type.in' => 'The related party type is invalid.',
            'owner_id.required' => 'The owner is required.',
            'owner_id.exists' => 'The owner does not exist.',
            'owner_id.in' => 'The owner must be the authenticated user.',
            'account_id.required' => 'The account is required.',
            'account_id.exists' => 'The account must belong to the authenticated user.',
            'description.max' => 'The description must not exceed 1000 characters.',
        ];
    }
}
