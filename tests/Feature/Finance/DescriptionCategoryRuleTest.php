<?php

use App\Models\DescriptionCategoryRule;
use App\Models\Jurisdiction;
use App\Models\TransactionCategory;

test('can create a description category rule', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    $category = TransactionCategory::factory()->create(['jurisdiction_id' => $jurisdiction->id]);

    $rule = DescriptionCategoryRule::create([
        'jurisdiction_id' => $jurisdiction->id,
        'category_id' => $category->id,
        'description_pattern' => 'STARBUCKS',
        'notes' => 'Coffee expenses',
        'is_active' => true,
    ]);

    expect($rule->id)->toBeTruthy()
        ->and($rule->description_pattern)->toBe('STARBUCKS')
        ->and($rule->category_id)->toBe($category->id)
        ->and($rule->is_active)->toBeTrue();
});

test('finds matching rule by case-insensitive pattern', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    $category = TransactionCategory::factory()->create(['jurisdiction_id' => $jurisdiction->id]);

    $rule = DescriptionCategoryRule::create([
        'jurisdiction_id' => $jurisdiction->id,
        'category_id' => $category->id,
        'description_pattern' => 'STARBUCKS',
        'is_active' => true,
    ]);

    $descriptions = [
        'STARBUCKS COFFEE #1234',
        'starbucks coffee #1234',
        'Starbucks Coffee #1234',
        'STARBUCKS',
    ];

    foreach ($descriptions as $description) {
        $matched = DescriptionCategoryRule::where('jurisdiction_id', $jurisdiction->id)
            ->where('is_active', true)
            ->get()
            ->first(function (DescriptionCategoryRule $r) use ($description) {
                return str_starts_with(strtoupper($description), strtoupper($r->description_pattern));
            });

        expect($matched->id)->toBe($rule->id);
    }
});

test('does not match inactive rules', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    $category = TransactionCategory::factory()->create(['jurisdiction_id' => $jurisdiction->id]);

    $inactiveRule = DescriptionCategoryRule::create([
        'jurisdiction_id' => $jurisdiction->id,
        'category_id' => $category->id,
        'description_pattern' => 'STARBUCKS',
        'is_active' => false,
    ]);

    $matched = DescriptionCategoryRule::where('jurisdiction_id', $jurisdiction->id)
        ->where('is_active', true)
        ->get()
        ->first(function (DescriptionCategoryRule $r) {
            return str_starts_with('STARBUCKS COFFEE', strtoupper($r->description_pattern));
        });

    expect($matched)->toBeNull();
});

test('requires jurisdiction and category to exist', function () {
    $this->expectException(\Illuminate\Database\QueryException::class);

    DescriptionCategoryRule::create([
        'jurisdiction_id' => 999,
        'category_id' => 999,
        'description_pattern' => 'PATTERN',
        'is_active' => true,
    ]);
});

test('enforces unique jurisdiction and pattern combination', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    $category = TransactionCategory::factory()->create(['jurisdiction_id' => $jurisdiction->id]);

    DescriptionCategoryRule::create([
        'jurisdiction_id' => $jurisdiction->id,
        'category_id' => $category->id,
        'description_pattern' => 'STARBUCKS',
        'is_active' => true,
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    DescriptionCategoryRule::create([
        'jurisdiction_id' => $jurisdiction->id,
        'category_id' => $category->id,
        'description_pattern' => 'STARBUCKS',
        'is_active' => true,
    ]);
});

test('can update an existing rule', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    $category1 = TransactionCategory::factory()->create(['jurisdiction_id' => $jurisdiction->id]);
    $category2 = TransactionCategory::factory()->create(['jurisdiction_id' => $jurisdiction->id]);

    $rule = DescriptionCategoryRule::create([
        'jurisdiction_id' => $jurisdiction->id,
        'category_id' => $category1->id,
        'description_pattern' => 'STARBUCKS',
        'is_active' => true,
    ]);

    $rule->update([
        'category_id' => $category2->id,
        'is_active' => false,
    ]);

    $updated = DescriptionCategoryRule::find($rule->id);

    expect($updated->category_id)->toBe($category2->id)
        ->and($updated->is_active)->toBeFalse();
});

test('can delete a rule', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    $category = TransactionCategory::factory()->create(['jurisdiction_id' => $jurisdiction->id]);

    $rule = DescriptionCategoryRule::create([
        'jurisdiction_id' => $jurisdiction->id,
        'category_id' => $category->id,
        'description_pattern' => 'STARBUCKS',
        'is_active' => true,
    ]);

    $rule->delete();

    expect(DescriptionCategoryRule::find($rule->id))->toBeNull();
});

test('pattern matching returns first matching rule', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    $category1 = TransactionCategory::factory()->create(['jurisdiction_id' => $jurisdiction->id]);
    $category2 = TransactionCategory::factory()->create(['jurisdiction_id' => $jurisdiction->id]);

    DescriptionCategoryRule::create([
        'jurisdiction_id' => $jurisdiction->id,
        'category_id' => $category1->id,
        'description_pattern' => 'STARBUCKS',
        'is_active' => true,
    ]);

    DescriptionCategoryRule::create([
        'jurisdiction_id' => $jurisdiction->id,
        'category_id' => $category2->id,
        'description_pattern' => 'COFFEE',
        'is_active' => true,
    ]);

    $matched = DescriptionCategoryRule::where('jurisdiction_id', $jurisdiction->id)
        ->where('is_active', true)
        ->orderBy('description_pattern')
        ->get()
        ->first(function (DescriptionCategoryRule $r) {
            return str_starts_with(strtoupper('STARBUCKS COFFEE #1234'), strtoupper($r->description_pattern));
        });

    expect($matched->description_pattern)->toBe('STARBUCKS');
});
