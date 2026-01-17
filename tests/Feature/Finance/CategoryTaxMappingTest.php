<?php

use App\Enums\Finance\TaxFormCode;
use App\Models\CategoryTaxMapping;
use App\Models\Entity;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists tax mappings for the authenticated user', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $category = TransactionCategory::factory()->forEntity($entity)->create();
    expect($category->entity->user_id)->toBe($user->id);
    $mapping = CategoryTaxMapping::factory()->forCategory($category)->create([
        'tax_form_code' => TaxFormCode::ScheduleE,
    ]);

    $otherUser = User::factory()->create();
    $otherEntity = Entity::factory()->create(['user_id' => $otherUser->id]);
    $otherCategory = TransactionCategory::factory()->forEntity($otherEntity)->create();
    CategoryTaxMapping::factory()->forCategory($otherCategory)->create();

    $response = $this->actingAs($user)->getJson('/api/category-tax-mappings');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mapping->id);
});

it('creates a category tax mapping', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $category = TransactionCategory::factory()->forEntity($entity)->create();
    expect($category->entity->user_id)->toBe($user->id);

    $response = $this->actingAs($user)->postJson('/api/category-tax-mappings', [
        'category_id' => $category->id,
        'tax_form_code' => TaxFormCode::ScheduleE->value,
        'line_item' => 'Rents received',
        'country' => 'USA',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('tax_form_code', TaxFormCode::ScheduleE->value)
        ->assertJsonPath('category_id', $category->id);
});

it('rejects category tax mapping creation for another users category', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherEntity = Entity::factory()->create(['user_id' => $otherUser->id]);
    $otherCategory = TransactionCategory::factory()->forEntity($otherEntity)->create();

    $response = $this->actingAs($user)->postJson('/api/category-tax-mappings', [
        'category_id' => $otherCategory->id,
        'tax_form_code' => TaxFormCode::IRPF->value,
        'line_item' => 'Ingresos por alquileres',
        'country' => 'Spain',
    ]);

    $response->assertForbidden();
});

it('validates tax form code', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $category = TransactionCategory::factory()->forEntity($entity)->create();

    $response = $this->actingAs($user)->postJson('/api/category-tax-mappings', [
        'category_id' => $category->id,
        'tax_form_code' => 'invalid',
        'line_item' => 'Rents received',
        'country' => 'USA',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['tax_form_code']);
});

it('deletes a category tax mapping', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $category = TransactionCategory::factory()->forEntity($entity)->create();
    $mapping = CategoryTaxMapping::factory()->forCategory($category)->create();

    $response = $this->actingAs($user)->deleteJson("/api/category-tax-mappings/{$mapping->id}");

    $response->assertNoContent();
    expect(CategoryTaxMapping::query()->whereKey($mapping->id)->exists())->toBeFalse();
});
