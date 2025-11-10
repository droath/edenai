<?php

declare(strict_types=1);

use Droath\Edenai\Exceptions\ApiException;
use Droath\Edenai\Exceptions\ServerException;
use Droath\Edenai\Exceptions\NetworkException;
use Droath\Edenai\Exceptions\RateLimitException;
use Droath\Edenai\Exceptions\ValidationException;
use Droath\Edenai\Exceptions\AuthorizationException;
use Droath\Edenai\Exceptions\AuthenticationException;
use Droath\Edenai\Exceptions\ResourceNotFoundException;

describe('ApiException', function (): void {
    test('constructs with message, status code, and response body', function (): void {
        $message = 'API error occurred';
        $statusCode = 400;
        $responseBody = ['error' => 'Bad request'];

        $exception = new ApiException($message, $statusCode, $responseBody);

        expect($exception->getMessage())->toBe($message)
            ->and($exception->getStatusCode())->toBe($statusCode)
            ->and($exception->getResponseBody())->toBe($responseBody);
    });

    test('constructs with null response body', function (): void {
        $exception = new ApiException('Error', 500, null);

        expect($exception->getResponseBody())->toBeNull();
    });

    test('extends PHP Exception class', function (): void {
        $exception = new ApiException('Error', 500);

        expect($exception)->toBeInstanceOf(Exception::class);
    });

    test('preserves previous exception in chain', function (): void {
        $previous = new Exception('Original error');
        $exception = new ApiException('Wrapped error', 500, null, $previous);

        expect($exception->getPrevious())->toBe($previous);
    });
});

describe('AuthenticationException', function (): void {
    test('has default message and 401 status code', function (): void {
        $exception = new AuthenticationException();

        expect($exception->getMessage())->toBe('Invalid or missing API credentials')
            ->and($exception->getStatusCode())->toBe(401);
    });

    test('allows custom message override', function (): void {
        $exception = new AuthenticationException('Custom auth error');

        expect($exception->getMessage())->toBe('Custom auth error')
            ->and($exception->getStatusCode())->toBe(401);
    });

    test('extends ApiException', function (): void {
        $exception = new AuthenticationException();

        expect($exception)->toBeInstanceOf(ApiException::class);
    });
});

describe('AuthorizationException', function (): void {
    test('has default message and 403 status code', function (): void {
        $exception = new AuthorizationException();

        expect($exception->getMessage())->toBe('Insufficient permissions')
            ->and($exception->getStatusCode())->toBe(403);
    });

    test('extends ApiException', function (): void {
        $exception = new AuthorizationException();

        expect($exception)->toBeInstanceOf(ApiException::class);
    });
});

describe('ResourceNotFoundException', function (): void {
    test('has default message and 404 status code', function (): void {
        $exception = new ResourceNotFoundException();

        expect($exception->getMessage())->toBe('Resource not found')
            ->and($exception->getStatusCode())->toBe(404);
    });

    test('extends ApiException', function (): void {
        $exception = new ResourceNotFoundException();

        expect($exception)->toBeInstanceOf(ApiException::class);
    });
});

describe('ValidationException', function (): void {
    test('has default message, 422 status code, and stores validation errors', function (): void {
        $errors = [
            'email' => ['Email is required'],
            'password' => ['Password must be at least 8 characters'],
        ];
        $exception = new ValidationException('Validation failed', $errors);

        expect($exception->getMessage())->toBe('Validation failed')
            ->and($exception->getStatusCode())->toBe(422)
            ->and($exception->getErrors())->toBe($errors);
    });

    test('extends ApiException', function (): void {
        $exception = new ValidationException('Validation failed', []);

        expect($exception)->toBeInstanceOf(ApiException::class);
    });
});

describe('RateLimitException', function (): void {
    test('has default message, 429 status code, and stores retry after', function (): void {
        $retryAfter = 1730000000;
        $exception = new RateLimitException('Rate limit exceeded', $retryAfter);

        expect($exception->getMessage())->toBe('Rate limit exceeded')
            ->and($exception->getStatusCode())->toBe(429)
            ->and($exception->getRetryAfter())->toBe($retryAfter);
    });

    test('allows null retry after value', function (): void {
        $exception = new RateLimitException('Rate limit exceeded', null);

        expect($exception->getRetryAfter())->toBeNull();
    });

    test('extends ApiException', function (): void {
        $exception = new RateLimitException('Rate limit exceeded', null);

        expect($exception)->toBeInstanceOf(ApiException::class);
    });
});

describe('ServerException', function (): void {
    test('has default message and 500 status code', function (): void {
        $exception = new ServerException();

        expect($exception->getMessage())->toBe('Server error occurred')
            ->and($exception->getStatusCode())->toBe(500);
    });

    test('allows custom status code for 5xx range', function (): void {
        $exception = new ServerException('Bad Gateway', 502);

        expect($exception->getMessage())->toBe('Bad Gateway')
            ->and($exception->getStatusCode())->toBe(502);
    });

    test('extends ApiException', function (): void {
        $exception = new ServerException();

        expect($exception)->toBeInstanceOf(ApiException::class);
    });
});

describe('NetworkException', function (): void {
    test('has default message and status code 0', function (): void {
        $exception = new NetworkException();

        expect($exception->getMessage())->toBe('Network error occurred')
            ->and($exception->getStatusCode())->toBe(0);
    });

    test('allows custom message for specific network error', function (): void {
        $exception = new NetworkException('Connection timeout');

        expect($exception->getMessage())->toBe('Connection timeout')
            ->and($exception->getStatusCode())->toBe(0);
    });

    test('extends ApiException', function (): void {
        $exception = new NetworkException();

        expect($exception)->toBeInstanceOf(ApiException::class);
    });
});
