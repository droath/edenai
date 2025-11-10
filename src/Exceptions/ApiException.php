<?php

declare(strict_types=1);

namespace Droath\Edenai\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for all HTTP-related API errors.
 *
 * This exception captures the HTTP status code and response body from failed API requests,
 * providing context for error handling and debugging. All domain-specific API exceptions
 * extend this base class.
 */
class ApiException extends Exception
{
    /**
     * Create a new API exception.
     *
     * @param string $message Human-readable error message
     * @param int $statusCode HTTP status code from the response
     * @param array<string, mixed>|null $responseBody Parsed response body containing error details
     * @param Throwable|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message,
        protected readonly int $statusCode,
        protected readonly ?array $responseBody = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the HTTP status code from the failed request.
     *
     * @return int HTTP status code (e.g., 404, 500)
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the parsed response body from the failed request.
     *
     * @return array<string, mixed>|null Response body as associative array, or null if unavailable
     */
    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
