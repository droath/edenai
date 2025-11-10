<?php

declare(strict_types=1);

namespace Droath\Edenai\Middleware;

use JsonException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Droath\Edenai\Exceptions\ServerException;
use Psr\Http\Client\ClientExceptionInterface;
use Droath\Edenai\Exceptions\NetworkException;
use Droath\Edenai\Exceptions\RateLimitException;
use Droath\Edenai\Exceptions\ValidationException;
use Droath\Edenai\Exceptions\AuthorizationException;
use Droath\Edenai\Exceptions\AuthenticationException;
use Droath\Edenai\Exceptions\ResourceNotFoundException;

/**
 * Maps HTTP status codes and network errors to domain-specific exceptions.
 *
 * This middleware intercepts HTTP responses and throws appropriate exceptions
 * based on status codes, allowing application code to use typed exception
 * handling instead of checking status codes. It also catches PSR-18 client
 * exceptions (network errors, DNS failures, timeouts) and wraps them in
 * NetworkException for consistent error handling.
 *
 * Status code mappings:
 * - 401 → AuthenticationException (invalid credentials)
 * - 403 → AuthorizationException (insufficient permissions)
 * - 404 → ResourceNotFoundException (resource not found)
 * - 422 → ValidationException (validation errors)
 * - 429 → RateLimitException (rate limit exceeded)
 * - 500+ → ServerException (server errors)
 *
 * Position: This middleware should be second in the pipeline, after authentication
 * but before retry logic, to ensure errors are properly categorized before retry
 * decisions are made.
 *
 * Example usage:
 * ```php
 * try {
 *     $response = $middleware->handle($request, $next);
 * } catch (AuthenticationException $e) {
 *     // Handle invalid API key
 * } catch (RateLimitException $e) {
 *     // Handle rate limiting
 * }
 * ```
 */
final class ErrorHandlingMiddleware implements MiddlewareInterface
{
    /**
     * Process the request and map error responses to exceptions.
     *
     * Successful responses (2xx) pass through unchanged. Error responses (4xx, 5xx)
     * are mapped to specific exception types with error details extracted from the
     * response body when available. PSR-18 client exceptions are caught and wrapped
     * in NetworkException.
     *
     * @param RequestInterface $request The PSR-7 HTTP request
     * @param callable $next The next middleware in the chain
     *
     * @return ResponseInterface The PSR-7 HTTP response
     *
     * @throws AuthenticationException For 401 status codes
     * @throws AuthorizationException For 403 status codes
     * @throws ResourceNotFoundException For 404 status codes
     * @throws ValidationException For 422 status codes
     * @throws RateLimitException For 429 status codes
     * @throws ServerException For 500+ status codes
     * @throws NetworkException For network/connection failures
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        try {
            $response = $next($request);
        } catch (ClientExceptionInterface $e) {
            throw new NetworkException(
                $e->getMessage(),
                null,
                $e,
            );
        }

        $statusCode = $response->getStatusCode();

        // Pass successful responses through unchanged
        if ($statusCode >= 200 && $statusCode < 300) {
            return $response;
        }

        // Extract error details from response body
        $responseBody = $this->parseResponseBody($response);

        // Map status codes to specific exceptions
        match (true) {
            $statusCode === 401 => throw new AuthenticationException(
                $responseBody['message'] ?? 'Invalid or missing API credentials',
                $responseBody,
            ),
            $statusCode === 403 => throw new AuthorizationException(
                $responseBody['message'] ?? 'Insufficient permissions',
                $responseBody,
            ),
            $statusCode === 404 => throw new ResourceNotFoundException(
                $responseBody['message'] ?? 'Resource not found',
                $responseBody,
            ),
            $statusCode === 422 => throw new ValidationException(
                $responseBody['message'] ?? 'Validation failed',
                $responseBody['errors'] ?? [],
                $responseBody,
            ),
            $statusCode === 429 => throw new RateLimitException(
                $responseBody['message'] ?? 'Rate limit exceeded',
                $responseBody['retry_after'] ?? null,
                $responseBody,
            ),
            $statusCode >= 500 => throw new ServerException(
                $responseBody['message'] ?? 'Server error occurred',
                $statusCode,
                $responseBody,
            ),
            default => throw new ServerException(
                $responseBody['message'] ?? "HTTP error {$statusCode}",
                $statusCode,
                $responseBody,
            ),
        };
    }

    /**
     * Parse the response body JSON into an array.
     *
     * Handles missing or malformed JSON gracefully by returning an empty array.
     *
     * @param ResponseInterface $response The PSR-7 HTTP response
     *
     * @return array<string, mixed> Parsed response body or empty array
     */
    private function parseResponseBody(ResponseInterface $response): array
    {
        $body = $response->getBody()->getContents();

        if ($body === '') {
            return [];
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (JsonException) {
            return [];
        }
    }
}
