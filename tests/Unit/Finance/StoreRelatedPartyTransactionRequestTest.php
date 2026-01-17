<?php

use App\Enums\Finance\RelatedPartyType;
use App\Http\Requests\Finance\StoreRelatedPartyTransactionRequest;
use App\Models\Account;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

it('validates related party transaction for authenticated user', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);

    $request = new StoreRelatedPartyTransactionRequest;
    $request->setUserResolver(fn () => $user);

    $data = [
        'transaction_date' => '2024-02-01',
        'amount' => 1000,
        'type' => RelatedPartyType::OwnerContribution->value,
        'owner_id' => $user->id,
        'account_id' => $account->id,
        'description' => 'Initial capitalization',
    ];

    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->passes())->toBeTrue();
});

it('rejects account not owned by user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherEntity = Entity::factory()->create(['user_id' => $otherUser->id]);
    $otherAccount = Account::factory()->create(['entity_id' => $otherEntity->id]);

    $request = new StoreRelatedPartyTransactionRequest;
    $request->setUserResolver(fn () => $user);

    $data = [
        'transaction_date' => '2024-02-01',
        'amount' => 1000,
        'type' => RelatedPartyType::OwnerContribution->value,
        'owner_id' => $user->id,
        'account_id' => $otherAccount->id,
    ];

    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('account_id'))->toBeTrue();
});

it('rejects mismatched owner id', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->create(['user_id' => $user->id]);
    $account = Account::factory()->create(['entity_id' => $entity->id]);
    $otherUser = User::factory()->create();

    $request = new StoreRelatedPartyTransactionRequest;
    $request->setUserResolver(fn () => $user);

    $data = [
        'transaction_date' => '2024-02-01',
        'amount' => 1000,
        'type' => RelatedPartyType::OwnerContribution->value,
        'owner_id' => $otherUser->id,
        'account_id' => $account->id,
    ];

    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('owner_id'))->toBeTrue();
});
