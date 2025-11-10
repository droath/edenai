<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs;

use DateTimeImmutable;

/**
 * Example response DTO demonstrating lenient parsing and type transformations.
 *
 * This class serves as a reference implementation for creating response DTOs.
 * It demonstrates:
 * - Static factory method fromResponse()
 * - Lenient parsing (ignores unknown keys)
 * - Type transformations (string to DateTimeImmutable)
 * - Optional fields with null defaults
 * - Readonly properties for immutability
 *
 * Usage example:
 * ```php
 * $data = [
 *     'id' => 'abc123',
 *     'count' => 42,
 *     'created_at' => '2024-01-15 10:30:00',
 *     'description' => 'Example response',
 *     'unknown_field' => 'ignored',
 * ];
 *
 * $response = ExampleResponseDTO::fromResponse($data);
 * // Unknown fields are ignored, dates are transformed
 * ```
 *
 * @package Droath\Edenai\DTOs
 */
final class ExampleResponseDTO extends AbstractResponseDTO
{
    /**
     * Create a new example response DTO.
     *
     * @param string $id The unique identifier
     * @param int $count The count value
     * @param DateTimeImmutable $createdAt The creation timestamp
     * @param string|null $description Optional description (nullable)
     */
    public function __construct(
        public readonly string $id,
        public readonly int $count,
        public readonly DateTimeImmutable $createdAt,
        public readonly ?string $description = null,
    ) {
    }

    /**
     * Create a response DTO from API response data.
     *
     * This method demonstrates lenient parsing by ignoring unknown keys
     * and transforming string dates to DateTimeImmutable instances.
     *
     * @param array<string, mixed> $data The API response data
     *
     * @return static The constructed response DTO
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            id: (string) $data['id'],
            count: (int) $data['count'],
            createdAt: new DateTimeImmutable((string) $data['created_at']),
            description: isset($data['description']) ? (string) $data['description'] : null,
        );
        // Note: Unknown keys in $data are automatically ignored (lenient parsing)
    }
}
