<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class RentalPropertyReportRequest extends FormRequest
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
        $maxYear = now()->year + 1;

        return [
            'year' => [
                'required',
                'integer',
                'min:1900',
                "max:{$maxYear}",
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
            'year.required' => 'The report year is required.',
            'year.integer' => 'The report year must be an integer.',
            'year.min' => 'The report year must be 1900 or later.',
            'year.max' => 'The report year must not be in the far future.',
        ];
    }
}
