<?php

declare(strict_types=1);

namespace Droath\Edenai\Exceptions;

use Exception;

/**
 * Exception thrown when request validation fails (HTTP 422).
 *
 * This indicates the request payload contains invalid or missing data.
 * The errors property contains field-level validation messages to help
 * the client correct the input.
 */
final class ValidationException extends ApiException
{
    /**
     * Create a new validation exception.
     *
     * @param string $message Error message describing the validation failure
     * @param array<string, array<int, string>> $errors Field-level validation errors (field => [messages])
     * @param array<string, mixed>|null $responseBody Parsed response body with full error details
     * @param Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message,
        protected readonly array $errors,
        ?array $responseBody = null,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, 422, $responseBody, $previous);
    }

    /**
     * Get the field-level validation errors.
     *
     * @return array<string, array<int, string>> Validation errors keyed by field name
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
