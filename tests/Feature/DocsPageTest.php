<?php

use App\Models\User;

it('shows the documentation page for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/docs')
        ->assertOk()
        ->assertSee('Documentation');
});
