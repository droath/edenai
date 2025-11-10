<?php

declare(strict_types=1);

use Droath\Edenai\Middleware\MiddlewareInterface;

/**
 * Architecture Tests
 *
 * These tests enforce code standards and architectural patterns across the codebase.
 * They ensure consistency, maintainability, and adherence to project conventions.
 */

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'var_dump', 'print_r'])
    ->each->not->toBeUsed();

arch('all PHP files in src/ have strict types declaration')
    ->expect('Droath\Edenai')
    ->toUseStrictTypes();

arch('all DTO classes are marked final for immutability')
    ->expect('Droath\Edenai\DTOs\ExampleRequestDTO')
    ->toBeFinal()
    ->and('Droath\Edenai\DTOs\ExampleResponseDTO')
    ->toBeFinal()
    ->and('Droath\Edenai\DTOs\ResponseMetadata')
    ->toBeFinal();

arch('all concrete classes are marked final')
    ->expect('Droath\Edenai')
    ->classes()
    ->toBeFinal()
    ->ignoring([
        // Abstract classes
        'Droath\Edenai\DTOs\AbstractRequestDTO',
        'Droath\Edenai\DTOs\AbstractResponseDTO',
        'Droath\Edenai\Resources\AbstractResource',
        'Droath\Edenai\Exceptions\ApiException',
        // Interfaces
        'Droath\Edenai\Middleware\MiddlewareInterface',
    ]);

arch('all middleware implement MiddlewareInterface')
    ->expect('Droath\Edenai\Middleware\AuthenticationMiddleware')
    ->toImplement(MiddlewareInterface::class)
    ->and('Droath\Edenai\Middleware\ErrorHandlingMiddleware')
    ->toImplement(MiddlewareInterface::class)
    ->and('Droath\Edenai\Middleware\RetryMiddleware')
    ->toImplement(MiddlewareInterface::class);

arch('namespace organization matches PSR-4 autoloading')
    ->expect('Droath\Edenai\Http\ApiClient')
    ->toBeClass()
    ->and('Droath\Edenai\Exceptions\ApiException')
    ->toBeClass()
    ->and('Droath\Edenai\Middleware\Pipeline')
    ->toBeClass()
    ->and('Droath\Edenai\Resources\AbstractResource')
    ->toBeClass()
    ->and('Droath\Edenai\DTOs\ResponseMetadata')
    ->toBeClass();

arch('exception classes extend base ApiException')
    ->expect('Droath\Edenai\Exceptions\AuthenticationException')
    ->toExtend('Droath\Edenai\Exceptions\ApiException')
    ->and('Droath\Edenai\Exceptions\AuthorizationException')
    ->toExtend('Droath\Edenai\Exceptions\ApiException')
    ->and('Droath\Edenai\Exceptions\ResourceNotFoundException')
    ->toExtend('Droath\Edenai\Exceptions\ApiException')
    ->and('Droath\Edenai\Exceptions\ValidationException')
    ->toExtend('Droath\Edenai\Exceptions\ApiException')
    ->and('Droath\Edenai\Exceptions\RateLimitException')
    ->toExtend('Droath\Edenai\Exceptions\ApiException')
    ->and('Droath\Edenai\Exceptions\ServerException')
    ->toExtend('Droath\Edenai\Exceptions\ApiException')
    ->and('Droath\Edenai\Exceptions\NetworkException')
    ->toExtend('Droath\Edenai\Exceptions\ApiException');
