<?php

declare(strict_types=1);

namespace Droath\Edenai\Exceptions;

use Exception;

/**
 * Exception thrown when rate limit is exceeded (HTTP 429).
 *
 * This indicates the client has made too many requests in a given time period.
 * The retryAfter property contains the timestamp when the client can retry,
 * if provided by the API.
 */
final class RateLimitException extends ApiException
{
    /**
     * Create a new rate limit exception.
     *
     * @param string $message Error message describing the rate limit
     * @param int|null $retryAfter Unix timestamp when the client can retry, or null if not provided
     * @param array<string, mixed>|null $responseBody Parsed response body with error details
     * @param Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message,
        protected readonly ?int $retryAfter,
        ?array $responseBody = null,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, 429, $responseBody, $previous);
    }

    /**
     * Get the retry-after timestamp.
     *
     * @return int|null Unix timestamp when requests can resume, or null if not specified
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
