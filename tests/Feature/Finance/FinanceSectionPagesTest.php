<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows finance section pages for authenticated users', function (string $routeName, string $component, string $heading) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route($routeName));

    $response->assertSuccessful()
        ->assertSee($heading)
        ->assertSeeLivewire($component);
})->with([
    'accounts' => ['finance.accounts', 'finance.account-management', 'Accounts'],
    'transactions' => ['finance.transactions', 'finance.transaction-list', 'Transactions'],
    'assets' => ['finance.assets', 'finance.asset-management', 'Assets'],
    'year-end-values' => ['finance.year-end-values', 'finance.year-end-values', 'Year-End Values'],
    'categories' => ['finance.categories', 'finance.category-management', 'Categories'],
    'mappings' => ['finance.mappings', 'finance.category-mapping', 'Mappings'],
]);

it('redirects guests away from finance section pages', function (string $routeName) {
    $response = $this->get(route($routeName));

    $response->assertRedirect();
})->with([
    'accounts' => ['finance.accounts'],
    'transactions' => ['finance.transactions'],
    'assets' => ['finance.assets'],
    'year-end-values' => ['finance.year-end-values'],
    'categories' => ['finance.categories'],
    'mappings' => ['finance.mappings'],
]);
