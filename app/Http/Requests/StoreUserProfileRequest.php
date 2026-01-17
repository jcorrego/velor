<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserProfileRequest extends FormRequest
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
                Rule::unique('user_profiles')->where(function ($query) {
                    return $query->where('jurisdiction_id', $this->jurisdiction_id);
                }),
            ],
            'jurisdiction_id' => [
                'required',
                'integer',
                'exists:jurisdictions,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'tax_id' => [
                'required',
                'string',
                'max:255',
            ],
            'default_currency' => [
                'required',
                'string',
                'size:3',
            ],
            'display_currencies' => [
                'nullable',
                'array',
            ],
            'display_currencies.*' => [
                'string',
                'size:3',
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
            'user_id.unique' => 'This user already has a profile for this jurisdiction.',
            'jurisdiction_id.required' => 'The jurisdiction is required.',
            'jurisdiction_id.exists' => 'The selected jurisdiction does not exist.',
            'name.required' => 'The name is required.',
            'name.max' => 'The name must not exceed 255 characters.',
            'tax_id.required' => 'The tax ID is required.',
            'tax_id.max' => 'The tax ID must not exceed 255 characters.',
            'default_currency.required' => 'The default currency is required.',
            'default_currency.size' => 'The currency code must be exactly 3 characters.',
            'display_currencies.array' => 'Display currencies must be an array.',
            'display_currencies.*.size' => 'Each currency code must be exactly 3 characters.',
        ];
    }
}
