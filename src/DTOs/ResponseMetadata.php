<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs;

use Psr\Http\Message\ResponseInterface;

/**
 * Response metadata object containing HTTP headers and rate limit information.
 *
 * This class separates HTTP metadata from business data, keeping response DTOs
 * focused on business logic only. It extracts common metadata from PSR-7 responses:
 * - All HTTP headers
 * - Rate limit remaining count
 * - Rate limit reset timestamp
 * - Request ID for tracing
 *
 * Metadata Extraction Strategy:
 * - Headers extracted as-is from PSR-7 Response
 * - Rate limit headers: X-RateLimit-Remaining, X-RateLimit-Reset
 * - Request ID from X-Request-ID header
 * - All metadata fields are nullable (not all APIs provide these headers)
 *
 * Usage example:
 * ```php
 * $response = $httpClient->sendRequest($request);
 * $metadata = new ResponseMetadata($response);
 *
 * if ($metadata->getRateLimitRemaining() < 10) {
 *     // Handle low rate limit
 * }
 * ```
 */
final class ResponseMetadata
{
    /**
     * Create a new response metadata object.
     *
     * @param array<string, array<int, string>> $headers All HTTP headers from response
     * @param int|null $rateLimitRemaining Number of remaining requests in current window
     * @param int|null $rateLimitReset Unix timestamp when rate limit resets
     * @param string|null $requestId Unique request identifier for tracing
     */
    public function __construct(
        public readonly array $headers,
        public readonly ?int $rateLimitRemaining = null,
        public readonly ?int $rateLimitReset = null,
        public readonly ?string $requestId = null,
    ) {
    }

    /**
     * Create metadata from a PSR-7 Response object.
     *
     * Extracts all headers and parses rate limit information.
     *
     * @param ResponseInterface $response The PSR-7 response
     *
     * @return self The constructed metadata object
     */
    public static function fromResponse(ResponseInterface $response): self
    {
        $headers = $response->getHeaders();

        return new self(
            headers: $headers,
            rateLimitRemaining: self::extractIntHeader($headers, 'X-RateLimit-Remaining'),
            rateLimitReset: self::extractIntHeader($headers, 'X-RateLimit-Reset'),
            requestId: self::extractStringHeader($headers, 'X-Request-ID'),
        );
    }

    /**
     * Get all HTTP headers.
     *
     * @return array<string, array<int, string>> The headers array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get the number of remaining requests in the current rate limit window.
     *
     * @return int|null The remaining request count, or null if not provided
     */
    public function getRateLimitRemaining(): ?int
    {
        return $this->rateLimitRemaining;
    }

    /**
     * Get the Unix timestamp when the rate limit resets.
     *
     * @return int|null The reset timestamp, or null if not provided
     */
    public function getRateLimitReset(): ?int
    {
        return $this->rateLimitReset;
    }

    /**
     * Get the unique request identifier for tracing.
     *
     * @return string|null The request ID, or null if not provided
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Extract an integer value from headers.
     *
     * @param array<string, array<int, string>> $headers The headers array
     * @param string $name The header name
     *
     * @return int|null The integer value, or null if not present
     */
    private static function extractIntHeader(array $headers, string $name): ?int
    {
        if (! isset($headers[$name][0])) {
            return null;
        }

        $value = (int) $headers[$name][0];

        return $value !== 0 ? $value : null;
    }

    /**
     * Extract a string value from headers.
     *
     * @param array<string, array<int, string>> $headers The headers array
     * @param string $name The header name
     *
     * @return string|null The string value, or null if not present
     */
    private static function extractStringHeader(array $headers, string $name): ?string
    {
        return $headers[$name][0] ?? null;
    }
}
