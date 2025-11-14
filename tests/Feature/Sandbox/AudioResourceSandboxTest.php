<?php

declare(strict_types=1);

namespace Droath\Edenai\Tests\Feature\Sandbox;

use Exception;
use DateTimeImmutable;
use GuzzleHttp\Client;
use InvalidArgumentException;
use Droath\Edenai\Http\ApiClient;
use Droath\Edenai\Resources\AudioResource;
use Droath\Edenai\Enums\ServiceProviderEnum;
use Droath\Edenai\DTOs\Audio\TextToSpeechRequest;
use Droath\Edenai\Exceptions\ValidationException;
use Droath\Edenai\DTOs\Audio\TextToSpeechResponse;
use Droath\Edenai\Exceptions\AuthorizationException;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncRequest;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncRequest;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncResponse;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncResponse;

/**
 * Sandbox functional tests for AudioResource using real EdenAI API endpoints.
 *
 * These tests verify end-to-end functionality with actual API credentials.
 * All tests are marked with @group sandbox-test and require EDENAI_API_KEY
 * environment variable to be set.
 *
 * Run with: vendor/bin/pest --group=sandbox-test
 *
 * @group sandbox-test
 */
beforeEach(function (): void {
    // Skip all sandbox tests if API key is not set
    if (! getenv('EDENAI_API_KEY')) {
        $this->markTestSkipped('Sandbox tests require EDENAI_API_KEY environment variable to be set');
    }

    // Initialize ApiClient with real base URL and API key
    $httpClient = new Client();
    $this->apiClient = new ApiClient(
        httpClient: $httpClient,
        baseUrl: 'https://api.edenai.run',
        apiKey: getenv('EDENAI_API_KEY'),
    );

    // Create AudioResource instance for all tests
    $this->audioResource = new AudioResource($this->apiClient);
});

/**
 * Dataset for testing multiple providers (Google and Amazon).
 */
dataset('providers', [
    'google provider' => [ServiceProviderEnum::GOOGLE],
    'amazon provider' => [ServiceProviderEnum::AMAZON],
]);

describe('Sandbox: speechToTextAsync() success scenarios', function (): void {
    test('speechToTextAsync creates job with valid audio file', function (ServiceProviderEnum $provider): void {
        $audioFilePath = __DIR__.'/../../Fixtures/audio/valid-speech.mp3';

        $request = new SpeechToTextAsyncRequest(
            file: $audioFilePath,
            providers: [$provider],
            language: 'en',
        );

        $response = $this->audioResource->speechToTextAsync($request);

        expect($response)->toBeInstanceOf(SpeechToTextAsyncResponse::class)
            ->and($response->jobId)->toBeString()
            ->and($response->jobId)->not->toBeEmpty()
            ->and($response->providers)->toBeArray()
            ->and($response->providers)->toContain($provider->value)
            ->and($response->submittedAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($response->submittedAt->getTimestamp())->toBeLessThanOrEqual(time());
    })->with('providers');
});

describe('Sandbox: textToSpeech() success scenarios', function (): void {
    test('textToSpeech generates audio data synchronously', function (ServiceProviderEnum $provider): void {
        $request = new TextToSpeechRequest(
            text: 'Hello world',
            providers: [$provider],
            language: 'en',
            option: 'FEMALE', // Required by some providers like Google
        );

        $response = $this->audioResource->textToSpeech($request);

        expect($response)->toBeInstanceOf(TextToSpeechResponse::class)
            ->and($response->audioData)->toBeString()
            ->and($response->audioData)->not->toBeEmpty()
            ->and($response->contentType)->toBeString()
            ->and($response->contentType)->toContain('audio/');

        // Verify audio data can be written to temporary file
        $tempFile = sys_get_temp_dir().'/sandbox_audio_test_'.uniqid().'.mp3';
        $bytesWritten = file_put_contents($tempFile, $response->audioData);

        expect($bytesWritten)->toBeGreaterThan(0);

        // Clean up
        unlink($tempFile);
    })->with('providers');

    test('textToSpeech accepts integer audio parameters', function (): void {
        $request = new TextToSpeechRequest(
            text: 'Testing integer parameters',
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
            option: 'FEMALE',
            rate: 1,
            pitch: 0,
            volume: 1,
        );

        $response = $this->audioResource->textToSpeech($request);

        expect($response)->toBeInstanceOf(TextToSpeechResponse::class)
            ->and($response->audioData)->toBeString()
            ->and($response->audioData)->not->toBeEmpty();
    });
})->group('sandbox-test');

describe('Sandbox: textToSpeechAsync() success scenarios', function (): void {
    test('textToSpeechAsync creates job for audio generation', function (): void {
        // Note: Google does not support async text-to-speech, only Amazon does
        $request = new TextToSpeechAsyncRequest(
            text: 'Hello async world',
            providers: [ServiceProviderEnum::AMAZON],
            language: 'en',
            option: 'FEMALE',
        );

        $response = $this->audioResource->textToSpeechAsync($request);

        expect($response)->toBeInstanceOf(TextToSpeechAsyncResponse::class)
            ->and($response->jobId)->toBeString()
            ->and($response->jobId)->not->toBeEmpty()
            ->and($response->jobId)->toMatch('/^[a-f0-9\-]+$/i') // UUID or hex format
            ->and($response->providers)->toBeArray()
            ->and($response->submittedAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($response->submittedAt->getTimestamp())->toBeLessThanOrEqual(time());
    });
})->group('sandbox-test');

describe('Sandbox: Authentication error scenarios', function (): void {
    test('invalid API key throws AuthorizationException', function (): void {
        // Create ApiClient with invalid API key
        $httpClient = new Client();
        $invalidClient = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: 'invalid-key-12345',
        );

        $invalidResource = new AudioResource($invalidClient);

        $request = new TextToSpeechRequest(
            text: 'Test authentication',
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
            option: 'FEMALE',
        );

        expect(fn () => $invalidResource->textToSpeech($request))
            ->toThrow(AuthorizationException::class);
    });
})->group('sandbox-test');

describe('Sandbox: Validation error scenarios', function (): void {
    test('malformed audio file throws exception', function (): void {
        $malformedFilePath = __DIR__.'/../../Fixtures/audio/malformed.mp3';

        $request = new SpeechToTextAsyncRequest(
            file: $malformedFilePath,
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
        );

        // The API should reject malformed files
        // This may throw ValidationException or other API-level exception
        expect(fn () => $this->audioResource->speechToTextAsync($request))
            ->toThrow(Exception::class);
    });

    test('empty text parameter throws InvalidArgumentException', function (): void {
        expect(fn () => new TextToSpeechRequest(
            text: '', // Empty text should fail validation
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
        ))->toThrow(InvalidArgumentException::class);
    });
})->group('sandbox-test');
