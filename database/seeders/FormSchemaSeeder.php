<?php

namespace Database\Seeders;

use App\FormSchemaParser;
use App\Models\FormSchema;
use Illuminate\Database\Seeder;

class FormSchemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contents = file_get_contents(resource_path('forms/5472/contents.md'));

        if (! $contents) {
            return;
        }

        $parser = app(FormSchemaParser::class);
        $sections = $this->withFields($parser->parse($contents));

        foreach ([2025, 2026] as $taxYear) {
            FormSchema::updateOrCreate(
                [
                    'form_code' => '5472',
                    'tax_year' => $taxYear,
                ],
                [
                    'title' => 'Form 5472',
                    'sections' => $sections,
                ]
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array<string, mixed>>
     */
    private function withFields(array $sections): array
    {
        $fieldsBySection = $this->fieldsBySection();

        return array_map(function (array $section) use ($fieldsBySection): array {
            $section['fields'] = $fieldsBySection[$section['key']] ?? [];

            return $section;
        }, $sections);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function fieldsBySection(): array
    {
        return [
            '1-identifying-information-initial-part' => [
                ['key' => 'reporting_corp_name', 'label' => 'Reporting corporation name', 'type' => 'text', 'required' => true],
                ['key' => 'reporting_corp_address', 'label' => 'Reporting corporation address', 'type' => 'text', 'required' => false],
                ['key' => 'reporting_corp_ein', 'label' => 'EIN', 'type' => 'text', 'required' => false],
                ['key' => 'country_incorporation', 'label' => 'Country of incorporation', 'type' => 'text', 'required' => false],
                ['key' => 'principal_activity', 'label' => 'Principal business activity', 'type' => 'text', 'required' => false],
                ['key' => 'total_assets', 'label' => 'Total assets', 'type' => 'text', 'required' => false],
                ['key' => 'filer_type', 'label' => 'Type of filer', 'type' => 'text', 'required' => false],
            ],
            '2-part-i-reporting-corporation' => [
                ['key' => 'date_of_incorporation', 'label' => 'Date of incorporation', 'type' => 'date', 'required' => false],
                ['key' => 'country_of_organization', 'label' => 'Country of organization', 'type' => 'text', 'required' => false],
                ['key' => 'entity_type', 'label' => 'Type of entity', 'type' => 'text', 'required' => false],
                ['key' => 'accounting_method', 'label' => 'Accounting method', 'type' => 'text', 'required' => false],
            ],
            '3-part-ii-25-foreign-shareholder' => [
                ['key' => 'shareholder_name', 'label' => 'Foreign shareholder name', 'type' => 'text', 'required' => true],
                ['key' => 'shareholder_address', 'label' => 'Foreign shareholder address', 'type' => 'text', 'required' => false],
                ['key' => 'shareholder_country', 'label' => 'Country of residence', 'type' => 'text', 'required' => false],
                ['key' => 'ownership_percentage', 'label' => 'Percentage of ownership', 'type' => 'text', 'required' => false],
                ['key' => 'ownership_type', 'label' => 'Nature of ownership (direct/indirect)', 'type' => 'text', 'required' => false],
                ['key' => 'shareholder_activity', 'label' => 'Principal business activity of shareholder', 'type' => 'text', 'required' => false],
            ],
            '4-part-iii-related-party' => [
                ['key' => 'related_parties', 'label' => 'Related party details', 'type' => 'textarea', 'required' => false],
            ],
            '7-part-vi-additional-information' => [
                ['key' => 'ownership_changes', 'label' => 'Ownership changes', 'type' => 'text', 'required' => false],
                ['key' => 'accurate_information', 'label' => 'Accurate information confirmation', 'type' => 'text', 'required' => false],
                ['key' => 'other_transactions', 'label' => 'Other relevant transactions', 'type' => 'textarea', 'required' => false],
            ],
        ];
    }
}
