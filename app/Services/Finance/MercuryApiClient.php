<?php

namespace App\Services\Finance;

use App\Services\ExternalServiceClient;

/**
 * Mercury Bank API Client.
 *
 * Provides integration with Mercury Bank API for reading accounts and transactions.
 */
class MercuryApiClient extends ExternalServiceClient
{
    protected string $apiToken;

    public function __construct(string $apiToken)
    {
        $this->apiToken = $apiToken;
    }

    public function getBaseUrl(): string
    {
        return 'https://api.mercury.com';
    }

    public function getRateLimitKey(): string
    {
        return 'mercury_api';
    }

    public function getMaxRequestsPerMinute(): int
    {
        // Mercury API allows 60 requests per minute
        return 60;
    }

    /**
     * Get all accounts for the authenticated user.
     *
     * @return array<array<string, mixed>>|null Array of account objects or null on failure
     */
    public function getAccounts(): ?array
    {
        $response = $this->get('accounts');

        if (! $response) {
            return null;
        }

        return $response['data'] ?? null;
    }

    /**
     * Get a specific account by ID.
     *
     * @return array<string, mixed>|null Account object or null on failure
     */
    public function getAccount(string $accountId): ?array
    {
        $response = $this->get("accounts/{$accountId}");

        if (! $response) {
            return null;
        }

        return $response['data'] ?? null;
    }

    /**
     * Get transactions for an account.
     *
     * @param  string  $accountId  The account ID
     * @param  array<string, mixed>  $filters  Optional filters (limit, offset, start_date, end_date)
     * @return array<array<string, mixed>>|null Array of transaction objects or null on failure
     */
    public function getTransactions(string $accountId, array $filters = []): ?array
    {
        $params = array_merge(['limit' => 100], $filters);
        $response = $this->get("accounts/{$accountId}/transactions", $params);

        if (! $response) {
            return null;
        }

        return $response['data'] ?? null;
    }

    /**
     * Get a specific transaction by ID.
     *
     * @return array<string, mixed>|null Transaction object or null on failure
     */
    public function getTransaction(string $accountId, string $transactionId): ?array
    {
        $response = $this->get("accounts/{$accountId}/transactions/{$transactionId}");

        if (! $response) {
            return null;
        }

        return $response['data'] ?? null;
    }

    /**
     * Verify API authentication by checking if we can fetch accounts.
     */
    public function verifyAuthentication(): bool
    {
        $accounts = $this->getAccounts();

        return $accounts !== null;
    }

    /**
     * Make HTTP request with Mercury API token authentication.
     *
     * @param  array<string, mixed>  $params  Query parameters
     * @return array<string, mixed>|null Response data
     */
    protected function get(string $endpoint, array $params = []): ?array
    {
        $params['token'] = $this->apiToken;

        return parent::get($endpoint, $params);
    }

    /**
     * Make POST request with Mercury API token authentication.
     *
     * @param  array<string, mixed>  $data  Request body
     * @param  array<string, string>  $headers  Optional headers
     * @return array<string, mixed>|null Response data
     */
    protected function post(string $endpoint, array $data = [], array $headers = []): ?array
    {
        $data['token'] = $this->apiToken;

        return parent::post($endpoint, $data, $headers);
    }
}
