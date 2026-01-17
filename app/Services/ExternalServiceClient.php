<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Base client for external service integrations.
 *
 * Provides common functionality for API interactions with rate limiting,
 * error handling, and logging.
 */
abstract class ExternalServiceClient
{
    /**
     * Base URL for the service API.
     */
    abstract public function getBaseUrl(): string;

    /**
     * Rate limit key for this service (used by RateLimiter).
     */
    abstract public function getRateLimitKey(): string;

    /**
     * Maximum number of requests allowed per minute.
     */
    abstract public function getMaxRequestsPerMinute(): int;

    /**
     * Timeout for HTTP requests (in seconds).
     */
    public function getTimeout(): int
    {
        return 30;
    }

    /**
     * Check if we can make a request without hitting rate limit.
     */
    public function canMakeRequest(): bool
    {
        return RateLimiter::attempt(
            $this->getRateLimitKey(),
            $this->getMaxRequestsPerMinute(),
            fn () => true,
            60,
        );
    }

    /**
     * Get the current rate limit status.
     *
     * @return array<string, int> Array with 'remaining' and 'reset_at' keys
     */
    public function getRateLimitStatus(): array
    {
        $key = $this->getRateLimitKey();
        $limit = $this->getMaxRequestsPerMinute();

        // This is an approximation; Laravel's rate limiter doesn't expose full status
        return [
            'remaining' => max(0, $limit - RateLimiter::attempts($key)),
            'limit' => $limit,
        ];
    }

    /**
     * Make a GET request to the service API.
     *
     * @param  array<string, mixed>  $params  Optional query parameters
     * @return array<string, mixed>|null Response data or null on failure
     */
    protected function get(string $endpoint, array $params = []): ?array
    {
        if (! $this->canMakeRequest()) {
            Log::warning('ExternalServiceClient: Rate limit exceeded for '.$this->getRateLimitKey());

            return null;
        }

        try {
            $response = Http::timeout($this->getTimeout())
                ->get($this->getBaseUrl().'/'.$endpoint, $params);

            if (! $response->successful()) {
                Log::error('ExternalServiceClient: API request failed', [
                    'service' => $this->getRateLimitKey(),
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('ExternalServiceClient: API request exception', [
                'service' => $this->getRateLimitKey(),
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Make a POST request to the service API.
     *
     * @param  array<string, mixed>  $data  Request body data
     * @param  array<string, string>  $headers  Optional headers
     * @return array<string, mixed>|null Response data or null on failure
     */
    protected function post(string $endpoint, array $data = [], array $headers = []): ?array
    {
        if (! $this->canMakeRequest()) {
            Log::warning('ExternalServiceClient: Rate limit exceeded for '.$this->getRateLimitKey());

            return null;
        }

        try {
            $response = Http::timeout($this->getTimeout())
                ->withHeaders($headers)
                ->post($this->getBaseUrl().'/'.$endpoint, $data);

            if (! $response->successful()) {
                Log::error('ExternalServiceClient: API request failed', [
                    'service' => $this->getRateLimitKey(),
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('ExternalServiceClient: API request exception', [
                'service' => $this->getRateLimitKey(),
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
