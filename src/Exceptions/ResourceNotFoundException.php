<?php

declare(strict_types=1);

namespace Droath\Edenai\Exceptions;

use Exception;

/**
 * Exception thrown when a requested resource is not found (HTTP 404).
 *
 * This indicates the resource at the specified endpoint does not exist.
 * The client should verify the resource ID or path is correct.
 */
final class ResourceNotFoundException extends ApiException
{
    /**
     * Create a new resource not found exception.
     *
     * @param string $message Error message describing the missing resource
     * @param array<string, mixed>|null $responseBody Parsed response body with error details
     * @param Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'Resource not found',
        ?array $responseBody = null,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, 404, $responseBody, $previous);
    }
}
