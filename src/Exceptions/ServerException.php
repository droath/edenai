<?php

declare(strict_types=1);

namespace Droath\Edenai\Exceptions;

use Exception;

/**
 * Exception thrown when a server error occurs (HTTP 500+).
 *
 * This indicates an error on the API server side (5xx status codes).
 * The client typically cannot resolve these errors and should retry
 * with exponential backoff or contact support.
 */
final class ServerException extends ApiException
{
    /**
     * Create a new server exception.
     *
     * @param string $message Error message describing the server error
     * @param int $statusCode HTTP status code in the 5xx range (default 500)
     * @param array<string, mixed>|null $responseBody Parsed response body with error details
     * @param Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'Server error occurred',
        int $statusCode = 500,
        ?array $responseBody = null,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $statusCode, $responseBody, $previous);
    }
}
