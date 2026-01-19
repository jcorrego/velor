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
        ->create([
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
        ->create([
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

it('uses configured rules when none are provided', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->create();

    $category = TransactionCategory::factory()
        ->create([
            'name' => 'Consulting Income',
        ]);

    config()->set('finance.transaction_categorization_rules', [
        [
            'pattern' => '/payment received/i',
            'category_name' => 'Consulting Income',
            'fields' => ['description'],
        ],
    ]);

    $service = app(TransactionCategorizationService::class);

    $resolved = $service->resolveCategoryId([
        'description' => 'Payment received from client',
    ], $account);

    expect($resolved)->toBe($category->id);
});
