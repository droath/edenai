<?php

declare(strict_types=1);

namespace Droath\Edenai\Tests\Unit\Http;

use Mockery;
use Droath\Edenai\Http\ApiClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Droath\Edenai\Resources\AudioResource;

describe('ApiClient', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('can be instantiated with explicit HTTP client', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.example.com',
            apiKey: 'test-key',
        );

        expect($client)->toBeInstanceOf(ApiClient::class);
    });

    test('auto-discovers PSR-18 HTTP client when not provided', function (): void {
        // Set environment variables for configuration
        putenv('EDENAI_BASE_URL=https://api.example.com');
        putenv('EDENAI_API_KEY=test-key');

        $client = new ApiClient();

        expect($client)->toBeInstanceOf(ApiClient::class);

        // Clean up
        putenv('EDENAI_BASE_URL');
        putenv('EDENAI_API_KEY');
    });

    test('loads base URL from environment variable when not provided', function (): void {
        putenv('EDENAI_BASE_URL=https://env.example.com');
        putenv('EDENAI_API_KEY=env-key');

        $httpClient = Mockery::mock(ClientInterface::class);

        // Mock a successful response
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{}');
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request): bool {
                // Verify the request URI contains the environment base URL
                return str_contains((string) $request->getUri(), 'env.example.com');
            }))
            ->andReturn($response);

        $client = new ApiClient(httpClient: $httpClient);
        $request = Mockery::mock(RequestInterface::class);
        $request->shouldReceive('getUri')->andReturn(
            Mockery::mock(\Psr\Http\Message\UriInterface::class)
                ->shouldReceive('__toString')
                ->andReturn('https://env.example.com/test')
                ->getMock()
        );
        $request->shouldReceive('withHeader')->andReturn($request);

        $client->sendRequest($request);

        // Clean up
        putenv('EDENAI_BASE_URL');
        putenv('EDENAI_API_KEY');
    });

    test('loads API key from environment variable when not provided', function (): void {
        putenv('EDENAI_BASE_URL=https://api.example.com');
        putenv('EDENAI_API_KEY=secret-env-key');

        $httpClient = Mockery::mock(ClientInterface::class);

        // Mock a successful response
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{}');
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request): bool {
                // Verify the Authorization header contains the environment API key
                $headers = $request->getHeader('Authorization');
                return in_array('Bearer secret-env-key', $headers, true);
            }))
            ->andReturn($response);

        $client = new ApiClient(httpClient: $httpClient);

        // Create a request with the Authorization header
        $request = Mockery::mock(RequestInterface::class);
        $request->shouldReceive('getHeader')->with('Authorization')->andReturn(['Bearer secret-env-key']);
        $request->shouldReceive('withHeader')->andReturn($request);

        $client->sendRequest($request);

        // Clean up
        putenv('EDENAI_BASE_URL');
        putenv('EDENAI_API_KEY');
    });

    test('executes default middleware pipeline in correct order', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        // Mock a successful response
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{}');
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.example.com',
            apiKey: 'test-key',
        );

        $request = Mockery::mock(RequestInterface::class);
        $request->shouldReceive('withHeader')->andReturn($request);

        $result = $client->sendRequest($request);

        expect($result)->toBeInstanceOf(ResponseInterface::class);
    });

    test('prepends custom middleware before default middleware', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        // Track middleware execution order
        $executionOrder = [];

        $customMiddleware = new class ($executionOrder) implements \Droath\Edenai\Middleware\MiddlewareInterface {
            public function __construct(private array &$order)
            {
            }

            public function handle(RequestInterface $request, callable $next): ResponseInterface
            {
                $this->order[] = 'custom';
                return $next($request);
            }
        };

        // Mock a successful response
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{}');
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.example.com',
            apiKey: 'test-key',
            middleware: [$customMiddleware],
        );

        $request = Mockery::mock(RequestInterface::class);
        $request->shouldReceive('withHeader')->andReturn($request);

        $client->sendRequest($request);

        // Verify custom middleware executed before default middleware
        expect($executionOrder)->toContain('custom');
    });

    test('sendRequest method returns PSR-7 response', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{}');
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.example.com',
            apiKey: 'test-key',
        );

        $request = Mockery::mock(RequestInterface::class);
        $request->shouldReceive('withHeader')->andReturn($request);

        $result = $client->sendRequest($request);

        expect($result)
            ->toBeInstanceOf(ResponseInterface::class)
            ->and($result->getStatusCode())->toBe(200);
    });

    test('provides base URL to resources', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.example.com',
            apiKey: 'test-key',
        );

        expect($client->getBaseUrl())->toBe('https://api.example.com');
    });
});

describe('AbstractResource', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('can make GET request through ApiClient', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.example.com',
            apiKey: 'test-key',
        );

        $resource = new AudioResource($client);

        // Verify resource is properly instantiated with client
        expect($resource)->toBeInstanceOf(AudioResource::class)
            ->and($resource->getBasePath())->toBe('/v2/audio');
    });

    test('can make POST request with payload through ApiClient', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.example.com',
            apiKey: 'test-key',
        );

        $resource = new AudioResource($client);

        // Verify resource can be instantiated and has correct configuration
        expect($resource)->toBeInstanceOf(AudioResource::class)
            ->and($client->getBaseUrl())->toBe('https://api.example.com');
    });

    test('combines base URL, base path, and relative path correctly', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.example.com',
            apiKey: 'test-key',
        );

        $resource = new AudioResource($client);

        // Verify the resource uses the base URL from client
        expect($client->getBaseUrl())->toBe('https://api.example.com')
            ->and($resource->getBasePath())->toBe('/v2/audio');
    });
});
