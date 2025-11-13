<?php

declare(strict_types=1);

use Droath\Edenai\DTOs\AbstractResponseDTO;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncResponse;

describe('TextToSpeechAsyncResponse', function () {
    test('fromResponse() parses API response correctly', function () {
        $responseData = [
            'job_id' => 'tts_job_12345',
            'providers' => ['google', 'amazon'],
            'submitted_at' => '2024-01-15 10:30:00',
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        expect($response)->toBeInstanceOf(TextToSpeechAsyncResponse::class)
            ->and($response->jobId)->toBe('tts_job_12345')
            ->and($response->providers)->toBe(['google', 'amazon'])
            ->and($response->submittedAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($response->submittedAt->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
    });

    test('identical structure to SpeechToTextAsyncResponse', function () {
        $responseData = [
            'job_id' => 'tts_job_67890',
            'providers' => ['microsoft', 'openai'],
            'submitted_at' => '2024-03-20 14:45:30',
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

    test('timestamp transformation to DateTimeImmutable', function () {
        $responseData = [
            'job_id' => 'tts_job_timestamp',
            'providers' => ['deepgram'],
            'submitted_at' => '2024-06-15 18:22:33',
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        expect($response->submittedAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($response->submittedAt->format('Y-m-d H:i:s'))->toBe('2024-06-15 18:22:33');
    });

    test('lenient handling of unknown response keys', function () {
        $responseData = [
            'job_id' => 'tts_job_lenient',
            'providers' => ['google', 'azure'],
            'submitted_at' => '2024-02-10 08:15:45',
            'unknown_field' => 'should be ignored',
            'extra_data' => ['nested' => 'value'],
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        expect($response)->toBeInstanceOf(TextToSpeechAsyncResponse::class)
            ->and($response->jobId)->toBe('tts_job_lenient');
    });

    test('extends AbstractResponseDTO', function () {
        $responseData = [
            'job_id' => 'tts_job_inheritance',
            'providers' => ['google'],
            'submitted_at' => '2024-01-01 00:00:00',
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        expect($response)->toBeInstanceOf(AbstractResponseDTO::class);
    });

    test('properties use readonly modifier for immutability', function () {
        $responseData = [
            'job_id' => 'tts_job_readonly',
            'providers' => ['amazon'],
            'submitted_at' => '2024-01-01 12:00:00',
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
            'job_id' => 'tts_job_multi',
            'providers' => ['google', 'amazon', 'microsoft', 'openai', 'deepgram'],
            'submitted_at' => '2024-01-15 10:30:00',
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        expect($response->providers)->toBeArray()
            ->and($response->providers)->toHaveCount(5)
            ->and($response->providers)->toBe(['google', 'amazon', 'microsoft', 'openai', 'deepgram']);
    });

    test('contains only job tracking metadata', function () {
        $responseData = [
            'job_id' => 'tts_job_metadata_only',
            'providers' => ['google'],
            'submitted_at' => '2024-01-15 10:30:00',
        ];

        $response = TextToSpeechAsyncResponse::fromResponse($responseData);

        // Verify DTO contains exactly three properties (job metadata)
        $reflection = new ReflectionClass($response);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        expect($properties)->toHaveCount(3);
    });
});
