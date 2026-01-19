<?php

use App\Enums\Finance\TaxFormCode;
use App\Models\CategoryTaxMapping;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists tax mappings for the authenticated user', function () {
    $user = User::factory()->create();
    $category = TransactionCategory::factory()->create();
    $mapping = CategoryTaxMapping::factory()->forCategory($category)->create([
        'tax_form_code' => TaxFormCode::ScheduleE,
    ]);

    $otherCategory = TransactionCategory::factory()->create();
    CategoryTaxMapping::factory()->forCategory($otherCategory)->create();

    $response = $this->actingAs($user)->getJson('/api/category-tax-mappings');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('creates a category tax mapping', function () {
    $user = User::factory()->create();
    $category = TransactionCategory::factory()->create();

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

it('validates tax form code', function () {
    $user = User::factory()->create();
    $category = TransactionCategory::factory()->create();

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
    $category = TransactionCategory::factory()->create();
    $mapping = CategoryTaxMapping::factory()->forCategory($category)->create();

    $response = $this->actingAs($user)->deleteJson("/api/category-tax-mappings/{$mapping->id}");

    $response->assertNoContent();
    expect(CategoryTaxMapping::query()->whereKey($mapping->id)->exists())->toBeFalse();
});
