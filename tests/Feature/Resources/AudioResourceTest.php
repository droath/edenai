<?php

declare(strict_types=1);

namespace Droath\Edenai\Tests\Feature\Resources;

use Mockery;
use DateTimeImmutable;
use Droath\Edenai\Http\ApiClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Droath\Edenai\Resources\AudioResource;
use Droath\Edenai\Enums\ServiceProviderEnum;
use Droath\Edenai\DTOs\Audio\TextToSpeechRequest;
use Droath\Edenai\DTOs\Audio\TextToSpeechResponse;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncRequest;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncRequest;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncResponse;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncResponse;

/**
 * Feature tests for AudioResource covering the three audio endpoint methods.
 *
 * These tests verify that the AudioResource properly:
 * - Creates multipart requests for file uploads (speech-to-text async)
 * - Creates JSON requests for text-to-speech operations
 * - Parses responses into strongly-typed response DTOs
 * - Integrates with ApiClient middleware pipeline for authentication
 */
describe('Feature: AudioResource - speechToTextAsync()', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('speechToTextAsync creates multipart request and returns typed response', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'test-audio-key-12345';

        // Create temporary test audio file
        $testFilePath = sys_get_temp_dir().'/test_audio.mp3';
        file_put_contents($testFilePath, 'fake audio content');

        // Mock successful API response
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(json_encode([
            'job_id' => 'job-speech-123',
            'providers' => ['google'],
            'submitted_at' => '2024-11-09 12:00:00',
        ]));
        $response->shouldReceive('getBody')->andReturn($stream);

        // Verify multipart/form-data request is created
        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request): bool {
                $contentType = $request->getHeader('Content-Type');
                return !empty($contentType) && str_contains($contentType[0], 'multipart/form-data');
            }))
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new AudioResource($client);

        $requestDTO = new SpeechToTextAsyncRequest(
            file: $testFilePath,
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
        );

        $result = $resource->speechToTextAsync($requestDTO);

        expect($result)->toBeInstanceOf(SpeechToTextAsyncResponse::class)
            ->and($result->jobId)->toBe('job-speech-123')
            ->and($result->providers)->toBe(['google'])
            ->and($result->submittedAt)->toBeInstanceOf(DateTimeImmutable::class);

        // Clean up
        unlink($testFilePath);
    });
});

describe('Feature: AudioResource - textToSpeech()', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('textToSpeech creates JSON request and returns typed response with decoded audio', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'test-audio-key-12345';

        // Mock successful API response with Base64 encoded audio
        $rawAudio = 'raw audio binary data here';
        $base64Audio = base64_encode($rawAudio);

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(json_encode([
            'audio' => $base64Audio,
            'content_type' => 'audio/mpeg',
            'duration' => null,
        ]));
        $response->shouldReceive('getBody')->andReturn($stream);

        // Verify JSON request with Content-Type header
        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request): bool {
                $contentType = $request->getHeader('Content-Type');
                return !empty($contentType) && in_array('application/json', $contentType, true);
            }))
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new AudioResource($client);

        $requestDTO = new TextToSpeechRequest(
            text: 'Hello world',
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
        );

        $result = $resource->textToSpeech($requestDTO);

        expect($result)->toBeInstanceOf(TextToSpeechResponse::class)
            ->and($result->audioData)->toBe($rawAudio) // Decoded from Base64
            ->and($result->contentType)->toBe('audio/mpeg')
            ->and($result->duration)->toBeNull();
    });
});

describe('Feature: AudioResource - textToSpeechAsync()', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('textToSpeechAsync creates JSON request and returns typed response', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'test-audio-key-12345';

        // Mock successful API response
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(json_encode([
            'job_id' => 'job-tts-456',
            'providers' => ['amazon'],
            'submitted_at' => '2024-11-09 13:00:00',
        ]));
        $response->shouldReceive('getBody')->andReturn($stream);

        // Verify JSON request with Content-Type header
        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request): bool {
                $contentType = $request->getHeader('Content-Type');
                return !empty($contentType) && in_array('application/json', $contentType, true);
            }))
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new AudioResource($client);

        $requestDTO = new TextToSpeechAsyncRequest(
            text: 'Hello async world',
            providers: [ServiceProviderEnum::AMAZON],
            language: 'en',
        );

        $result = $resource->textToSpeechAsync($requestDTO);

        expect($result)->toBeInstanceOf(TextToSpeechAsyncResponse::class)
            ->and($result->jobId)->toBe('job-tts-456')
            ->and($result->providers)->toBe(['amazon'])
            ->and($result->submittedAt)->toBeInstanceOf(DateTimeImmutable::class);
    });
});

describe('Feature: AudioResource - getBasePath()', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('getBasePath returns /v2/audio', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: 'test-key',
        );

        $resource = new AudioResource($client);

        expect($resource->getBasePath())->toBe('/v2/audio');
    });
});

describe('Feature: AudioResource - Authentication', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('authentication via Bearer token is inherited from AbstractResource', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'secret-bearer-token-xyz';

        // Create temporary test audio file
        $testFilePath = sys_get_temp_dir().'/test_auth_audio.mp3';
        file_put_contents($testFilePath, 'fake audio content');

        // Mock successful API response
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(json_encode([
            'job_id' => 'auth-test-789',
            'providers' => ['openai'],
            'submitted_at' => '2024-11-09 14:00:00',
        ]));
        $response->shouldReceive('getBody')->andReturn($stream);

        // Verify Authorization header with Bearer token
        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request) use ($apiKey): bool {
                $authHeaders = $request->getHeader('Authorization');
                return in_array("Bearer {$apiKey}", $authHeaders, true);
            }))
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new AudioResource($client);

        $requestDTO = new SpeechToTextAsyncRequest(
            file: $testFilePath,
            providers: [ServiceProviderEnum::OPENAI],
            language: 'en',
        );

        $result = $resource->speechToTextAsync($requestDTO);

        expect($result)->toBeInstanceOf(SpeechToTextAsyncResponse::class);

        // Clean up
        unlink($testFilePath);
    });
});
