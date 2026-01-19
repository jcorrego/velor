<?php

use App\Models\Entity;
use App\Models\Jurisdiction;
use App\Models\TransactionCategory;
use App\Models\User;
use Livewire\Livewire;

it('requires unique category names per jurisdiction', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $jurisdiction = Jurisdiction::factory()->create();

    TransactionCategory::factory()->create([
        'name' => 'Groceries',
        'jurisdiction_id' => $jurisdiction->id,
        'income_or_expense' => 'expense',
    ]);

    $this->actingAs($user);

    Livewire::test('finance.category-management')
        ->set('name', 'Groceries')
        ->set('jurisdiction_id', $jurisdiction->id)
        ->set('income_or_expense', 'expense')
        ->set('sort_order', 0)
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});
