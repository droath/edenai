<?php

declare(strict_types=1);

use Droath\Edenai\Middleware\Pipeline;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Droath\Edenai\Exceptions\ServerException;
use Droath\Edenai\Middleware\RetryMiddleware;
use Psr\Http\Client\ClientExceptionInterface;
use Droath\Edenai\Exceptions\NetworkException;
use Droath\Edenai\Exceptions\RateLimitException;
use Droath\Edenai\Exceptions\ValidationException;
use Droath\Edenai\Middleware\MiddlewareInterface;
use Droath\Edenai\Exceptions\AuthenticationException;
use Droath\Edenai\Middleware\ErrorHandlingMiddleware;
use Droath\Edenai\Middleware\AuthenticationMiddleware;
use Droath\Edenai\Exceptions\ResourceNotFoundException;

describe('MiddlewareInterface', function (): void {
    test('defines handle method contract', function (): void {
        $middleware = Mockery::mock(MiddlewareInterface::class);

        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $middleware->shouldReceive('handle')
            ->with($request, Mockery::type('callable'))
            ->andReturn($response);

        $result = $middleware->handle($request, fn () => $response);

        expect($result)->toBe($response);
    });
});

describe('AuthenticationMiddleware', function (): void {
    test('injects Authorization header when API key is provided', function (): void {
        $apiKey = 'test-api-key-12345';
        $middleware = new AuthenticationMiddleware($apiKey);

        $request = Mockery::mock(RequestInterface::class);
        $modifiedRequest = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $request->shouldReceive('withHeader')
            ->once()
            ->with('Authorization', "Bearer {$apiKey}")
            ->andReturn($modifiedRequest);

        $next = function (RequestInterface $req) use ($modifiedRequest, $response): ResponseInterface {
            expect($req)->toBe($modifiedRequest);
            return $response;
        };

        $result = $middleware->handle($request, $next);

        expect($result)->toBe($response);
    });

    test('skips Authorization header when API key is null', function (): void {
        $middleware = new AuthenticationMiddleware(null);

        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $request->shouldNotReceive('withHeader');

        $next = function (RequestInterface $req) use ($request, $response): ResponseInterface {
            expect($req)->toBe($request);
            return $response;
        };

        $result = $middleware->handle($request, $next);

        expect($result)->toBe($response);
    });
});

describe('ErrorHandlingMiddleware', function (): void {
    test('maps 401 status to AuthenticationException', function (): void {
        $middleware = new ErrorHandlingMiddleware();

        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $response->shouldReceive('getStatusCode')->andReturn(401);
        $response->shouldReceive('getBody->getContents')->andReturn('{"error": "Unauthorized"}');

        $next = fn () => $response;

        expect(fn () => $middleware->handle($request, $next))
            ->toThrow(AuthenticationException::class);
    });

    test('maps 404 status to ResourceNotFoundException', function (): void {
        $middleware = new ErrorHandlingMiddleware();

        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $response->shouldReceive('getStatusCode')->andReturn(404);
        $response->shouldReceive('getBody->getContents')->andReturn('{"error": "Not found"}');

        $next = fn () => $response;

        expect(fn () => $middleware->handle($request, $next))
            ->toThrow(ResourceNotFoundException::class);
    });

    test('maps 422 status to ValidationException', function (): void {
        $middleware = new ErrorHandlingMiddleware();

        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $response->shouldReceive('getStatusCode')->andReturn(422);
        $response->shouldReceive('getBody->getContents')->andReturn('{"errors": {"field": ["error"]}}');

        $next = fn () => $response;

        expect(fn () => $middleware->handle($request, $next))
            ->toThrow(ValidationException::class);
    });

    test('maps 429 status to RateLimitException', function (): void {
        $middleware = new ErrorHandlingMiddleware();

        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $response->shouldReceive('getStatusCode')->andReturn(429);
        $response->shouldReceive('getBody->getContents')->andReturn('{}');

        $next = fn () => $response;

        expect(fn () => $middleware->handle($request, $next))
            ->toThrow(RateLimitException::class);
    });

    test('maps 500+ status to ServerException', function (): void {
        $middleware = new ErrorHandlingMiddleware();

        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $response->shouldReceive('getStatusCode')->andReturn(500);
        $response->shouldReceive('getBody->getContents')->andReturn('{"error": "Internal error"}');

        $next = fn () => $response;

        expect(fn () => $middleware->handle($request, $next))
            ->toThrow(ServerException::class);
    });

    test('passes successful 2xx responses through unchanged', function (): void {
        $middleware = new ErrorHandlingMiddleware();

        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $response->shouldReceive('getStatusCode')->andReturn(200);

        $next = fn () => $response;

        $result = $middleware->handle($request, $next);

        expect($result)->toBe($response);
    });

    test('wraps PSR-18 ClientException in NetworkException', function (): void {
        $middleware = new ErrorHandlingMiddleware();

        $request = Mockery::mock(RequestInterface::class);

        // Create a real exception that implements ClientExceptionInterface
        $clientException = new class ('Connection failed') extends Exception implements ClientExceptionInterface {
        };

        $next = function () use ($clientException): never {
            throw $clientException;
        };

        expect(fn () => $middleware->handle($request, $next))
            ->toThrow(NetworkException::class);
    });
});

describe('RetryMiddleware', function (): void {
    test('retries on 429 status code with exponential backoff', function (): void {
        $config = ['max_attempts' => 3, 'backoff' => 'exponential', 'enable_sleep' => false];
        $middleware = new RetryMiddleware($config);

        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $response->shouldReceive('getStatusCode')->andReturn(429, 429, 200);

        $attempts = 0;
        $next = function () use ($response, &$attempts): ResponseInterface {
            $attempts++;
            return $response;
        };

        $result = $middleware->handle($request, $next);

        expect($result)->toBe($response)
            ->and($attempts)->toBe(3);
    });

    test('retries on 500 status code', function (): void {
        $config = ['max_attempts' => 2, 'backoff' => 'exponential', 'enable_sleep' => false];
        $middleware = new RetryMiddleware($config);

        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $response->shouldReceive('getStatusCode')->andReturn(500, 200);

        $attempts = 0;
        $next = function () use ($response, &$attempts): ResponseInterface {
            $attempts++;
            return $response;
        };

        $result = $middleware->handle($request, $next);

        expect($result)->toBe($response)
            ->and($attempts)->toBe(2);
    });

    test('does not retry on 4xx errors except 429', function (): void {
        $config = ['max_attempts' => 3, 'backoff' => 'exponential', 'enable_sleep' => false];
        $middleware = new RetryMiddleware($config);

        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $response->shouldReceive('getStatusCode')->andReturn(404);

        $attempts = 0;
        $next = function () use ($response, &$attempts): ResponseInterface {
            $attempts++;
            return $response;
        };

        $result = $middleware->handle($request, $next);

        expect($result)->toBe($response)
            ->and($attempts)->toBe(1);
    });

    test('throws exception after max attempts exceeded', function (): void {
        $config = ['max_attempts' => 2, 'backoff' => 'exponential', 'enable_sleep' => false];
        $middleware = new RetryMiddleware($config);

        $request = Mockery::mock(RequestInterface::class);

        // Create a real exception that implements ClientExceptionInterface
        $clientException = new class ('Network error') extends Exception implements ClientExceptionInterface {
        };

        $attempts = 0;
        $next = function () use ($clientException, &$attempts): never {
            $attempts++;
            throw $clientException;
        };

        try {
            $middleware->handle($request, $next);
            expect(false)->toBeTrue('Expected exception to be thrown');
        } catch (ClientExceptionInterface $e) {
            expect($e)->toBe($clientException)
                ->and($attempts)->toBe(2);
        }
    });
});

describe('Pipeline', function (): void {
    test('executes middleware in order', function (): void {
        $executionOrder = [];

        $middleware1 = Mockery::mock(MiddlewareInterface::class);
        $middleware2 = Mockery::mock(MiddlewareInterface::class);

        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $middleware1->shouldReceive('handle')
            ->once()
            ->with($request, Mockery::type('callable'))
            ->andReturnUsing(function ($req, $next) use (&$executionOrder, $response) {
                $executionOrder[] = 'middleware1';
                return $next($req);
            });

        $middleware2->shouldReceive('handle')
            ->once()
            ->with($request, Mockery::type('callable'))
            ->andReturnUsing(function ($req, $next) use (&$executionOrder, $response) {
                $executionOrder[] = 'middleware2';
                return $next($req);
            });

        $final = function () use (&$executionOrder, $response): ResponseInterface {
            $executionOrder[] = 'final';
            return $response;
        };

        $pipeline = new Pipeline([$middleware1, $middleware2]);
        $result = $pipeline->process($request, $final);

        expect($result)->toBe($response)
            ->and($executionOrder)->toBe(['middleware1', 'middleware2', 'final']);
    });

    test('handles empty middleware array', function (): void {
        $request = Mockery::mock(RequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);

        $final = fn () => $response;

        $pipeline = new Pipeline([]);
        $result = $pipeline->process($request, $final);

        expect($result)->toBe($response);
    });
});
