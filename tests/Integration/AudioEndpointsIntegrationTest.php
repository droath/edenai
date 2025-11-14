<?php

declare(strict_types=1);

namespace Droath\Edenai\Tests\Integration;

use Mockery;
use JsonException;
use RuntimeException;
use DateTimeImmutable;
use Droath\Edenai\Http\ApiClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Droath\Edenai\Resources\AudioResource;
use Droath\Edenai\Enums\ServiceProviderEnum;
use Droath\Edenai\Exceptions\NetworkException;
use Droath\Edenai\DTOs\Audio\TextToSpeechRequest;
use Droath\Edenai\Exceptions\ValidationException;
use Droath\Edenai\DTOs\Audio\TextToSpeechResponse;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncRequest;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncRequest;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncResponse;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncResponse;

/**
 * Integration tests for audio endpoints covering end-to-end workflows and error scenarios.
 *
 * These tests verify complete request-to-response cycles including:
 * - Multiple provider selection and serialization
 * - API error response handling
 * - Network failure scenarios
 * - Large file handling edge cases
 * - Malformed response handling
 */
describe('Integration: Complete speechToTextAsync workflow', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('complete flow with multiple providers and all parameters', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'integration-test-key';

        // Create test audio file
        $testFilePath = sys_get_temp_dir().'/integration_test.mp3';
        file_put_contents($testFilePath, str_repeat('audio data ', 100));

        // Mock successful API response with multiple providers
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(json_encode([
            'public_id' => 'integration-job-12345',
            'status' => 'pending',
            'results' => [
                'google' => ['status' => 'pending'],
                'amazon' => ['status' => 'pending'],
                'deepgram' => ['status' => 'pending'],
            ],
        ]));
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')->once()->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new AudioResource($client);

        $requestDTO = new SpeechToTextAsyncRequest(
            file: $testFilePath,
            providers: [ServiceProviderEnum::GOOGLE, ServiceProviderEnum::AMAZON, ServiceProviderEnum::DEEPGRAM],
            language: 'fr',
            speakers: 3,
            profanityFilter: true,
        );

        $result = $resource->speechToTextAsync($requestDTO);

        expect($result)->toBeInstanceOf(SpeechToTextAsyncResponse::class)
            ->and($result->jobId)->toBe('integration-job-12345')
            ->and($result->providers)->toBe(['google', 'amazon', 'deepgram'])
            ->and($result->submittedAt)->toBeInstanceOf(DateTimeImmutable::class);

        // Clean up
        unlink($testFilePath);
    });
});

describe('Integration: Complete textToSpeech workflow', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('complete flow with Base64 decoding and all optional parameters', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'integration-test-key';

        // Create realistic audio binary data
        $rawAudio = pack('C*', ...array_map(fn () => rand(0, 255), range(1, 500)));
        $base64Audio = base64_encode($rawAudio);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(json_encode([
            'microsoft' => [
                'audio' => $base64Audio,
                'duration' => 12.5,
            ],
        ]));
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')->once()->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new AudioResource($client);

        $requestDTO = new TextToSpeechRequest(
            text: 'This is a complete integration test with all parameters.',
            providers: [ServiceProviderEnum::MICROSOFT, ServiceProviderEnum::AZURE],
            language: 'en-US',
            option: 'FEMALE',
            audioFormat: 'wav',
            rate: 1,
            pitch: 1,
            volume: 1,
        );

        $result = $resource->textToSpeech($requestDTO);

        expect($result)->toBeInstanceOf(TextToSpeechResponse::class)
            ->and($result->audioData)->toBe($rawAudio)
            ->and($result->contentType)->toBe('audio/mpeg')
            ->and($result->duration)->toBe(12.5);
    });
});

describe('Integration: Complete textToSpeechAsync workflow', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('complete flow with job ID return and provider tracking', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'integration-test-key';

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(json_encode([
            'public_id' => 'async-tts-job-789',
            'results' => [
                'openai' => ['status' => 'pending'],
                'ibmwatson' => ['status' => 'pending'],
            ],
        ]));
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')->once()->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new AudioResource($client);

        $requestDTO = new TextToSpeechAsyncRequest(
            text: 'Async text-to-speech with multiple providers',
            providers: [ServiceProviderEnum::OPENAI, ServiceProviderEnum::IBMWATSON],
            language: 'es',
            rate: 1,
            pitch: 1,
        );

        $result = $resource->textToSpeechAsync($requestDTO);

        expect($result)->toBeInstanceOf(TextToSpeechAsyncResponse::class)
            ->and($result->jobId)->toBe('async-tts-job-789')
            ->and($result->providers)->toBe(['openai', 'ibmwatson'])
            ->and($result->submittedAt)->toBeInstanceOf(DateTimeImmutable::class);
    });
});

describe('Integration: Error handling scenarios', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('malformed JSON response throws JsonException', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'error-test-key';

        // Mock response with invalid JSON
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('{"invalid json missing brace"');
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')->once()->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new AudioResource($client);

        $requestDTO = new TextToSpeechRequest(
            text: 'Test malformed response',
            providers: [ServiceProviderEnum::GOOGLE],
        );

        expect(fn () => $resource->textToSpeech($requestDTO))
            ->toThrow(JsonException::class);
    });

    test('network failure throws NetworkException through middleware', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'network-error-key';

        // Create a simple exception class that implements ClientExceptionInterface
        $networkException = new class ('Connection timeout') extends RuntimeException implements \Psr\Http\Client\ClientExceptionInterface {
        };

        // Simulate network failure - middleware will retry, so expect 3 attempts
        $httpClient->shouldReceive('sendRequest')
            ->times(3) // RetryMiddleware will retry twice after initial failure
            ->andThrow($networkException);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new AudioResource($client);

        $requestDTO = new TextToSpeechRequest(
            text: 'Test network failure',
            providers: [ServiceProviderEnum::AMAZON],
        );

        expect(fn () => $resource->textToSpeech($requestDTO))
            ->toThrow(NetworkException::class);
    });

    test('empty provider array validation', function (): void {
        // This should be caught at DTO construction if validation is added
        // For now, we test that it can be created (API will validate)
        $requestDTO = new TextToSpeechRequest(
            text: 'Test with empty providers',
            providers: [],
        );

        expect($requestDTO->providers)->toBeArray()
            ->and($requestDTO->providers)->toHaveCount(0);
    });
});

describe('Integration: File size and format edge cases', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('large file upload workflow', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'large-file-test-key';

        // Create a large test file (1MB)
        $testFilePath = sys_get_temp_dir().'/large_audio.wav';
        $largeContent = str_repeat('A', 1024 * 1024); // 1MB
        file_put_contents($testFilePath, $largeContent);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(json_encode([
            'public_id' => 'large-file-job',
            'status' => 'pending',
            'results' => [
                'google' => ['status' => 'pending'],
            ],
        ]));
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')->once()->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new AudioResource($client);

        $requestDTO = new SpeechToTextAsyncRequest(
            file: $testFilePath,
            providers: [ServiceProviderEnum::GOOGLE],
        );

        $result = $resource->speechToTextAsync($requestDTO);

        expect($result)->toBeInstanceOf(SpeechToTextAsyncResponse::class)
            ->and($result->jobId)->toBe('large-file-job');

        // Clean up
        unlink($testFilePath);
    });

    test('all supported audio formats pass validation', function (): void {
        $formats = ['mp3', 'wav', 'flac', 'ogg'];

        foreach ($formats as $format) {
            $testFilePath = sys_get_temp_dir()."/test_format.{$format}";
            file_put_contents($testFilePath, 'audio content');

            // Should not throw exception
            $requestDTO = new SpeechToTextAsyncRequest(
                file: $testFilePath,
                providers: [ServiceProviderEnum::GOOGLE],
            );

            expect($requestDTO->file)->toBe($testFilePath);

            unlink($testFilePath);
        }
    });

    test('unsupported format in various cases throws ValidationException', function (): void {
        $unsupportedFormats = ['aac', 'm4a', 'wma', 'avi', 'mp4'];

        foreach ($unsupportedFormats as $format) {
            $testFilePath = sys_get_temp_dir()."/unsupported.{$format}";
            file_put_contents($testFilePath, 'content');

            expect(fn () => new SpeechToTextAsyncRequest(
                file: $testFilePath,
                providers: [ServiceProviderEnum::GOOGLE],
            ))->toThrow(ValidationException::class);

            unlink($testFilePath);
        }
    });
});

describe('Integration: Provider serialization', function (): void {
    test('multiple providers serialize correctly in request', function (): void {
        $testFilePath = sys_get_temp_dir().'/provider_test.mp3';
        file_put_contents($testFilePath, 'audio');

        $request = new SpeechToTextAsyncRequest(
            file: $testFilePath,
            providers: [
                ServiceProviderEnum::GOOGLE,
                ServiceProviderEnum::AMAZON,
                ServiceProviderEnum::MICROSOFT,
                ServiceProviderEnum::OPENAI,
                ServiceProviderEnum::DEEPGRAM,
            ],
        );

        $array = $request->toArray();

        expect($array['providers'])->toBeArray()
            ->and($array['providers'])->toBe(['google', 'amazon', 'microsoft', 'openai', 'deepgram'])
            ->and($array['providers'])->toHaveCount(5);

        unlink($testFilePath);
    });

    test('single provider serializes correctly', function (): void {
        $request = new TextToSpeechRequest(
            text: 'Single provider test',
            providers: [ServiceProviderEnum::ASSEMBLY_AI],
        );

        $array = $request->toArray();

        expect($array['providers'])->toBeArray()
            ->and($array['providers'])->toBe(['assembly_ai'])
            ->and($array['providers'])->toHaveCount(1);
    });
});
