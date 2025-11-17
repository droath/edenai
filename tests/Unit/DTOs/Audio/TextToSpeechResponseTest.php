<?php

declare(strict_types=1);

use Droath\Edenai\DTOs\AbstractResponseDTO;
use Droath\Edenai\DTOs\Audio\ProviderResult;
use Droath\Edenai\DTOs\Audio\TextToSpeechResponse;

describe('TextToSpeechResponse', function () {
    test('fromResponse() decodes Base64 audio to raw binary for single provider', function () {
        $rawBinary = 'This is raw audio binary data';
        $base64Audio = base64_encode($rawBinary);

        $responseData = [
            'google' => [
                'audio' => $base64Audio,
                'voice_type' => 1,
                'audio_resource_url' => 'https://example.com/audio.mp3',
                'cost' => 0,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response)->toBeInstanceOf(TextToSpeechResponse::class)
            ->and($response->results)->toBeArray()
            ->and($response->results)->toHaveCount(1)
            ->and($response->results[0])->toBeInstanceOf(ProviderResult::class)
            ->and($response->results[0]->provider)->toBe('google')
            ->and($response->results[0]->audioData)->toBe($rawBinary)
            ->and($response->results[0]->audioData)->not->toBe($base64Audio);
    });

    test('handles multiple providers in response', function () {
        $audioData1 = 'first provider audio';
        $audioData2 = 'second provider audio';

        $responseData = [
            'google' => [
                'audio' => base64_encode($audioData1),
                'voice_type' => 1,
                'cost' => 5,
            ],
            'amazon' => [
                'audio' => base64_encode($audioData2),
                'voice_type' => 2,
                'cost' => 3,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->results)->toHaveCount(2)
            ->and($response->results[0])->toBeInstanceOf(ProviderResult::class)
            ->and($response->results[1])->toBeInstanceOf(ProviderResult::class)
            ->and($response->results[0]->provider)->toBeIn(['google', 'amazon'])
            ->and($response->results[1]->provider)->toBeIn(['google', 'amazon'])
            ->and($response->results[0]->audioData)->not->toBeEmpty()
            ->and($response->results[1]->audioData)->not->toBeEmpty();
    });

    test('preserves all provider results without prioritization', function () {
        $responseData = [
            'openai' => [
                'audio' => base64_encode('openai audio'),
                'voice_type' => 1,
                'cost' => 10,
            ],
            'deepgram' => [
                'audio' => base64_encode('deepgram audio'),
                'voice_type' => 2,
                'cost' => 8,
            ],
            'google' => [
                'audio' => base64_encode('google audio'),
                'voice_type' => 1,
                'cost' => 5,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->results)->toHaveCount(3);

        $providers = array_map(fn (ProviderResult $result) => $result->provider, $response->results);
        expect($providers)->toContain('openai')
            ->and($providers)->toContain('deepgram')
            ->and($providers)->toContain('google');
    });

    test('voiceType is extracted correctly from provider results', function () {
        $responseData = [
            'amazon' => [
                'audio' => base64_encode('audio data'),
                'voice_type' => 2,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->results[0]->voiceType)->toBe(2)
            ->and($response->results[0]->voiceType)->toBeInt();
    });

    test('voiceType handles null values', function () {
        $responseData = [
            'google' => [
                'audio' => base64_encode('audio data'),
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->results[0]->voiceType)->toBeNull();
    });

    test('audioResourceUrl is extracted correctly', function () {
        $url = 'https://edenai.example.com/audio/12345.mp3';
        $responseData = [
            'openai' => [
                'audio' => base64_encode('audio data'),
                'audio_resource_url' => $url,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->results[0]->audioResourceUrl)->toBe($url);
    });

    test('cost is extracted correctly', function () {
        $responseData = [
            'deepgram' => [
                'audio' => base64_encode('audio data'),
                'cost' => 15,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->results[0]->cost)->toBe(15)
            ->and($response->results[0]->cost)->toBeInt();
    });

    test('lenient handling of unknown response keys', function () {
        $responseData = [
            'google' => [
                'audio' => base64_encode('audio data'),
                'voice_type' => 1,
                'unknown_field' => 'should be ignored',
                'extra_metadata' => ['nested' => 'data'],
                'provider_info' => 'extra data',
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response)->toBeInstanceOf(TextToSpeechResponse::class)
            ->and($response->results)->toHaveCount(1)
            ->and($response->results[0]->voiceType)->toBe(1);
    });

    test('stores audio in memory-efficient string format', function () {
        $rawBinary = str_repeat('audio', 1000);
        $base64Audio = base64_encode($rawBinary);

        $responseData = [
            'microsoft' => [
                'audio' => $base64Audio,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->results[0]->audioData)->toBeString()
            ->and(mb_strlen($response->results[0]->audioData))->toBe(mb_strlen($rawBinary))
            ->and($response->results[0]->audioData)->toBe($rawBinary);
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

    test('results property uses readonly modifier for immutability', function () {
        $responseData = [
            'deepgram' => [
                'audio' => base64_encode('audio data'),
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        $reflection = new ReflectionClass($response);
        $resultsProp = $reflection->getProperty('results');

        expect($resultsProp->isReadOnly())->toBeTrue();
    });

    test('ProviderResult objects have readonly properties', function () {
        $responseData = [
            'google' => [
                'audio' => base64_encode('audio data'),
                'voice_type' => 1,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        $reflection = new ReflectionClass($response->results[0]);

        expect($reflection->isReadOnly())->toBeTrue();
    });

    test('decodes complex Base64 audio data correctly', function () {
        $rawBinary = pack('C*', ...array_map(fn () => rand(0, 255), range(1, 100)));
        $base64Audio = base64_encode($rawBinary);

        $responseData = [
            'azure' => [
                'audio' => $base64Audio,
                'voice_type' => 1,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->results[0]->audioData)->toBe($rawBinary)
            ->and($response->results[0]->provider)->toBe('azure')
            ->and($response->results[0]->voiceType)->toBe(1);
    });

    test('handles empty response gracefully', function () {
        $responseData = [];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->results)->toBeArray()
            ->and($response->results)->toHaveCount(0)
            ->and($response->results)->toBeEmpty();
    });

    test('filters out non-array provider data', function () {
        $responseData = [
            'google' => [
                'audio' => base64_encode('valid audio'),
            ],
            'invalid_provider' => 'not an array',
            'amazon' => [
                'audio' => base64_encode('also valid'),
            ],
            'another_invalid' => 12345,
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->results)->toHaveCount(2);

        $providers = array_map(fn (ProviderResult $result) => $result->provider, $response->results);
        expect($providers)->toContain('google')
            ->and($providers)->toContain('amazon')
            ->and($providers)->not->toContain('invalid_provider')
            ->and($providers)->not->toContain('another_invalid');
    });

    test('iteration through all results works properly', function () {
        $responseData = [
            'openai' => [
                'audio' => base64_encode('openai audio'),
                'cost' => 5,
            ],
            'deepgram' => [
                'audio' => base64_encode('deepgram audio'),
                'cost' => 3,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        $totalCost = 0;
        foreach ($response->results as $result) {
            expect($result)->toBeInstanceOf(ProviderResult::class);
            $totalCost += $result->cost ?? 0;
        }

        expect($totalCost)->toBe(8);
    });

    test('provider names are preserved correctly', function () {
        $responseData = [
            'openai' => ['audio' => base64_encode('audio1')],
            'deepgram' => ['audio' => base64_encode('audio2')],
            'google' => ['audio' => base64_encode('audio3')],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        $providers = array_map(fn (ProviderResult $result) => $result->provider, $response->results);

        expect($providers)->toHaveCount(3)
            ->and($providers)->toContain('openai')
            ->and($providers)->toContain('deepgram')
            ->and($providers)->toContain('google');
    });

    test('handles real-world API response structure', function () {
        $responseData = [
            'openai' => [
                'audio' => base64_encode('openai audio data'),
                'voice_type' => 1,
                'audio_resource_url' => 'https://api.edenai.run/v2/audio/openai/123.mp3',
                'cost' => 0,
            ],
            'deepgram' => [
                'audio' => base64_encode('deepgram audio data'),
                'voice_type' => 1,
                'audio_resource_url' => 'https://api.edenai.run/v2/audio/deepgram/456.mp3',
                'cost' => 0,
            ],
        ];

        $response = TextToSpeechResponse::fromResponse($responseData);

        expect($response->results)->toHaveCount(2);

        foreach ($response->results as $result) {
            expect($result->provider)->toBeIn(['openai', 'deepgram'])
                ->and($result->audioData)->not->toBeEmpty()
                ->and($result->voiceType)->toBe(1)
                ->and($result->audioResourceUrl)->toContain('https://api.edenai.run')
                ->and($result->cost)->toBe(0);
        }
    });
});
