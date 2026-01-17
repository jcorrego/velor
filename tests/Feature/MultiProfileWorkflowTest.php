<?php

use App\Models\Jurisdiction;
use App\Models\User;
use App\Models\UserProfile;

test('user can create multiple profiles for different jurisdictions', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();
    $usa = Jurisdiction::factory()->usa()->create();
    $colombia = Jurisdiction::factory()->colombia()->create();

    $spainProfile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'name' => 'Juan Carlos Correa',
        'tax_id' => '12345678A',
        'default_currency' => 'EUR',
    ]);

    $usaProfile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $usa->id,
        'name' => 'John Correa',
        'tax_id' => '123-45-6789',
        'default_currency' => 'USD',
    ]);

    $colombiaProfile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $colombia->id,
        'name' => 'Juan Carlos Correa Gómez',
        'tax_id' => '1234567890',
        'default_currency' => 'COP',
    ]);

    expect($user->fresh()->load('profiles')->profiles)->toHaveCount(3)
        ->and($spainProfile->name)->toBe('Juan Carlos Correa')
        ->and($spainProfile->tax_id)->toBe('12345678A')
        ->and($usaProfile->name)->toBe('John Correa')
        ->and($usaProfile->tax_id)->toBe('123-45-6789')
        ->and($colombiaProfile->name)->toBe('Juan Carlos Correa Gómez')
        ->and($colombiaProfile->tax_id)->toBe('1234567890');
});

test('each profile has jurisdiction-specific currency', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();
    $usa = Jurisdiction::factory()->usa()->create();

    $spainProfile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'default_currency' => 'EUR',
    ]);

    $usaProfile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $usa->id,
        'default_currency' => 'USD',
    ]);

    expect($spainProfile->default_currency)->toBe('EUR')
        ->and($usaProfile->default_currency)->toBe('USD');
});

test('user cannot create duplicate profile for same jurisdiction', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
    ]);
});

test('profiles can have multiple display currencies', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();

    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'default_currency' => 'EUR',
        'display_currencies' => ['EUR', 'USD', 'GBP'],
    ]);

    expect($profile->display_currencies)->toBeArray()
        ->and($profile->display_currencies)->toHaveCount(3)
        ->and($profile->display_currencies)->toContain('EUR', 'USD', 'GBP');
});

test('different users can have profiles in same jurisdiction', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();

    $profile1 = UserProfile::factory()->create([
        'user_id' => $user1->id,
        'jurisdiction_id' => $spain->id,
    ]);

    $profile2 = UserProfile::factory()->create([
        'user_id' => $user2->id,
        'jurisdiction_id' => $spain->id,
    ]);

    expect($profile1->jurisdiction_id)->toBe($profile2->jurisdiction_id)
        ->and($profile1->user_id)->not->toBe($profile2->user_id);
});

test('user profiles maintain encrypted tax IDs independently', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();
    $usa = Jurisdiction::factory()->usa()->create();

    $spainProfile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'tax_id' => 'SPAIN-TAX-123',
    ]);

    $usaProfile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $usa->id,
        'tax_id' => 'USA-TAX-456',
    ]);

    // Each profile should have its own encrypted tax ID
    expect($spainProfile->tax_id)->toBe('SPAIN-TAX-123')
        ->and($usaProfile->tax_id)->toBe('USA-TAX-456')
        ->and($spainProfile->getAttributes()['tax_id'])->not->toBe('SPAIN-TAX-123')
        ->and($usaProfile->getAttributes()['tax_id'])->not->toBe('USA-TAX-456');
});
