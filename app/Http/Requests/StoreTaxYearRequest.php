<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxYearRequest extends FormRequest
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
            'jurisdiction_id' => [
                'required',
                'exists:jurisdictions,id',
            ],
            'year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                Rule::unique('tax_years', 'year')->where('jurisdiction_id', $this->input('jurisdiction_id')),
            ],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jurisdiction_id.required' => 'The jurisdiction is required.',
            'jurisdiction_id.exists' => 'The selected jurisdiction does not exist.',
            'year.required' => 'The tax year is required.',
            'year.integer' => 'The tax year must be a valid year.',
            'year.min' => 'The tax year must be at least 2000.',
            'year.max' => 'The tax year must be 2100 or earlier.',
            'year.unique' => 'A tax year already exists for this jurisdiction.',
        ];
    }
}
