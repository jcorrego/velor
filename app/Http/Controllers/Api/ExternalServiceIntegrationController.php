<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Finance\MercuryApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API controller for managing external service integrations.
 *
 * Handles integration setup, verification, and data synchronization with
 * external services like Mercury Bank, ECB, and future integrations.
 */
class ExternalServiceIntegrationController extends Controller
{
    /**
     * Verify Mercury API connection.
     *
     * @throws \Exception
     */
    public function verifyMercury(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'api_token' => 'required|string|min:10',
        ]);

        try {
            $client = new MercuryApiClient($validated['api_token']);

            if (! $client->verifyAuthentication()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to authenticate with Mercury API. Please check your API token.',
                ], 401);
            }

            // Get rate limit status
            $status = $client->getRateLimitStatus();

            return response()->json([
                'success' => true,
                'message' => 'Successfully authenticated with Mercury API',
                'rate_limit' => $status,
            ], 200);
        } catch (\Exception $e) {
            Log::error('ExternalServiceIntegrationController: Mercury verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while verifying Mercury API connection',
            ], 500);
        }
    }

    /**
     * Get Mercury API rate limit status.
     */
    public function getMercuryRateLimit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'api_token' => 'required|string|min:10',
        ]);

        try {
            $client = new MercuryApiClient($validated['api_token']);
            $status = $client->getRateLimitStatus();

            return response()->json([
                'success' => true,
                'rate_limit' => $status,
            ], 200);
        } catch (\Exception $e) {
            Log::error('ExternalServiceIntegrationController: Getting Mercury rate limit failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get rate limit status',
            ], 500);
        }
    }

    /**
     * Webhook handler for external service notifications.
     *
     * Receives webhooks from services like Mercury for real-time transaction updates.
     */
    public function handleWebhook(Request $request, string $service): JsonResponse
    {
        try {
            $signature = $request->header('X-Signature');

            // Verify webhook signature based on service
            if (! $this->verifyWebhookSignature($service, $request->getContent(), $signature)) {
                Log::warning('ExternalServiceIntegrationController: Invalid webhook signature', [
                    'service' => $service,
                ]);

                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Process webhook based on service type
            match ($service) {
                'mercury' => $this->processMercuryWebhook($request),
                'ecb' => $this->processEcbWebhook($request),
                default => Log::warning('ExternalServiceIntegrationController: Unknown webhook service', [
                    'service' => $service,
                ]),
            };

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('ExternalServiceIntegrationController: Webhook processing failed', [
                'service' => $service,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Verify webhook signature.
     */
    protected function verifyWebhookSignature(string $service, string $payload, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }

        $secret = match ($service) {
            'mercury' => config('services.mercury.webhook_secret'),
            'ecb' => config('services.ecb.webhook_secret'),
            default => null,
        };

        if (! $secret) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Process Mercury webhook (transaction created, account updated, etc.).
     */
    protected function processMercuryWebhook(Request $request): void
    {
        $event = $request->input('event');

        match ($event) {
            'transaction.created' => $this->handleMercuryTransactionCreated($request),
            'transaction.updated' => $this->handleMercuryTransactionUpdated($request),
            'account.updated' => $this->handleMercuryAccountUpdated($request),
            default => Log::info('ExternalServiceIntegrationController: Unknown Mercury event', [
                'event' => $event,
            ]),
        };
    }

    /**
     * Process ECB webhook (new rates published, etc.).
     */
    protected function processEcbWebhook(Request $request): void
    {
        $event = $request->input('event');

        match ($event) {
            'rates.published' => Log::info('ExternalServiceIntegrationController: ECB rates published'),
            default => Log::info('ExternalServiceIntegrationController: Unknown ECB event', [
                'event' => $event,
            ]),
        };
    }

    /**
     * Handle Mercury transaction.created webhook.
     */
    private function handleMercuryTransactionCreated(Request $request): void
    {
        Log::info('ExternalServiceIntegrationController: Mercury transaction created', [
            'transaction_id' => $request->input('data.id'),
        ]);

        // Future: Queue job to import transaction
    }

    /**
     * Handle Mercury transaction.updated webhook.
     */
    private function handleMercuryTransactionUpdated(Request $request): void
    {
        Log::info('ExternalServiceIntegrationController: Mercury transaction updated', [
            'transaction_id' => $request->input('data.id'),
        ]);

        // Future: Queue job to update transaction
    }

    /**
     * Handle Mercury account.updated webhook.
     */
    private function handleMercuryAccountUpdated(Request $request): void
    {
        Log::info('ExternalServiceIntegrationController: Mercury account updated', [
            'account_id' => $request->input('data.id'),
        ]);

        // Future: Queue job to update account
    }
}
