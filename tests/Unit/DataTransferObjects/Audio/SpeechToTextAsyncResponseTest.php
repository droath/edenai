<?php

declare(strict_types=1);

use Droath\Edenai\DTOs\AbstractResponseDTO;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncResponse;

describe('SpeechToTextAsyncResponse', function () {
    test('fromResponse() parses API response correctly', function () {
        $responseData = [
            'job_id' => 'job_12345',
            'providers' => ['google', 'amazon'],
            'submitted_at' => '2024-01-15 10:30:00',
        ];

        $response = SpeechToTextAsyncResponse::fromResponse($responseData);

        expect($response)->toBeInstanceOf(SpeechToTextAsyncResponse::class)
            ->and($response->jobId)->toBe('job_12345')
            ->and($response->providers)->toBe(['google', 'amazon'])
            ->and($response->submittedAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($response->submittedAt->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
    });

    test('timestamp string transforms to DateTimeImmutable', function () {
        $responseData = [
            'job_id' => 'job_67890',
            'providers' => ['microsoft'],
            'submitted_at' => '2024-03-20 14:45:30',
        ];

        $response = SpeechToTextAsyncResponse::fromResponse($responseData);

        expect($response->submittedAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($response->submittedAt->format('Y-m-d H:i:s'))->toBe('2024-03-20 14:45:30');
    });

    test('lenient handling of unknown response keys', function () {
        $responseData = [
            'job_id' => 'job_abc123',
            'providers' => ['openai', 'deepgram'],
            'submitted_at' => '2024-02-10 08:15:45',
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
            'job_id' => 'job_test',
            'providers' => ['google'],
            'submitted_at' => '2024-01-01 00:00:00',
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
            'job_id' => 'job_inheritance_test',
            'providers' => ['amazon'],
            'submitted_at' => '2024-01-01 12:00:00',
        ];

        $response = SpeechToTextAsyncResponse::fromResponse($responseData);

        expect($response)->toBeInstanceOf(AbstractResponseDTO::class);
    });

    test('handles array of provider strings correctly', function () {
        $responseData = [
            'job_id' => 'job_multi_provider',
            'providers' => ['google', 'amazon', 'microsoft', 'openai', 'deepgram'],
            'submitted_at' => '2024-01-15 10:30:00',
        ];

        $response = SpeechToTextAsyncResponse::fromResponse($responseData);

        expect($response->providers)->toBeArray()
            ->and($response->providers)->toHaveCount(5)
            ->and($response->providers)->toBe(['google', 'amazon', 'microsoft', 'openai', 'deepgram']);
    });

    test('handles single provider in array', function () {
        $responseData = [
            'job_id' => 'job_single_provider',
            'providers' => ['google'],
            'submitted_at' => '2024-01-15 10:30:00',
        ];

        $response = SpeechToTextAsyncResponse::fromResponse($responseData);

        expect($response->providers)->toBeArray()
            ->and($response->providers)->toHaveCount(1)
            ->and($response->providers[0])->toBe('google');
    });

    test('contains only job tracking metadata without echoed request parameters', function () {
        $responseData = [
            'job_id' => 'job_metadata_test',
            'providers' => ['google'],
            'submitted_at' => '2024-01-15 10:30:00',
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
