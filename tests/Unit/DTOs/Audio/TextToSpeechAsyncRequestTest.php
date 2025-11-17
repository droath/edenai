<?php

declare(strict_types=1);

use Droath\Edenai\Enums\VoiceOptionEnum;
use Droath\Edenai\Enums\ServiceProviderEnum;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncRequest;

describe('TextToSpeechAsyncRequest', function (): void {
    test('DTO creation with valid text', function (): void {
        $request = new TextToSpeechAsyncRequest(
            text: 'Hello, async world!',
            providers: [ServiceProviderEnum::GOOGLE, ServiceProviderEnum::AMAZON],
            language: 'en',
        );

        expect($request->text)->toBe('Hello, async world!')
            ->and($request->providers)->toBe([ServiceProviderEnum::GOOGLE, ServiceProviderEnum::AMAZON])
            ->and($request->language)->toBe('en')
            ->and($request->option)->toBeNull()
            ->and($request->audioFormat)->toBeNull()
            ->and($request->rate)->toBeNull()
            ->and($request->pitch)->toBeNull()
            ->and($request->volume)->toBeNull();
    });

    test('identical structure to TextToSpeechRequest', function (): void {
        $request = new TextToSpeechAsyncRequest(
            text: 'Async conversion test',
            providers: [ServiceProviderEnum::DEEPGRAM],
            language: 'fr',
            option: 'FEMALE',
            audioFormat: 'wav',
            rate: 1,
            pitch: 1,
            volume: 1,
        );

        expect($request->text)->toBe('Async conversion test')
            ->and($request->option)->toBe('FEMALE')
            ->and($request->audioFormat)->toBe('wav')
            ->and($request->rate)->toBe(1)
            ->and($request->pitch)->toBe(1)
            ->and($request->volume)->toBe(1);
    });

    test('toArray includes all non-null properties', function (): void {
        $request = new TextToSpeechAsyncRequest(
            text: 'Complete async request',
            providers: [ServiceProviderEnum::MICROSOFT, ServiceProviderEnum::AZURE],
            language: 'es',
            option: 'MALE',
            audioFormat: 'flac',
            rate: 1,
            pitch: 1,
            volume: 1,
        );

        $array = $request->toArray();

        expect($array)->toHaveKey('text')
            ->and($array)->toHaveKey('providers')
            ->and($array)->toHaveKey('language')
            ->and($array)->toHaveKey('option')
            ->and($array)->toHaveKey('audio_format')
            ->and($array)->toHaveKey('rate')
            ->and($array)->toHaveKey('pitch')
            ->and($array)->toHaveKey('volume')
            ->and($array['text'])->toBe('Complete async request')
            ->and($array['providers'])->toBe(['microsoft', 'azure'])
            ->and($array['language'])->toBe('es')
            ->and($array['rate'])->toBe(1)
            ->and($array['pitch'])->toBe(1)
            ->and($array['volume'])->toBe(1);
    });

    test('toArray excludes null optional parameters', function (): void {
        $request = new TextToSpeechAsyncRequest(
            text: 'Minimal async request',
            providers: [ServiceProviderEnum::OPENAI],
            language: 'en',
        );

        $array = $request->toArray();

        expect($array)->toHaveKey('text')
            ->and($array)->toHaveKey('providers')
            ->and($array)->toHaveKey('language')
            ->and($array)->toHaveKey('rate')
            ->and($array)->toHaveKey('pitch')
            ->and($array)->toHaveKey('volume')
            ->and($array)->not->toHaveKey('option')
            ->and($array)->not->toHaveKey('audio_format')
            ->and($array['rate'])->toBe(0)
            ->and($array['pitch'])->toBe(0)
            ->and($array['volume'])->toBe(0);
    });

    test('validation for empty text string', function (): void {
        expect(fn () => new TextToSpeechAsyncRequest(
            text: '',
            providers: [ServiceProviderEnum::REV_AI],
            language: 'en',
        ))->toThrow(InvalidArgumentException::class, 'Text cannot be empty');
    });

    test('default language is en', function (): void {
        $request = new TextToSpeechAsyncRequest(
            text: 'Default language test',
            providers: [ServiceProviderEnum::ASSEMBLY_AI],
        );

        expect($request->language)->toBe('en')
            ->and($request->toArray()['language'])->toBe('en');
    });

    test('providers array serializes correctly', function (): void {
        $request = new TextToSpeechAsyncRequest(
            text: 'Multi-provider async',
            providers: [
                ServiceProviderEnum::GOOGLE,
                ServiceProviderEnum::AMAZON,
                ServiceProviderEnum::SPEECHMATICS,
            ],
            language: 'de',
        );

        $array = $request->toArray();

        expect($array['providers'])->toBe(['google', 'amazon', 'speechmatics'])
            ->and($array['language'])->toBe('de');
    });

    test('settings parameter is excluded from toArray when null', function (): void {
        $request = new TextToSpeechAsyncRequest(
            text: 'Async without settings',
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
            settings: null,
        );

        $array = $request->toArray();

        expect($array)->not->toHaveKey('settings');
    });

    test('settings parameter is included in toArray when provided', function (): void {
        $settings = [
            'google' => 'en-US-Neural2-C',
            'amazon' => 'neural',
        ];

        $request = new TextToSpeechAsyncRequest(
            text: 'Async with settings',
            providers: [ServiceProviderEnum::GOOGLE, ServiceProviderEnum::AMAZON],
            language: 'en',
            settings: $settings,
        );

        $array = $request->toArray();

        expect($array)->toHaveKey('settings')
            ->and($array['settings'])->toBe($settings)
            ->and($array['settings']['google'])->toBe('en-US-Neural2-C')
            ->and($array['settings']['amazon'])->toBe('neural');
    });

    test('settings parameter with single provider in async request', function (): void {
        $request = new TextToSpeechAsyncRequest(
            text: 'Single provider async settings',
            providers: [ServiceProviderEnum::MICROSOFT],
            language: 'fr',
            settings: ['microsoft' => 'fr-FR-DeniseNeural'],
        );

        $array = $request->toArray();

        expect($array)->toHaveKey('settings')
            ->and($array['settings'])->toBe(['microsoft' => 'fr-FR-DeniseNeural']);
    });

    test('settings parameter with multiple providers in async request', function (): void {
        $settings = [
            'google' => 'de-DE-Wavenet-F',
            'azure' => 'de-DE-KatjaNeural',
            'ibm' => 'de-DE_BirgitV3Voice',
        ];

        $request = new TextToSpeechAsyncRequest(
            text: 'Multi-provider async settings',
            providers: [ServiceProviderEnum::GOOGLE, ServiceProviderEnum::AZURE, ServiceProviderEnum::IBMWATSON],
            language: 'de',
            settings: $settings,
        );

        $array = $request->toArray();

        expect($array)->toHaveKey('settings')
            ->and($array['settings'])->toBe($settings)
            ->and($array['settings'])->toHaveCount(3);
    });

    test('factory method supports settings parameter', function (): void {
        $settings = [
            'google' => 'es-ES-Neural2-A',
            'microsoft' => 'es-ES-ElviraNeural',
        ];

        $request = TextToSpeechAsyncRequest::make(
            text: 'Async factory with settings',
            providers: [ServiceProviderEnum::GOOGLE, ServiceProviderEnum::MICROSOFT],
            option: VoiceOptionEnum::FEMALE,
            language: 'es',
            settings: $settings,
        );

        expect($request->settings)->toBe($settings)
            ->and($request->option)->toBe('FEMALE')
            ->and($request->toArray())->toHaveKey('settings')
            ->and($request->toArray()['settings'])->toBe($settings);
    });
});
