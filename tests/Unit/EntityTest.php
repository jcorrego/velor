<?php

use App\EntityType;
use App\Models\Entity;
use App\Models\Jurisdiction;
use App\Models\User;

test('entity can be created with factory', function () {
    $entity = Entity::factory()->create();

    expect($entity->exists)->toBeTrue()
        ->and($entity->user_id)->toBeInt()
        ->and($entity->jurisdiction_id)->toBeInt()
        ->and($entity->type)->toBeInstanceOf(EntityType::class)
        ->and($entity->name)->toBeString();
});

test('entity type is cast to enum', function () {
    $entity = Entity::factory()->create(['type' => EntityType::LLC]);

    expect($entity->type)->toBeInstanceOf(EntityType::class)
        ->and($entity->type)->toBe(EntityType::LLC)
        ->and($entity->type->value)->toBe('llc');
});

test('ein_or_tax_id is encrypted', function () {
    $entity = Entity::factory()->create([
        'ein_or_tax_id' => '12-3456789',
    ]);

    // Value in memory should be decrypted
    expect($entity->ein_or_tax_id)->toBe('12-3456789');

    // Value in database should be encrypted (different from plaintext)
    $rawValue = $entity->getAttributes()['ein_or_tax_id'];
    expect($rawValue)->not->toBe('12-3456789');
});

test('entity belongs to user', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);

    expect($entity->user)->toBeInstanceOf(User::class)
        ->and($entity->user->id)->toBe($user->id);
});

test('entity belongs to jurisdiction', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    $entity = Entity::factory()->create(['jurisdiction_id' => $jurisdiction->id]);

    expect($entity->jurisdiction)->toBeInstanceOf(Jurisdiction::class)
        ->and($entity->jurisdiction->id)->toBe($jurisdiction->id);
});

test('entity factory has state methods for types', function () {
    $individual = Entity::factory()->individual()->create();
    $llc = Entity::factory()->llc()->create();

    expect($individual->type)->toBe(EntityType::Individual)
        ->and($llc->type)->toBe(EntityType::LLC);
});

test('entity can be created without ein_or_tax_id', function () {
    $entity = Entity::factory()->create(['ein_or_tax_id' => null]);

    expect($entity->ein_or_tax_id)->toBeNull();
});
