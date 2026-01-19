<?php

use App\Enums\Finance\TaxFormCode;
use App\Models\CategoryTaxMapping;
use App\Models\Entity;
use App\Models\TransactionCategory;
use App\Models\User;
use Livewire\Livewire;

it('creates a tax mapping for a category', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $category = TransactionCategory::factory()
        ->create([
            'jurisdiction_id' => $entity->jurisdiction_id,
        ]);

    $this->actingAs($user);

    Livewire::test('finance.category-mapping')
        ->set('category_id', $category->id)
        ->set('tax_form_code', TaxFormCode::ScheduleE->value)
        ->set('line_item', 'Rents received')
        ->set('country', 'USA')
        ->call('save')
        ->assertHasNoErrors();

    expect(CategoryTaxMapping::query()->count())->toBe(1)
        ->and(CategoryTaxMapping::query()->first()->category_id)->toBe($category->id);
});
