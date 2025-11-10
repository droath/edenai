<?php

declare(strict_types=1);

use Droath\Edenai\DTOs\ResponseMetadata;
use Droath\Edenai\DTOs\ExampleRequestDTO;
use Droath\Edenai\DTOs\ExampleResponseDTO;

describe('Request DTOs', function () {
    test('request DTO validates required fields in constructor', function () {
        // Arrange & Act & Assert
        expect(fn () => new ExampleRequestDTO(
            name: '',
            age: 25,
            status: 'active',
            tags: ['test']
        ))->toThrow(InvalidArgumentException::class, 'Name cannot be empty');
    });

    test('request DTO validates positive integers', function () {
        // Arrange & Act & Assert
        expect(fn () => new ExampleRequestDTO(
            name: 'John',
            age: -5,
            status: 'active',
            tags: ['test']
        ))->toThrow(InvalidArgumentException::class, 'Age must be positive');
    });

    test('request DTO validates enum values', function () {
        // Arrange & Act & Assert
        expect(fn () => new ExampleRequestDTO(
            name: 'John',
            age: 25,
            status: 'invalid_status',
            tags: ['test']
        ))->toThrow(InvalidArgumentException::class, 'Invalid status');
    });

    test('request DTO serializes to array with toArray method', function () {
        // Arrange
        $dto = new ExampleRequestDTO(
            name: 'John Doe',
            age: 30,
            status: 'active',
            tags: ['php', 'testing']
        );

        // Act
        $result = $dto->toArray();

        // Assert
        expect($result)->toBeArray()
            ->toHaveKeys(['name', 'age', 'status', 'tags'])
            ->and($result['name'])->toBe('John Doe')
            ->and($result['age'])->toBe(30)
            ->and($result['status'])->toBe('active')
            ->and($result['tags'])->toBe(['php', 'testing']);
    });

    test('request DTO properties are readonly and immutable', function () {
        // Arrange
        $dto = new ExampleRequestDTO(
            name: 'John',
            age: 25,
            status: 'active',
            tags: ['test']
        );

        // Act & Assert
        expect(fn () => $dto->name = 'Jane')
            ->toThrow(Error::class);
    });
});

describe('Response DTOs', function () {
    test('response DTO constructs from array via factory method', function () {
        // Arrange
        $data = [
            'id' => 'abc123',
            'count' => 42,
            'created_at' => '2024-01-15 10:30:00',
            'description' => 'Test description',
        ];

        // Act
        $dto = ExampleResponseDTO::fromResponse($data);

        // Assert
        expect($dto)->toBeInstanceOf(ExampleResponseDTO::class)
            ->and($dto->id)->toBe('abc123')
            ->and($dto->count)->toBe(42)
            ->and($dto->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($dto->createdAt->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00')
            ->and($dto->description)->toBe('Test description');
    });

    test('response DTO parses leniently and ignores unknown keys', function () {
        // Arrange
        $data = [
            'id' => 'xyz789',
            'count' => 10,
            'created_at' => '2024-02-20 15:45:00',
            'description' => null,
            'unknown_field' => 'should be ignored',
            'another_unknown' => ['nested', 'data'],
        ];

        // Act
        $dto = ExampleResponseDTO::fromResponse($data);

        // Assert - no exception thrown, unknown fields ignored
        expect($dto)->toBeInstanceOf(ExampleResponseDTO::class)
            ->and($dto->id)->toBe('xyz789')
            ->and($dto->count)->toBe(10);
    });

    test('response DTO transforms string dates to DateTimeImmutable', function () {
        // Arrange
        $data = [
            'id' => 'date123',
            'count' => 5,
            'created_at' => '2024-11-09 12:00:00',
            'description' => 'Date transformation test',
        ];

        // Act
        $dto = ExampleResponseDTO::fromResponse($data);

        // Assert
        expect($dto->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($dto->createdAt->format('Y-m-d'))->toBe('2024-11-09');
    });

    test('response DTO handles missing optional fields with null defaults', function () {
        // Arrange - description is optional
        $data = [
            'id' => 'minimal123',
            'count' => 1,
            'created_at' => '2024-01-01 00:00:00',
        ];

        // Act
        $dto = ExampleResponseDTO::fromResponse($data);

        // Assert
        expect($dto->description)->toBeNull();
    });

    test('response DTO properties are readonly and immutable', function () {
        // Arrange
        $data = [
            'id' => 'immutable123',
            'count' => 99,
            'created_at' => '2024-01-01 00:00:00',
            'description' => 'Test',
        ];
        $dto = ExampleResponseDTO::fromResponse($data);

        // Act & Assert
        expect(fn () => $dto->id = 'changed')
            ->toThrow(Error::class);
    });
});

describe('ResponseMetadata', function () {
    test('metadata object extracts headers from PSR-7 response', function () {
        // Arrange
        $response = Mockery::mock(Psr\Http\Message\ResponseInterface::class);
        $response->shouldReceive('getHeaders')->andReturn([
            'Content-Type' => ['application/json'],
            'X-RateLimit-Remaining' => ['100'],
            'X-RateLimit-Reset' => ['1704067200'],
            'X-Request-ID' => ['req-abc123'],
        ]);

        // Act
        $metadata = ResponseMetadata::fromResponse($response);

        // Assert
        expect($metadata->getRateLimitRemaining())->toBe(100)
            ->and($metadata->getRateLimitReset())->toBe(1704067200)
            ->and($metadata->getRequestId())->toBe('req-abc123')
            ->and($metadata->getHeaders())->toBeArray()
            ->and($metadata->getHeaders())->toHaveKey('Content-Type');
    });

    test('metadata object handles missing rate limit headers gracefully', function () {
        // Arrange
        $response = Mockery::mock(Psr\Http\Message\ResponseInterface::class);
        $response->shouldReceive('getHeaders')->andReturn([
            'Content-Type' => ['application/json'],
        ]);

        // Act
        $metadata = ResponseMetadata::fromResponse($response);

        // Assert
        expect($metadata->getRateLimitRemaining())->toBeNull()
            ->and($metadata->getRateLimitReset())->toBeNull()
            ->and($metadata->getRequestId())->toBeNull();
    });

    test('metadata object properties are readonly', function () {
        // Arrange
        $response = Mockery::mock(Psr\Http\Message\ResponseInterface::class);
        $response->shouldReceive('getHeaders')->andReturn([]);
        $metadata = ResponseMetadata::fromResponse($response);

        // Act & Assert
        expect(fn () => $metadata->headers = [])
            ->toThrow(Error::class);
    });
});
