<?php

declare(strict_types=1);

namespace Droath\Edenai\Middleware;

use RuntimeException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Retries failed requests with exponential backoff for transient errors.
 *
 * This middleware implements automatic retry logic for requests that fail due
 * to transient issues like rate limiting, server errors, or network problems.
 * It uses exponential backoff (1s, 2s, 4s, 8s...) to avoid overwhelming the
 * server with rapid retry attempts.
 *
 * Retry conditions:
 * - Status codes: 429 (rate limit), 500, 502, 503, 504 (server errors)
 * - Network exceptions: timeouts, connection failures, DNS errors
 *
 * Non-retryable conditions:
 * - 4xx client errors (except 429) - these indicate invalid requests
 * - 2xx success responses - no retry needed
 * - 3xx redirects - should be handled by HTTP client
 *
 * Position: This middleware should be third in the pipeline, after authentication
 * and error handling, to ensure proper retry behavior on categorized errors.
 *
 * Configuration:
 * ```php
 * $middleware = new RetryMiddleware([
 *     'max_attempts' => 3,      // Total attempts (default: 3)
 *     'backoff' => 'exponential' // Backoff strategy (default: exponential)
 * ]);
 * ```
 *
 * Example usage:
 * ```php
 * // Will retry up to 3 times with 1s, 2s, 4s delays
 * $response = $middleware->handle($request, $next);
 * ```
 */
final class RetryMiddleware implements MiddlewareInterface
{
    /**
     * HTTP status codes that should trigger a retry.
     */
    private const RETRYABLE_STATUS_CODES = [429, 500, 502, 503, 504];
    /**
     * Maximum number of attempts (including initial request).
     */
    private readonly int $maxAttempts;

    /**
     * Backoff strategy ('exponential' or 'linear').
     */
    private readonly string $backoff;

    /**
     * Whether to enable sleep delays between retries.
     */
    private readonly bool $enableSleep;

    /**
     * Create a new retry middleware instance.
     *
     * @param array<string, mixed> $config Configuration options:
     *                                     - max_attempts: Total attempts (default: 3)
     *                                     - backoff: Strategy 'exponential' or 'linear' (default: exponential)
     *                                     - enable_sleep: Whether to sleep between retries (default: true)
     */
    public function __construct(array $config = [])
    {
        $this->maxAttempts = $config['max_attempts'] ?? 3;
        $this->backoff = $config['backoff'] ?? 'exponential';
        $this->enableSleep = $config['enable_sleep'] ?? true;
    }

    /**
     * Process the request with automatic retry logic.
     *
     * Attempts the request up to max_attempts times, retrying on transient failures
     * with exponential backoff delays between attempts. Throws the final exception
     * if all retry attempts are exhausted.
     *
     * @param RequestInterface $request The PSR-7 HTTP request
     * @param callable $next The next middleware in the chain
     *
     * @return ResponseInterface The PSR-7 HTTP response
     *
     * @throws ClientExceptionInterface If all retry attempts fail with network error
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxAttempts) {
            $attempt++;

            try {
                $response = $next($request);

                // Check if response status is retryable
                if ($this->shouldRetryResponse($response)) {
                    if ($attempt < $this->maxAttempts) {
                        $this->sleep($attempt);
                        continue;
                    }
                }

                return $response;
            } catch (ClientExceptionInterface $e) {
                $lastException = $e;

                // Retry on network exceptions if attempts remaining
                if ($attempt < $this->maxAttempts) {
                    $this->sleep($attempt);
                    continue;
                }

                // No more attempts, re-throw the exception
                throw $e;
            }
        }

        // This should never be reached, but satisfies static analysis
        throw $lastException ?? new RuntimeException('Unexpected retry loop exit');
    }

    /**
     * Determine if a response status code warrants a retry.
     *
     * @param ResponseInterface $response The PSR-7 HTTP response
     *
     * @return bool True if the response should be retried
     */
    private function shouldRetryResponse(ResponseInterface $response): bool
    {
        return in_array($response->getStatusCode(), self::RETRYABLE_STATUS_CODES, true);
    }

    /**
     * Sleep for the calculated backoff delay.
     *
     * Exponential backoff: 2^(attempt-1) seconds (1s, 2s, 4s, 8s...)
     * This prevents overwhelming the server with rapid retries.
     *
     * @param int $attempt The current attempt number (1-indexed)
     */
    private function sleep(int $attempt): void
    {
        if (! $this->enableSleep) {
            return;
        }

        if ($this->backoff === 'exponential') {
            $delay = 2 ** ($attempt - 1);
        } else {
            $delay = $attempt;
        }

        sleep($delay);
    }
}
