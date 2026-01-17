<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\TaxFormCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryTaxMappingRequest extends FormRequest
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
            'category_id' => [
                'required',
                'integer',
                'exists:transaction_categories,id',
            ],
            'tax_form_code' => [
                'required',
                'string',
                Rule::in(array_map(
                    fn (TaxFormCode $code) => $code->value,
                    TaxFormCode::cases()
                )),
                Rule::unique('category_tax_mappings', 'tax_form_code')
                    ->where('category_id', $this->input('category_id'))
                    ->where('line_item', $this->input('line_item')),
            ],
            'line_item' => [
                'required',
                'string',
                'max:255',
            ],
            'country' => [
                'required',
                'string',
                'max:100',
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
            'category_id.required' => 'The category is required.',
            'category_id.exists' => 'The selected category does not exist.',
            'tax_form_code.required' => 'The tax form code is required.',
            'tax_form_code.in' => 'The tax form code is invalid.',
            'tax_form_code.unique' => 'This tax form mapping already exists for the selected category.',
            'line_item.required' => 'The line item is required.',
            'line_item.max' => 'The line item must not exceed 255 characters.',
            'country.required' => 'The country is required.',
            'country.max' => 'The country must not exceed 100 characters.',
        ];
    }
}
