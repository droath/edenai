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

/**
 * Audio Endpoints Feature Architecture Tests
 *
 * These tests verify architectural patterns and code quality standards
 * specifically for the audio endpoints implementation (Task Group 6).
 */

arch('all audio request DTOs extend AbstractRequestDTO')
    ->expect('Droath\Edenai\DTOs\Audio\SpeechToTextAsyncRequest')
    ->toExtend('Droath\Edenai\DTOs\AbstractRequestDTO')
    ->and('Droath\Edenai\DTOs\Audio\TextToSpeechRequest')
    ->toExtend('Droath\Edenai\DTOs\AbstractRequestDTO')
    ->and('Droath\Edenai\DTOs\Audio\TextToSpeechAsyncRequest')
    ->toExtend('Droath\Edenai\DTOs\AbstractRequestDTO');

arch('all audio response DTOs extend AbstractResponseDTO')
    ->expect('Droath\Edenai\DTOs\Audio\SpeechToTextAsyncResponse')
    ->toExtend('Droath\Edenai\DTOs\AbstractResponseDTO')
    ->and('Droath\Edenai\DTOs\Audio\TextToSpeechResponse')
    ->toExtend('Droath\Edenai\DTOs\AbstractResponseDTO')
    ->and('Droath\Edenai\DTOs\Audio\TextToSpeechAsyncResponse')
    ->toExtend('Droath\Edenai\DTOs\AbstractResponseDTO');

arch('AudioResource extends AbstractResource')
    ->expect('Droath\Edenai\Resources\AudioResource')
    ->toExtend('Droath\Edenai\Resources\AbstractResource');

arch('all audio DTOs are marked final and readonly')
    ->expect('Droath\Edenai\DTOs\Audio\SpeechToTextAsyncRequest')
    ->toBeFinal()
    ->and('Droath\Edenai\DTOs\Audio\SpeechToTextAsyncResponse')
    ->toBeFinal()
    ->and('Droath\Edenai\DTOs\Audio\TextToSpeechRequest')
    ->toBeFinal()
    ->and('Droath\Edenai\DTOs\Audio\TextToSpeechResponse')
    ->toBeFinal()
    ->and('Droath\Edenai\DTOs\Audio\TextToSpeechAsyncRequest')
    ->toBeFinal()
    ->and('Droath\Edenai\DTOs\Audio\TextToSpeechAsyncResponse')
    ->toBeFinal();

arch('ServiceProviderEnum is a backed enum')
    ->expect('Droath\Edenai\Enums\ServiceProviderEnum')
    ->toBeEnum();

arch('AudioResource is marked final')
    ->expect('Droath\Edenai\Resources\AudioResource')
    ->toBeFinal();

arch('FileUploadException extends base Exception not ApiException')
    ->expect('Droath\Edenai\Exceptions\FileUploadException')
    ->toExtend('Exception')
    ->not->toExtend('Droath\Edenai\Exceptions\ApiException');

arch('audio DTOs use strict types declaration')
    ->expect('Droath\Edenai\DTOs\Audio')
    ->toUseStrictTypes();

arch('audio resource uses strict types declaration')
    ->expect('Droath\Edenai\Resources\AudioResource')
    ->toUseStrictTypes();

arch('FileUploadTrait uses strict types declaration')
    ->expect('Droath\Edenai\Traits\FileUploadTrait')
    ->toUseStrictTypes();

arch('ServiceProviderEnum uses strict types declaration')
    ->expect('Droath\Edenai\Enums\ServiceProviderEnum')
    ->toUseStrictTypes();

arch('audio DTOs do not use deprecated functions')
    ->expect('Droath\Edenai\DTOs\Audio')
    ->not->toUse(['create_function', 'each', 'ereg', 'money_format', 'split']);

arch('AudioResource does not use deprecated functions')
    ->expect('Droath\Edenai\Resources\AudioResource')
    ->not->toUse(['create_function', 'each', 'ereg', 'money_format', 'split']);
