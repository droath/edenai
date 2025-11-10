<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs;

use InvalidArgumentException;

/**
 * Example request DTO demonstrating validation and serialization patterns.
 *
 * This class serves as a reference implementation for creating request DTOs.
 * It demonstrates:
 * - Constructor validation with descriptive error messages
 * - Multiple property types (string, int, array)
 * - Readonly properties for immutability
 * - toArray() serialization
 *
 * Usage example:
 * ```php
 * $request = new ExampleRequestDTO(
 *     name: 'John Doe',
 *     age: 30,
 *     status: 'active',
 *     tags: ['php', 'api']
 * );
 *
 * $payload = $request->toArray();
 * // ['name' => 'John Doe', 'age' => 30, 'status' => 'active', 'tags' => ['php', 'api']]
 * ```
 *
 * @package Droath\Edenai\DTOs
 */
final class ExampleRequestDTO extends AbstractRequestDTO
{
    /**
     * Create a new example request DTO.
     *
     * @param string $name The name (non-empty string)
     * @param int $age The age (positive integer)
     * @param string $status The status (must be 'active', 'inactive', or 'pending')
     * @param array<int, mixed> $tags Array of tags (will be validated as strings)
     *
     * @throws InvalidArgumentException If validation fails
     */
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly string $status,
        public readonly array $tags,
    ) {
        // Validate non-empty string
        if ($this->name === '') {
            throw new InvalidArgumentException('Name cannot be empty');
        }

        // Validate positive integer
        if ($this->age <= 0) {
            throw new InvalidArgumentException('Age must be positive');
        }

        // Validate enum-like values
        if (!in_array($this->status, ['active', 'inactive', 'pending'], true)) {
            throw new InvalidArgumentException('Invalid status. Must be active, inactive, or pending');
        }

        // Validate array is not empty
        if ($this->tags === []) {
            throw new InvalidArgumentException('Tags cannot be empty');
        }

        // Validate all tags are strings
        foreach ($this->tags as $tag) {
            if (!is_string($tag)) {
                throw new InvalidArgumentException('All tags must be strings');
            }
        }
    }

    /**
     * Convert the DTO to an array for HTTP request serialization.
     *
     * @return array<string, mixed> The serialized request data
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'age' => $this->age,
            'status' => $this->status,
            'tags' => $this->tags,
        ];
    }
}
