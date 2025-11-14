<?php

declare(strict_types=1);

use Droath\Edenai\DTOs\AbstractResponseDTO;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncResponse;

describe('SpeechToTextAsyncResponse', function () {
    test('fromResponse() parses API response correctly', function () {
        $responseData = [
            'public_id' => 'job_12345',
            'status' => 'finished',
            'results' => [
                'google' => ['status' => 'success'],
                'amazon' => ['status' => 'success'],
            ],
        ];

        $response = SpeechToTextAsyncResponse::fromResponse($responseData);

        expect($response)->toBeInstanceOf(SpeechToTextAsyncResponse::class)
            ->and($response->jobId)->toBe('job_12345')
            ->and($response->providers)->toBe(['google', 'amazon'])
            ->and($response->submittedAt)->toBeInstanceOf(DateTimeImmutable::class);
    });

    test('timestamp generates as current time', function () {
        $beforeTime = new DateTimeImmutable();

        $responseData = [
            'public_id' => 'job_67890',
            'results' => [
                'microsoft' => ['status' => 'pending'],
            ],
        ];

        $response = SpeechToTextAsyncResponse::fromResponse($responseData);

        $afterTime = new DateTimeImmutable();

        expect($response->submittedAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($response->submittedAt->getTimestamp())->toBeGreaterThanOrEqual($beforeTime->getTimestamp())
            ->and($response->submittedAt->getTimestamp())->toBeLessThanOrEqual($afterTime->getTimestamp());
    });

    test('lenient handling of unknown response keys', function () {
        $responseData = [
            'public_id' => 'job_abc123',
            'status' => 'finished',
            'results' => [
                'openai' => ['status' => 'success'],
                'deepgram' => ['status' => 'success'],
            ],
            'unknown_field_1' => 'should be ignored',
            'unknown_field_2' => 12345,
            'extra_metadata' => ['nested' => 'data'],
        ];

        $response = SpeechToTextAsyncResponse::fromResponse($responseData);

        // Should parse successfully and ignore unknown keys
        expect($response)->toBeInstanceOf(SpeechToTextAsyncResponse::class)
            ->and($response->jobId)->toBe('job_abc123')
            ->and($response->providers)->toBe(['openai', 'deepgram']);
    });

    test('properties use readonly modifier for immutability', function () {
        $responseData = [
            'public_id' => 'job_test',
            'results' => [
                'google' => ['status' => 'pending'],
            ],
        ];

        $response = SpeechToTextAsyncResponse::fromResponse($responseData);

        // Verify each property is declared as readonly in constructor
        $reflection = new ReflectionClass($response);

        $jobIdProp = $reflection->getProperty('jobId');
        $providersProp = $reflection->getProperty('providers');
        $submittedAtProp = $reflection->getProperty('submittedAt');

        expect($jobIdProp->isReadOnly())->toBeTrue()
            ->and($providersProp->isReadOnly())->toBeTrue()
            ->and($submittedAtProp->isReadOnly())->toBeTrue();
    });

    test('extends AbstractResponseDTO', function () {
        $responseData = [
            'public_id' => 'job_inheritance_test',
            'results' => [
                'amazon' => ['status' => 'pending'],
            ],
        ];

        $response = SpeechToTextAsyncResponse::fromResponse($responseData);

        expect($response)->toBeInstanceOf(AbstractResponseDTO::class);
    });

    test('handles array of provider strings correctly', function () {
        $responseData = [
            'public_id' => 'job_multi_provider',
            'results' => [
                'google' => ['status' => 'pending'],
                'amazon' => ['status' => 'pending'],
                'microsoft' => ['status' => 'pending'],
                'openai' => ['status' => 'pending'],
                'deepgram' => ['status' => 'pending'],
            ],
        ];

        $response = SpeechToTextAsyncResponse::fromResponse($responseData);

        expect($response->providers)->toBeArray()
            ->and($response->providers)->toHaveCount(5)
            ->and($response->providers)->toBe(['google', 'amazon', 'microsoft', 'openai', 'deepgram']);
    });

    test('handles single provider in array', function () {
        $responseData = [
            'public_id' => 'job_single_provider',
            'results' => [
                'google' => ['status' => 'pending'],
            ],
        ];

        $response = SpeechToTextAsyncResponse::fromResponse($responseData);

        expect($response->providers)->toBeArray()
            ->and($response->providers)->toHaveCount(1)
            ->and($response->providers[0])->toBe('google');
    });

    test('contains only job tracking metadata without echoed request parameters', function () {
        $responseData = [
            'public_id' => 'job_metadata_test',
            'results' => [
                'google' => ['status' => 'pending'],
            ],
        ];

        $response = SpeechToTextAsyncResponse::fromResponse($responseData);

        // Verify DTO contains only these three properties (job tracking metadata)
        $reflection = new ReflectionClass($response);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        expect($properties)->toHaveCount(3);

        $propertyNames = array_map(fn ($prop) => $prop->getName(), $properties);
        expect($propertyNames)->toContain('jobId')
            ->and($propertyNames)->toContain('providers')
            ->and($propertyNames)->toContain('submittedAt');
    });
});
