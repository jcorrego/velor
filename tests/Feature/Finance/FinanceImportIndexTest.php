<?php

use App\Models\Account;
use App\Models\Entity;
use App\Models\User;

it('shows the import index with user accounts', function () {
    $user = User::factory()->create();
    $entity = Entity::factory()->for($user)->create();
    $account = Account::factory()->for($entity)->create([
        'name' => 'Primary Checking',
    ]);

    $this->actingAs($user)
        ->get('/finance/import')
        ->assertOk()
        ->assertSee('Import Transactions')
        ->assertSee($account->name);
});
