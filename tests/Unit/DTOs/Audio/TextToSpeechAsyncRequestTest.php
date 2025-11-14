<?php

declare(strict_types=1);

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
});
