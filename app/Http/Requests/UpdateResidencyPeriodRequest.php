<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResidencyPeriodRequest extends FormRequest
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
                Rule::unique('residency_periods')->where(function ($query) {
                    return $query->where('jurisdiction_id', $this->jurisdiction_id)
                        ->where('start_date', $this->start_date);
                })->ignore($this->input('residency_period_id')),
            ],
            'jurisdiction_id' => [
                'required',
                'integer',
                'exists:jurisdictions,id',
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'is_fiscal_residence' => [
                'nullable',
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
            'user_id.required' => 'The user is required.',
            'user_id.exists' => 'The selected user does not exist.',
            'user_id.unique' => 'A residency period already exists for this user, jurisdiction, and start date.',
            'jurisdiction_id.required' => 'The jurisdiction is required.',
            'jurisdiction_id.exists' => 'The selected jurisdiction does not exist.',
            'start_date.required' => 'The start date is required.',
            'start_date.date' => 'The start date must be a valid date.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
            'is_fiscal_residence.boolean' => 'The fiscal residence flag must be true or false.',
        ];
    }
}
