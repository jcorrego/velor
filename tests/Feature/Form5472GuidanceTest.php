<?php

declare(strict_types=1);

use App\Models\Filing;
use App\Models\FilingType;
use App\Models\FormSchema;
use App\Models\Jurisdiction;
use App\Models\TaxYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders form 5472 guidance and saves supplemental data', function () {
    $user = User::factory()->create();
    $usa = Jurisdiction::factory()->usa()->create();
    $taxYear = TaxYear::factory()->for($usa)->create(['year' => 2025]);

    $filingType = FilingType::factory()->for($usa)->create([
        'code' => '5472',
        'name' => 'Form 5472',
    ]);

    $filing = Filing::factory()
        ->for($user)
        ->for($taxYear)
        ->for($filingType)
        ->create();

    $schema = FormSchema::factory()->create([
        'form_code' => '5472',
        'tax_year' => 2025,
        'title' => 'Form 5472',
        'sections' => [
            [
                'key' => 'part-ii-foreign-shareholder',
                'title' => 'Part II – 25% Foreign Shareholder',
                'summary' => ['Shareholder identification details.'],
                'bullets' => [],
                'fields' => [
                    ['key' => 'shareholder_name', 'label' => 'Foreign shareholder name', 'type' => 'text', 'required' => true],
                ],
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test('finance.form-5472-guidance')
        ->set('filingId', (string) $filing->id)
        ->assertSee('Part II – 25% Foreign Shareholder')
        ->set('formData.shareholder_name', 'Acme Holdings')
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $filing->fresh();

    expect($fresh->form_schema_id)->toBe($schema->id)
        ->and($fresh->form_data['shareholder_name'])->toBe('Acme Holdings');
});
