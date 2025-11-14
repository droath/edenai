<?php

declare(strict_types=1);

use Droath\Edenai\DTOs\AbstractResponseDTO;
use Droath\Edenai\DTOs\Audio\TextToSpeechResponse;

describe('TextToSpeechResponse', function () {
    test('fromResponse() decodes Base64 audio to raw binary', function () {
        $rawBinary = 'This is raw audio binary data';
        $base64Audio = base64_encode($rawBinary);

        $responseData = [
            'google' => [
                'audio' => $base64Audio,
                'content_type' => 'audio/mpeg',
                'duration' => 3.5,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->audioData)->toBe($rawBinary)
            ->and($response->audioData)->not->toBe($base64Audio);
    });

    test('contentType defaults to audio/mpeg', function () {
        $responseData = [
            'amazon' => [
                'audio' => base64_encode('audio data'),
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->contentType)->toBe('audio/mpeg');
    });

    test('extracts audio from first provider in response', function () {
        $audioData1 = 'first provider audio';
        $audioData2 = 'second provider audio';

        $responseData = [
            'google' => [
                'audio' => base64_encode($audioData1),
                'duration' => 2.5,
            ],
            'amazon' => [
                'audio' => base64_encode($audioData2),
                'duration' => 3.0,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        // Should use first provider (google in this case)
        expect($response->audioData)->toBe($audioData1)
            ->and($response->duration)->toBe(2.5);
    });

    test('duration handles null values', function () {
        $responseData = [
            'google' => [
                'audio' => base64_encode('audio data'),
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->duration)->toBeNull();
    });

    test('duration handles float values', function () {
        $responseData = [
            'amazon' => [
                'audio' => base64_encode('audio data'),
                'duration' => 5.75,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->duration)->toBe(5.75)
            ->and($response->duration)->toBeFloat();
    });

    test('lenient handling of unknown response keys', function () {
        $responseData = [
            'google' => [
                'audio' => base64_encode('audio data'),
                'duration' => 2.5,
                'unknown_field' => 'should be ignored',
                'extra_metadata' => ['nested' => 'data'],
                'provider_info' => 'extra data',
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        // Should parse successfully and ignore unknown keys
        expect($response)->toBeInstanceOf(TextToSpeechResponse::class)
            ->and($response->contentType)->toBe('audio/mpeg')
            ->and($response->duration)->toBe(2.5);
    });

    test('stores audio in memory-efficient string format', function () {
        $rawBinary = str_repeat('audio', 1000); // Large-ish audio data
        $base64Audio = base64_encode($rawBinary);

        $responseData = [
            'microsoft' => [
                'audio' => $base64Audio,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        // Verify it's a string ready for file writing
        expect($response->audioData)->toBeString()
            ->and(mb_strlen($response->audioData))->toBe(mb_strlen($rawBinary))
            ->and($response->audioData)->toBe($rawBinary);
    });

    test('extends AbstractResponseDTO', function () {
        $responseData = [
            'openai' => [
                'audio' => base64_encode('audio data'),
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response)->toBeInstanceOf(AbstractResponseDTO::class);
    });

    test('properties use readonly modifier for immutability', function () {
        $responseData = [
            'deepgram' => [
                'audio' => base64_encode('audio data'),
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        // Verify each property is declared as readonly
        $reflection = new ReflectionClass($response);

        $audioDataProp = $reflection->getProperty('audioData');
        $contentTypeProp = $reflection->getProperty('contentType');
        $durationProp = $reflection->getProperty('duration');

        expect($audioDataProp->isReadOnly())->toBeTrue()
            ->and($contentTypeProp->isReadOnly())->toBeTrue()
            ->and($durationProp->isReadOnly())->toBeTrue();
    });

    test('decodes complex Base64 audio data correctly', function () {
        // Simulate real audio file bytes
        $rawBinary = pack('C*', ...array_map(fn () => rand(0, 255), range(1, 100)));
        $base64Audio = base64_encode($rawBinary);

        $responseData = [
            'azure' => [
                'audio' => $base64Audio,
                'duration' => 1.5,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        // Verify exact binary match after decode
        expect($response->audioData)->toBe($rawBinary)
            ->and($response->contentType)->toBe('audio/mpeg')
            ->and($response->duration)->toBe(1.5);
    });

    test('handles empty response gracefully', function () {
        $responseData = [];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->audioData)->toBe('')
            ->and($response->contentType)->toBe('audio/mpeg')
            ->and($response->duration)->toBeNull();
    });
});
