<?php

declare(strict_types=1);

namespace Droath\Edenai\Exceptions;

use Exception;

/**
 * Exception thrown when authentication fails (HTTP 401).
 *
 * This typically indicates missing or invalid API credentials. The client should
 * verify the API key is correct and has not expired.
 */
final class AuthenticationException extends ApiException
{
    /**
     * Create a new authentication exception.
     *
     * @param string $message Error message describing the authentication failure
     * @param array<string, mixed>|null $responseBody Parsed response body with error details
     * @param Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'Invalid or missing API credentials',
        ?array $responseBody = null,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, 401, $responseBody, $previous);
    }
}
