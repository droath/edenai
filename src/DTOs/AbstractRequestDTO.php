<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs;

use InvalidArgumentException;

/**
 * Abstract base class for request Data Transfer Objects.
 *
 * Request DTOs encapsulate data sent to the API and enforce strict validation
 * at construction time. They follow the "fail fast" principle to catch errors
 * before making API calls.
 *
 * All concrete implementations must:
 * - Use readonly properties for immutability
 * - Validate all data in the constructor
 * - Throw InvalidArgumentException for invalid input
 * - Implement toArray() for serialization to HTTP request body
 *
 * Example validation patterns:
 *
 * ```php
 * public function __construct(
 *     public readonly string $name,
 *     public readonly int $age,
 * ) {
 *     // Non-empty string validation
 *     if ($name === '') {
 *         throw new InvalidArgumentException('Name cannot be empty');
 *     }
 *
 *     // Positive integer validation
 *     if ($age <= 0) {
 *         throw new InvalidArgumentException('Age must be positive');
 *     }
 * }
 * ```
 *
 * @package Droath\Edenai\DTOs
 */
abstract class AbstractRequestDTO
{
    /**
     * Convert the DTO to an array for serialization to HTTP request body.
     *
     * This method should return an associative array suitable for JSON encoding
     * and sending as the request payload to the API.
     *
     * @return array<string, mixed> The serialized DTO data
     */
    abstract public function toArray(): array;
}
