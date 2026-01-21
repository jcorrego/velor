<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FormSchema>
 */
class FormSchemaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_code' => '5472',
            'tax_year' => 2025,
            'title' => 'Form 5472',
            'sections' => [
                [
                    'key' => 'identifying-information',
                    'title' => 'Identifying Information',
                    'summary' => ['Basic data of the entity filing this form.'],
                    'bullets' => ['Reporting corporation name', 'EIN', 'Country of incorporation'],
                    'fields' => [
                        ['key' => 'reporting_corp_name', 'label' => 'Reporting corporation name', 'type' => 'text', 'required' => true],
                    ],
                ],
            ],
        ];
    }
}
