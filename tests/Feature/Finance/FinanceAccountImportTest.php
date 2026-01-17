<?php

use App\Models\Account;
use App\Models\Entity;
use App\Models\User;

it('shows the import page for an account', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->create();

    $this->actingAs($user)
        ->get("/finance/accounts/{$account->id}/import")
        ->assertOk()
        ->assertSee('Import Transactions');
});
