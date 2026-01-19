<?php

use App\Models\Jurisdiction;
use App\Models\User;

test('can access the category rules management page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('management.description-category-rules'));

    $response->assertSuccessful();
    $response->assertSee('Description Category Rules');
});

test('shows jurisdictions on the page', function () {
    $user = User::factory()->create();
    Jurisdiction::factory()->create(['name' => 'US Federal']);

    $this->actingAs($user);

    $response = $this->get(route('management.description-category-rules'));

    $response->assertSuccessful()
        ->assertSee('US Federal');
});

test('requires authentication to access the page', function () {
    $response = $this->get(route('management.description-category-rules'));

    $response->assertRedirect('/login');
});
