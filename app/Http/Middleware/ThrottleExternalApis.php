<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limiting middleware for external API integrations.
 *
 * Prevents overwhelming external services by enforcing per-service rate limits.
 */
class ThrottleExternalApis
{
    /**
     * Rate limits per service (requests per minute).
     */
    protected const RATE_LIMITS = [
        'mercury' => 60,
        'ecb' => 30,
        'plaid' => 100,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $service = $request->query('service');

        if ($service && isset(self::RATE_LIMITS[$service])) {
            $limit = self::RATE_LIMITS[$service];
            $userId = $request->user()?->id ?? 'guest';
            $key = "external_api:{$service}:{$userId}";

            if (! RateLimiter::attempt($key, $limit, fn () => true, 60)) {
                return response()->json([
                    'message' => "Rate limit exceeded for {$service}. Maximum {$limit} requests per minute.",
                    'retry_after' => RateLimiter::availableAt($key),
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }
        }

        return $next($request);
    }
}
