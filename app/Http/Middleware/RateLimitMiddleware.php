<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{

    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     * @return void
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|null  $maxRequests
     * @param  int|null  $decayMinutes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?int $maxRequests = null, ?int $decayMinutes = null)
    {
        // Get configuration values, with fallbacks
        $maxRequests = $maxRequests ?? Config::get('rate_limit.max_requests', 60);
        $decayMinutes = $decayMinutes ?? Config::get('rate_limit.decay_minutes', 1);

        // Generate a unique key based on IP and optional user ID
        $key = $this->resolveRequestSignature($request);

        // Check if the request exceeds the rate limit
        if ($this->limiter->tooManyAttempts($key, $maxRequests)) {
            $retryAfter = $this->limiter->availableIn($key);
            
            return $this->buildTooManyAttemptsResponse($retryAfter);
        }

        // Increment the counter
        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        // Add rate limit headers to the response
        return $this->addHeaders(
            $response,
            $maxRequests,
            $this->calculateRemainingAttempts($key, $maxRequests)
        );
    }

    /**
     * Resolve request signature for rate limiting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // If user is authenticated, include their ID in the key
        $userId = $request->user()?->id;
        
        return sha1(implode('|', [
            $request->ip(),
            $userId ?? 'guest',
            $request->route()?->getName() ?? $request->path(),
            $request->fingerprint()
        ]));
    }

     /**
     * Create the error response when too many attempts have been made.
     *
     * @param  int  $retryAfter
     * @return \Illuminate\Http\JsonResponse
     */
    protected function buildTooManyAttemptsResponse(int $retryAfter)
    {
        $retryAfterMinutes = ceil($retryAfter / 60);

        return response()->json([
            'status' => 429,
            'success' => false,
            'message' => "Too many requests. Please try again in {$retryAfterMinutes} " . 
                        ($retryAfterMinutes === 1 ? 'minute' : 'minutes'),
            'data' => [
                'retry_after_seconds' => $retryAfter,
                'retry_after_minutes' => $retryAfterMinutes,
            ]
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Reset' => time() + $retryAfter,
        ]);
    }

    /**
     * Add rate limit headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $headers = [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ];
        
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    /**
     * Calculate the number of remaining attempts.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return int
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $maxAttempts - $this->limiter->attempts($key);
    }
}