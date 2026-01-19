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
    $category = TransactionCategory::factory()->create();

    $this->actingAs($user);

    $initialCount = CategoryTaxMapping::query()->count();

    Livewire::test('finance.category-mapping')
        ->set('category_id', $category->id)
        ->set('tax_form_code', TaxFormCode::ScheduleE->value)
        ->set('line_item', 'Rents received')
        ->set('country', 'USA')
        ->call('save')
        ->assertHasNoErrors();

    expect(CategoryTaxMapping::query()->count())->toBe($initialCount + 1)
        ->and(CategoryTaxMapping::query()->where('category_id', $category->id)->first()->category_id)->toBe($category->id);
});
