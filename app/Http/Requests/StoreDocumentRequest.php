<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
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
            ],
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:20480',
            ],
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'jurisdiction_id' => [
                'nullable',
                'integer',
                'exists:jurisdictions,id',
            ],
            'tax_year_id' => [
                'nullable',
                'integer',
                'exists:tax_years,id',
            ],
            'is_legal' => [
                'boolean',
            ],
            'tags' => [
                'nullable',
                'array',
            ],
            'tags.*' => [
                'string',
                'max:50',
            ],
            'entity_ids' => [
                'nullable',
                'array',
            ],
            'entity_ids.*' => [
                'integer',
                'exists:entities,id',
            ],
            'asset_ids' => [
                'nullable',
                'array',
            ],
            'asset_ids.*' => [
                'integer',
                'exists:assets,id',
            ],
            'transaction_ids' => [
                'nullable',
                'array',
            ],
            'transaction_ids.*' => [
                'integer',
                'exists:transactions,id',
            ],
            'filing_ids' => [
                'nullable',
                'array',
            ],
            'filing_ids.*' => [
                'integer',
                'exists:filings,id',
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
            'file.required' => 'A document file is required.',
            'file.file' => 'The document must be a valid file.',
            'file.mimes' => 'Documents must be PDF or image files.',
            'file.max' => 'Documents must not exceed 20MB.',
            'title.max' => 'The title must not exceed 255 characters.',
            'jurisdiction_id.exists' => 'The selected jurisdiction does not exist.',
            'tax_year_id.exists' => 'The selected tax year does not exist.',
            'tags.array' => 'Tags must be provided as a list.',
            'tags.*.max' => 'Tags must not exceed 50 characters.',
            'entity_ids.*.exists' => 'One or more selected entities do not exist.',
            'asset_ids.*.exists' => 'One or more selected assets do not exist.',
            'transaction_ids.*.exists' => 'One or more selected transactions do not exist.',
            'filing_ids.*.exists' => 'One or more selected filings do not exist.',
        ];
    }
}
