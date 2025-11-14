<?php

declare(strict_types=1);

use Droath\Edenai\DTOs\AbstractResponseDTO;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncResponse;

describe('TextToSpeechAsyncResponse', function () {
    test('fromResponse() parses API response correctly', function () {
        $responseData = [
            'public_id' => 'tts_job_12345',
            'results' => [
                'google' => ['status' => 'pending'],
                'amazon' => ['status' => 'pending'],
            ],
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        expect($response)->toBeInstanceOf(TextToSpeechAsyncResponse::class)
            ->and($response->jobId)->toBe('tts_job_12345')
            ->and($response->providers)->toBe(['google', 'amazon'])
            ->and($response->submittedAt)->toBeInstanceOf(DateTimeImmutable::class);
    });

    test('identical structure to SpeechToTextAsyncResponse', function () {
        $responseData = [
            'public_id' => 'tts_job_67890',
            'results' => [
                'microsoft' => ['status' => 'pending'],
                'openai' => ['status' => 'pending'],
            ],
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        // Verify same property structure
        $reflection = new ReflectionClass($response);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $propertyNames = array_map(fn ($prop) => $prop->getName(), $properties);

        expect($propertyNames)->toHaveCount(3)
            ->and($propertyNames)->toContain('jobId')
            ->and($propertyNames)->toContain('providers')
            ->and($propertyNames)->toContain('submittedAt');
    });

    test('generates current timestamp for submittedAt', function () {
        $beforeTime = new DateTimeImmutable();

        $responseData = [
            'public_id' => 'tts_job_timestamp',
            'results' => [
                'deepgram' => ['status' => 'pending'],
            ],
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        $afterTime = new DateTimeImmutable();

        expect($response->submittedAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($response->submittedAt->getTimestamp())->toBeGreaterThanOrEqual($beforeTime->getTimestamp())
            ->and($response->submittedAt->getTimestamp())->toBeLessThanOrEqual($afterTime->getTimestamp());
    });

    test('lenient handling of unknown response keys', function () {
        $responseData = [
            'public_id' => 'tts_job_lenient',
            'results' => [
                'google' => ['status' => 'pending'],
                'azure' => ['status' => 'pending'],
            ],
            'unknown_field' => 'should be ignored',
            'extra_data' => ['nested' => 'value'],
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        expect($response)->toBeInstanceOf(TextToSpeechAsyncResponse::class)
            ->and($response->jobId)->toBe('tts_job_lenient')
            ->and($response->providers)->toBe(['google', 'azure']);
    });

    test('extends AbstractResponseDTO', function () {
        $responseData = [
            'public_id' => 'tts_job_inheritance',
            'results' => [
                'google' => ['status' => 'pending'],
            ],
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        expect($response)->toBeInstanceOf(AbstractResponseDTO::class);
    });

    test('properties use readonly modifier for immutability', function () {
        $responseData = [
            'public_id' => 'tts_job_readonly',
            'results' => [
                'amazon' => ['status' => 'pending'],
            ],
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        // Verify each property is declared as readonly
        $reflection = new ReflectionClass($response);

        $jobIdProp = $reflection->getProperty('jobId');
        $providersProp = $reflection->getProperty('providers');
        $submittedAtProp = $reflection->getProperty('submittedAt');

        expect($jobIdProp->isReadOnly())->toBeTrue()
            ->and($providersProp->isReadOnly())->toBeTrue()
            ->and($submittedAtProp->isReadOnly())->toBeTrue();
    });

    test('handles multiple providers correctly', function () {
        $responseData = [
            'public_id' => 'tts_job_multi',
            'results' => [
                'google' => ['status' => 'pending'],
                'amazon' => ['status' => 'pending'],
                'microsoft' => ['status' => 'pending'],
                'openai' => ['status' => 'pending'],
                'deepgram' => ['status' => 'pending'],
            ],
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        expect($response->providers)->toBeArray()
            ->and($response->providers)->toHaveCount(5)
            ->and($response->providers)->toBe(['google', 'amazon', 'microsoft', 'openai', 'deepgram']);
    });

    test('contains only job tracking metadata', function () {
        $responseData = [
            'public_id' => 'tts_job_metadata_only',
            'results' => [
                'google' => ['status' => 'pending'],
            ],
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        // Verify DTO contains exactly three properties (job metadata)
        $reflection = new ReflectionClass($response);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        expect($properties)->toHaveCount(3);
    });

    test('handles empty results array', function () {
        $responseData = [
            'public_id' => 'tts_job_empty',
            'results' => [],
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        expect($response->jobId)->toBe('tts_job_empty')
            ->and($response->providers)->toBe([]);
    });

    test('handles missing public_id field', function () {
        $responseData = [
            'results' => [
                'google' => ['status' => 'pending'],
            ],
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        expect($response->jobId)->toBe('')
            ->and($response->providers)->toBe(['google']);
    });
});
