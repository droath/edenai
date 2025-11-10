<?php

declare(strict_types=1);

namespace Droath\Edenai\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when network-level errors occur.
 *
 * This exception is thrown for connection failures, DNS resolution errors,
 * timeouts, and other network issues that prevent the request from reaching
 * the server. Since no HTTP response is received, the status code is 0.
 *
 * Common scenarios:
 * - Connection refused or timeout
 * - DNS lookup failure
 * - SSL/TLS handshake failure
 * - Network unreachable
 */
final class NetworkException extends ApiException
{
    /**
     * Create a new network exception.
     *
     * @param string $message Error message describing the network failure
     * @param array<string, mixed>|null $responseBody Optional response body (usually null for network errors)
     * @param Throwable|null $previous Previous exception in the chain (typically a PSR-18 exception)
     */
    public function __construct(
        string $message = 'Network error occurred',
        ?array $responseBody = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $responseBody, $previous);
    }
}
