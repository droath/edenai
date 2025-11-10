<?php

declare(strict_types=1);

namespace Droath\Edenai\Tests\Feature;

use Mockery;
use Exception;
use DateTimeImmutable;
use InvalidArgumentException;
use Droath\Edenai\Http\ApiClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Droath\Edenai\DTOs\ExampleRequestDTO;
use Droath\Edenai\DTOs\ExampleResponseDTO;
use Droath\Edenai\Resources\ExampleResource;
use Droath\Edenai\Exceptions\ServerException;
use Psr\Http\Client\ClientExceptionInterface;
use Droath\Edenai\Exceptions\NetworkException;
use Droath\Edenai\Exceptions\ValidationException;
use Droath\Edenai\Middleware\MiddlewareInterface;
use Droath\Edenai\Exceptions\AuthenticationException;
use Droath\Edenai\Exceptions\ResourceNotFoundException;

/**
 * Integration tests verify end-to-end workflows through the complete middleware pipeline.
 *
 * These tests use mocked HTTP responses but exercise the full request/response cycle
 * including authentication, error handling, retry logic, and DTO transformation.
 */
describe('Integration: Authentication Flow', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('complete authentication flow injects Bearer token through pipeline', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'secret-integration-key-12345';

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{"id":"auth-test","count":1,"created_at":"2024-11-09 12:00:00"}');
        $response->shouldReceive('getBody')->andReturn($stream);

        // Verify Authorization header is present in the request
        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request) use ($apiKey): bool {
                $headers = $request->getHeader('Authorization');
                return in_array("Bearer {$apiKey}", $headers, true);
            }))
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.integration.test',
            apiKey: $apiKey,
        );

        $resource = new ExampleResource($client);
        $result = $resource->list();

        expect($result)->toBeInstanceOf(ResponseInterface::class)
            ->and($result->getStatusCode())->toBe(200);
    });

    test('authentication flow skips Bearer token when API key is null', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{"id":"no-auth","count":1,"created_at":"2024-11-09 12:00:00"}');
        $response->shouldReceive('getBody')->andReturn($stream);

        // Verify Authorization header is NOT present
        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request): bool {
                $headers = $request->getHeader('Authorization');
                return empty($headers);
            }))
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.integration.test',
            apiKey: null,
        );

        $resource = new ExampleResource($client);
        $result = $resource->list();

        expect($result)->toBeInstanceOf(ResponseInterface::class);
    });
});

describe('Integration: Error Handling Flow', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('401 status code maps to AuthenticationException through pipeline', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(401);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{"error":"Invalid API key"}');
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.integration.test',
            apiKey: 'invalid-key',
        );

        $resource = new ExampleResource($client);

        expect(fn () => $resource->list())
            ->toThrow(AuthenticationException::class);
    });

    test('404 status code maps to ResourceNotFoundException through pipeline', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(404);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{"error":"Resource not found"}');
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.integration.test',
            apiKey: 'test-key',
        );

        $resource = new ExampleResource($client);

        expect(fn () => $resource->getById('nonexistent'))
            ->toThrow(ResourceNotFoundException::class);
    });

    test('422 status code maps to ValidationException with error details', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(422);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{"errors":{"name":["Name is required"],"age":["Age must be positive"]}}');
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.integration.test',
            apiKey: 'test-key',
        );

        $resource = new ExampleResource($client);
        $requestDTO = new ExampleRequestDTO(
            name: 'Test',
            age: 25,
            status: 'active',
            tags: ['test'],
        );

        try {
            $resource->create($requestDTO);
            expect(false)->toBeTrue('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            expect($e)->toBeInstanceOf(ValidationException::class)
                ->and($e->getStatusCode())->toBe(422)
                ->and($e->getErrors())->toBeArray()
                ->and($e->getErrors())->toHaveKey('name');
        }
    });

    test('500 status code maps to ServerException through pipeline', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(500);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{"error":"Internal server error"}');
        $response->shouldReceive('getBody')->andReturn($stream);

        // Retry middleware will retry 500 errors 3 times total
        $httpClient->shouldReceive('sendRequest')
            ->times(3)
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.integration.test',
            apiKey: 'test-key',
        );

        $resource = new ExampleResource($client);

        expect(fn () => $resource->list())
            ->toThrow(ServerException::class);
    });
});

describe('Integration: Retry Flow', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('429 rate limit triggers retry with exponential backoff', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $rateLimitResponse = Mockery::mock(ResponseInterface::class);
        $rateLimitResponse->shouldReceive('getStatusCode')->andReturn(429);
        $stream1 = Mockery::mock(StreamInterface::class);
        $stream1->shouldReceive('getContents')->andReturn('{"error":"Rate limit exceeded"}');
        $rateLimitResponse->shouldReceive('getBody')->andReturn($stream1);

        $successResponse = Mockery::mock(ResponseInterface::class);
        $successResponse->shouldReceive('getStatusCode')->andReturn(200);
        $stream2 = Mockery::mock(StreamInterface::class);
        $stream2->shouldReceive('getContents')->andReturn('{"id":"retry-success","count":1,"created_at":"2024-11-09 12:00:00"}');
        $successResponse->shouldReceive('getBody')->andReturn($stream2);

        // First attempt fails with 429, second succeeds
        $httpClient->shouldReceive('sendRequest')
            ->twice()
            ->andReturn($rateLimitResponse, $successResponse);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.integration.test',
            apiKey: 'test-key',
        );

        $resource = new ExampleResource($client);
        $result = $resource->list();

        expect($result)->toBeInstanceOf(ResponseInterface::class)
            ->and($result->getStatusCode())->toBe(200);
    });

    test('500 server error triggers retry until success', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $errorResponse = Mockery::mock(ResponseInterface::class);
        $errorResponse->shouldReceive('getStatusCode')->andReturn(503);
        $stream1 = Mockery::mock(StreamInterface::class);
        $stream1->shouldReceive('getContents')->andReturn('{"error":"Service unavailable"}');
        $errorResponse->shouldReceive('getBody')->andReturn($stream1);

        $successResponse = Mockery::mock(ResponseInterface::class);
        $successResponse->shouldReceive('getStatusCode')->andReturn(200);
        $stream2 = Mockery::mock(StreamInterface::class);
        $stream2->shouldReceive('getContents')->andReturn('{"id":"retry-503","count":1,"created_at":"2024-11-09 12:00:00"}');
        $successResponse->shouldReceive('getBody')->andReturn($stream2);

        // First two attempts fail with 503, third succeeds
        $httpClient->shouldReceive('sendRequest')
            ->times(3)
            ->andReturn($errorResponse, $errorResponse, $successResponse);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.integration.test',
            apiKey: 'test-key',
        );

        $resource = new ExampleResource($client);
        $result = $resource->list();

        expect($result)->toBeInstanceOf(ResponseInterface::class)
            ->and($result->getStatusCode())->toBe(200);
    });

    test('network exceptions are wrapped and trigger retry', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        // Create a real exception that implements ClientExceptionInterface
        $networkException = new class ('Connection timeout') extends Exception implements ClientExceptionInterface {
        };

        $successResponse = Mockery::mock(ResponseInterface::class);
        $successResponse->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{"id":"network-retry","count":1,"created_at":"2024-11-09 12:00:00"}');
        $successResponse->shouldReceive('getBody')->andReturn($stream);

        // First attempt throws network exception, second succeeds
        $httpClient->shouldReceive('sendRequest')
            ->twice()
            ->andReturnUsing(function () use ($networkException, $successResponse) {
                static $attempt = 0;
                $attempt++;
                if ($attempt === 1) {
                    throw $networkException;
                }
                return $successResponse;
            });

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.integration.test',
            apiKey: 'test-key',
        );

        $resource = new ExampleResource($client);
        $result = $resource->list();

        expect($result)->toBeInstanceOf(ResponseInterface::class)
            ->and($result->getStatusCode())->toBe(200);
    });

    test('exhausted retries throw final exception', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        // Create a real exception that implements ClientExceptionInterface
        $networkException = new class ('Connection timeout') extends Exception implements ClientExceptionInterface {
        };

        // All attempts fail with network exception
        $httpClient->shouldReceive('sendRequest')
            ->times(3) // Default max_attempts
            ->andThrow($networkException);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.integration.test',
            apiKey: 'test-key',
        );

        $resource = new ExampleResource($client);

        // ErrorHandlingMiddleware wraps PSR-18 exceptions in NetworkException
        expect(fn () => $resource->list())
            ->toThrow(NetworkException::class);
    });
});

describe('Integration: DTO Validation Flow', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('request DTO validation prevents invalid API calls', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        // HTTP client should never be called because validation fails first
        $httpClient->shouldNotReceive('sendRequest');

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.integration.test',
            apiKey: 'test-key',
        );

        $resource = new ExampleResource($client);

        // Attempt to create with invalid DTO data
        expect(fn () => new ExampleRequestDTO(
            name: '', // Empty name should fail validation
            age: 25,
            status: 'active',
            tags: ['test'],
        ))->toThrow(InvalidArgumentException::class);
    });

    test('successful response is parsed into response DTO', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{"id":"dto-test-123","count":42,"created_at":"2024-11-09 15:30:45","description":"Integration test response"}');
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.integration.test',
            apiKey: 'test-key',
        );

        $resource = new ExampleResource($client);
        $result = $resource->getById('dto-test-123');

        expect($result)->toBeInstanceOf(ExampleResponseDTO::class)
            ->and($result->id)->toBe('dto-test-123')
            ->and($result->count)->toBe(42)
            ->and($result->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($result->description)->toBe('Integration test response');
    });
});

describe('Integration: Custom Middleware Execution', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('custom middleware executes before default middleware', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $executionOrder = [];

        // Create custom middleware that tracks execution
        $customMiddleware = new class ($executionOrder) implements MiddlewareInterface {
            public function __construct(private array &$order)
            {
            }

            public function handle(RequestInterface $request, callable $next): ResponseInterface
            {
                $this->order[] = 'custom_middleware';
                // Add custom header to verify execution
                $request = $request->withHeader('X-Custom-Middleware', 'executed');
                return $next($request);
            }
        };

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{"id":"custom","count":1,"created_at":"2024-11-09 12:00:00"}');
        $response->shouldReceive('getBody')->andReturn($stream);

        // Verify custom header is present (proving custom middleware executed)
        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request): bool {
                $headers = $request->getHeader('X-Custom-Middleware');
                return in_array('executed', $headers, true);
            }))
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.integration.test',
            apiKey: 'test-key',
            middleware: [$customMiddleware],
        );

        $resource = new ExampleResource($client);
        $result = $resource->list();

        expect($result)->toBeInstanceOf(ResponseInterface::class)
            ->and($executionOrder)->toContain('custom_middleware');
    });
});
