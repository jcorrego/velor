<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated users can access owner-flow report page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.us-tax.owner-flow'));

    $response->assertSuccessful()
        ->assertSee('Owner-Flow Summary')
        ->assertSeeLivewire('finance.owner-flow-report');
});

test('guests cannot access owner-flow report page', function () {
    $response = $this->get(route('finance.us-tax.owner-flow'));

    $response->assertRedirect();
});

test('authenticated users can access schedule e report page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('finance.us-tax.schedule-e'));

    $response->assertSuccessful()
        ->assertSee('Schedule E Rental Summary')
        ->assertSeeLivewire('finance.schedule-e-rental-report');
});

test('guests cannot access schedule e report page', function () {
    $response = $this->get(route('finance.us-tax.schedule-e'));

    $response->assertRedirect();
});
