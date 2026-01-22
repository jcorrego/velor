<?php

namespace App\Http\Requests;

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
            'form_data.array' => 'Form data must be an array.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rulesForSchema(?array $schema, string $prefix = 'form_data'): array
    {
        $rules = $this->rules();

        if ($prefix !== 'form_data' && isset($rules['form_data'])) {
            $rules[$prefix] = $rules['form_data'];
            unset($rules['form_data']);
        }

        if (! $schema) {
            return $rules;
        }

        foreach ($this->fieldsFromSchema($schema) as $field) {
            $key = $field['key'];
            $typeRule = $this->ruleForFieldType($field['type'] ?? 'text');
            $rules["{$prefix}.{$key}"] = array_filter([
                $field['required'] ? 'required' : 'nullable',
                $typeRule,
            ]);
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messagesForSchema(?array $schema, string $prefix = 'form_data'): array
    {
        $messages = $this->messages();

        if ($prefix !== 'form_data') {
            $messages = collect($messages)
                ->mapWithKeys(fn (string $message, string $key) => [str_replace('form_data', $prefix, $key) => $message])
                ->all();
        }

        if (! $schema) {
            return $messages;
        }

        foreach ($this->fieldsFromSchema($schema) as $field) {
            $label = $field['label'] ?? Str::headline($field['key'] ?? 'field');
            $key = $field['key'];

            $messages["{$prefix}.{$key}.required"] = "The {$label} field is required.";
        }

        return $messages;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<int, array<string, mixed>>
     */
    private function fieldsFromSchema(array $schema): array
    {
        $fields = [];

        foreach ($schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if (! isset($field['key'])) {
                    continue;
                }

                $fields[] = $field;
            }
        }

        return $fields;
    }

    private function ruleForFieldType(string $type): string
    {
        return match ($type) {
            'date' => 'date',
            'boolean' => 'boolean',
            default => 'string',
        };
    }
}
