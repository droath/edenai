<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs;

/**
 * Abstract base class for response Data Transfer Objects.
 *
 * Response DTOs encapsulate data received from the API and provide type-safe
 * access to response fields. They parse API responses leniently and transform
 * data types as needed.
 *
 * All concrete implementations must:
 * - Use readonly properties for immutability
 * - Implement static factory method fromResponse(array $data): static
 * - Parse leniently: ignore unknown properties from API
 * - Transform types as needed (string dates to DateTimeImmutable, string enums to enum cases)
 * - Handle missing optional fields with null defaults
 * - Focus on business data only (HTTP metadata handled separately via ResponseMetadata)
 *
 * Example type transformation patterns:
 *
 * ```php
 * public static function fromResponse(array $data): static
 * {
 *     return new static(
 *         id: $data['id'],
 *         count: $data['count'],
 *         // Transform string date to DateTimeImmutable
 *         createdAt: new DateTimeImmutable($data['created_at']),
 *         // Optional field with null default
 *         description: $data['description'] ?? null,
 *         // Transform string to enum
 *         status: StatusEnum::from($data['status']),
 *     );
 *     // Unknown keys in $data are ignored (lenient parsing)
 * }
 * ```
 *
 * @package Droath\Edenai\DTOs
 */
abstract class AbstractResponseDTO
{
    /**
     * Create a response DTO instance from API response data.
     *
     * This factory method constructs the DTO from the decoded JSON response array.
     * Implementations should transform types as needed (e.g., string dates to
     * DateTimeImmutable) and ignore any unknown keys in the input data.
     *
     * @param array<string, mixed> $data The decoded API response data
     *
     * @return static The constructed DTO instance
     */
    abstract public static function fromResponse(array $data): static;
}
