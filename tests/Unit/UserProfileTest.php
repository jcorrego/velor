<?php

use App\Models\Jurisdiction;
use App\Models\User;
use App\Models\UserProfile;

test('user profile can be created with factory', function () {
    $profile = UserProfile::factory()->create();

    expect($profile->exists)->toBeTrue()
        ->and($profile->user_id)->toBeInt()
        ->and($profile->jurisdiction_id)->toBeInt()
        ->and($profile->name)->toBeString()
        ->and($profile->tax_id)->toBeString()
        ->and($profile->default_currency)->toBeString();
});

test('tax_id is encrypted', function () {
    $profile = UserProfile::factory()->create([
        'tax_id' => '12345678A',
    ]);

    // Value in memory should be decrypted
    expect($profile->tax_id)->toBe('12345678A');

    // Value in database should be encrypted (different from plaintext)
    $rawValue = $profile->getAttributes()['tax_id'];
    expect($rawValue)->not->toBe('12345678A');
});

test('user profile belongs to user', function () {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create(['user_id' => $user->id]);

    expect($profile->user)->toBeInstanceOf(User::class)
        ->and($profile->user->id)->toBe($user->id);
});

test('user profile belongs to jurisdiction', function () {
    $jurisdiction = Jurisdiction::factory()->create();
    $profile = UserProfile::factory()->create(['jurisdiction_id' => $jurisdiction->id]);

    expect($profile->jurisdiction)->toBeInstanceOf(Jurisdiction::class)
        ->and($profile->jurisdiction->id)->toBe($jurisdiction->id);
});

test('display_currencies is cast to json', function () {
    $currencies = ['USD', 'EUR', 'GBP'];
    $profile = UserProfile::factory()->create([
        'display_currencies' => $currencies,
    ]);

    expect($profile->display_currencies)->toBeArray()
        ->and($profile->display_currencies)->toBe($currencies);
});

test('user can have multiple profiles for different jurisdictions', function () {
    $user = User::factory()->create();
    $spain = Jurisdiction::factory()->spain()->create();
    $usa = Jurisdiction::factory()->usa()->create();

    $spainProfile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $spain->id,
        'name' => 'Juan Carlos Correa',
        'tax_id' => '12345678A',
    ]);

    $usaProfile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'jurisdiction_id' => $usa->id,
        'name' => 'John Correa',
        'tax_id' => '123-45-6789',
    ]);

    expect($spainProfile->user_id)->toBe($user->id)
        ->and($usaProfile->user_id)->toBe($user->id)
        ->and($spainProfile->name)->toBe('Juan Carlos Correa')
        ->and($usaProfile->name)->toBe('John Correa')
        ->and($spainProfile->tax_id)->toBe('12345678A')
        ->and($usaProfile->tax_id)->toBe('123-45-6789');
});

test('user profile factory has jurisdiction state methods', function () {
    $spainProfile = UserProfile::factory()->spain()->create();
    $usaProfile = UserProfile::factory()->usa()->create();
    $colombiaProfile = UserProfile::factory()->colombia()->create();

    expect($spainProfile->jurisdiction->iso_code)->toBe('ESP')
        ->and($usaProfile->jurisdiction->iso_code)->toBe('USA')
        ->and($colombiaProfile->jurisdiction->iso_code)->toBe('COL');
});
