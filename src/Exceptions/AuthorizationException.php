<?php

declare(strict_types=1);

namespace Droath\Edenai\Exceptions;

use Exception;

/**
 * Exception thrown when authorization fails (HTTP 403).
 *
 * This indicates the authenticated user does not have permission to access
 * the requested resource. Authentication was successful, but the user lacks
 * the required permissions or roles.
 */
final class AuthorizationException extends ApiException
{
    /**
     * Create a new authorization exception.
     *
     * @param string $message Error message describing the authorization failure
     * @param array<string, mixed>|null $responseBody Parsed response body with error details
     * @param Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'Insufficient permissions',
        ?array $responseBody = null,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, 403, $responseBody, $previous);
    }
}
