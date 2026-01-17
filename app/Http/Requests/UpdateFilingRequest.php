<?php

namespace App\Http\Requests;

use App\FilingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFilingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // When creating, just check authentication
        if (! $this->has('filing_id')) {
            return true;
        }

        // When updating, ensure user owns the filing
        $filingId = $this->input('filing_id');
        $userId = $this->input('user_id');

        // Verify the filing exists and belongs to the authenticated user
        return auth()->check() && auth()->id() === $userId;
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
                Rule::unique('filings')->where(function ($query) {
                    return $query->where('tax_year_id', $this->tax_year_id)
                        ->where('filing_type_id', $this->filing_type_id);
                })->ignore($this->input('filing_id')),
            ],
            'tax_year_id' => [
                'required',
                'integer',
                'exists:tax_years,id',
            ],
            'filing_type_id' => [
                'required',
                'integer',
                'exists:filing_types,id',
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(array_map(fn ($case) => $case->value, FilingStatus::cases())),
            ],
            'key_metrics' => [
                'nullable',
                'array',
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
            'user_id.unique' => 'A filing already exists for this user, tax year, and filing type.',
            'tax_year_id.required' => 'The tax year is required.',
            'tax_year_id.exists' => 'The selected tax year does not exist.',
            'filing_type_id.required' => 'The filing type is required.',
            'filing_type_id.exists' => 'The selected filing type does not exist.',
            'status.in' => 'The selected status is invalid.',
            'key_metrics.array' => 'Key metrics must be an array.',
        ];
    }
}
