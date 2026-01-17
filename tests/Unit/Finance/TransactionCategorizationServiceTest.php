<?php

use App\Models\Account;
use App\Models\Entity;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Services\Finance\TransactionCategorizationService;

it('resolves manual category overrides by id or name', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->create();

    $category = TransactionCategory::factory()
        ->forEntity($entity)
        ->create([
            'jurisdiction_id' => $entity->jurisdiction_id,
            'name' => 'Software',
        ]);

    $service = app(TransactionCategorizationService::class);

    $byId = $service->resolveCategoryId([
        'category_id' => $category->id,
    ], $account, []);

    $byName = $service->resolveCategoryId([
        'category_name' => 'software',
    ], $account, []);

    expect($byId)->toBe($category->id)
        ->and($byName)->toBe($category->id);
});

it('matches categories using regex rules', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->create();

    $category = TransactionCategory::factory()
        ->forEntity($entity)
        ->create([
            'jurisdiction_id' => $entity->jurisdiction_id,
            'name' => 'Software',
        ]);

    $rules = [
        [
            'pattern' => '/forge/i',
            'category_name' => 'Software',
            'fields' => ['description'],
        ],
    ];

    $service = app(TransactionCategorizationService::class);

    $resolved = $service->resolveCategoryId([
        'description' => 'Forge Global',
    ], $account, $rules);

    expect($resolved)->toBe($category->id);
});
