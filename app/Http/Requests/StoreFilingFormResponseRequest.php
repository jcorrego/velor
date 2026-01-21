<?php

namespace App\Http\Requests;

use App\Models\FormSchema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreFilingFormResponseRequest extends FormRequest
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
            'filing_id' => ['required', 'integer', 'exists:filings,id'],
            'form_schema_id' => ['required', 'integer', 'exists:form_schemas,id'],
            'form_data' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'filing_id.required' => 'The filing is required.',
            'filing_id.exists' => 'The selected filing does not exist.',
            'form_schema_id.required' => 'The form schema is required.',
            'form_schema_id.exists' => 'The selected form schema does not exist.',
            'form_data.array' => 'Form data must be an array.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rulesForSchema(FormSchema $schema): array
    {
        $rules = $this->rules();

        foreach ($this->fieldsFromSchema($schema) as $field) {
            $key = $field['key'];
            $rules["form_data.{$key}"] = [
                $field['required'] ? 'required' : 'nullable',
                'string',
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messagesForSchema(FormSchema $schema): array
    {
        $messages = $this->messages();

        foreach ($this->fieldsFromSchema($schema) as $field) {
            $label = $field['label'] ?? Str::headline($field['key'] ?? 'field');
            $key = $field['key'];

            $messages["form_data.{$key}.required"] = "The {$label} field is required.";
        }

        return $messages;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fieldsFromSchema(FormSchema $schema): array
    {
        $fields = [];

        foreach ($schema->sections ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if (! isset($field['key'])) {
                    continue;
                }

                $fields[] = $field;
            }
        }

        return $fields;
    }
}
