<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class ImportTransactionsRequest extends FormRequest
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
            'file' => 'required|file|mimes:csv,txt|max:5120', // 5MB max
            'parser_type' => 'required|in:santander,mercury,bancolombia',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a CSV file to import.',
            'file.mimes' => 'The file must be a CSV or text file.',
            'file.max' => 'The file size cannot exceed 5MB.',
            'parser_type.required' => 'Please select which bank the file is from.',
            'parser_type.in' => 'Invalid bank selected.',
        ];
    }
}
