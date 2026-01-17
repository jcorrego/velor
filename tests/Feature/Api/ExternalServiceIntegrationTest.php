<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

it('verifies Mercury API connection with valid token', function () {
    Sanctum::actingAs(User::factory()->create());

    Http::fake([
        'https://api.mercury.com/accounts*' => Http::response([
            'data' => [
                ['id' => '1', 'name' => 'Main Account', 'balance' => 10000],
            ],
        ]),
    ]);

    $response = $this->postJson('/api/integrations/mercury/verify', [
        'api_token' => 'valid_mercury_token_12345',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Successfully authenticated with Mercury API');
});

it('rejects Mercury API connection with invalid token', function () {
    Sanctum::actingAs(User::factory()->create());

    Http::fake([
        'https://api.mercury.com/accounts*' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    $response = $this->postJson('/api/integrations/mercury/verify', [
        'api_token' => 'invalid_token',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('success', false);
});

it('returns Mercury API rate limit status', function () {
    Sanctum::actingAs(User::factory()->create());

    Http::fake([
        'https://api.mercury.com/accounts*' => Http::response([
            'data' => [],
        ]),
    ]);

    $response = $this->getJson('/api/integrations/mercury/rate-limit?api_token=valid_mercury_token_12345');

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'rate_limit']);
});

it('handles Mercury webhook with valid signature', function () {
    $payload = json_encode(['event' => 'transaction.created', 'data' => ['id' => '123']]);
    $signature = hash_hmac('sha256', $payload, 'test_secret');

    config()->set('services.mercury.webhook_secret', 'test_secret');

    $response = $this->postJson('/api/webhooks/mercury', json_decode($payload, true), [
        'X-Signature' => $signature,
    ]);

    $response->assertSuccessful();
});

it('rejects Mercury webhook with invalid signature', function () {
    $payload = json_encode(['event' => 'transaction.created']);
    $invalidSignature = 'invalid_signature_value';

    config()->set('services.mercury.webhook_secret', 'test_secret');

    $response = $this->postJson('/api/webhooks/mercury', json_decode($payload, true), [
        'X-Signature' => $invalidSignature,
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('error', 'Invalid signature');
});

it('requires authentication for Mercury verification endpoint', function () {
    $response = $this->postJson('/api/integrations/mercury/verify', [
        'api_token' => 'some_token',
    ]);

    $response->assertUnauthorized();
});

it('validates API token field', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/integrations/mercury/verify', [
        'api_token' => 'short',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('api_token');
});
