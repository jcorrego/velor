<?php

use App\Models\User;

it('displays the finance page when authenticated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/finance')
        ->assertOk();
});

it('requires authentication to access finance page', function () {
    $this->get('/finance')
        ->assertRedirect('/login');
});
