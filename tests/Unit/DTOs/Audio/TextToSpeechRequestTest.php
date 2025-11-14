<?php

declare(strict_types=1);

use Droath\Edenai\Enums\ServiceProviderEnum;
use Droath\Edenai\DTOs\Audio\TextToSpeechRequest;

describe('TextToSpeechRequest', function (): void {
    test('DTO creation with valid text', function (): void {
        $request = new TextToSpeechRequest(
            text: 'Hello, world!',
            providers: [ServiceProviderEnum::GOOGLE, ServiceProviderEnum::OPENAI],
            language: 'en',
        );

        expect($request->text)->toBe('Hello, world!')
            ->and($request->providers)->toBe([ServiceProviderEnum::GOOGLE, ServiceProviderEnum::OPENAI])
            ->and($request->language)->toBe('en')
            ->and($request->option)->toBeNull()
            ->and($request->audioFormat)->toBeNull()
            ->and($request->rate)->toBeNull()
            ->and($request->pitch)->toBeNull()
            ->and($request->volume)->toBeNull();
    });

    test('toArray includes all non-null properties', function (): void {
        $request = new TextToSpeechRequest(
            text: 'Convert this to speech',
            providers: [ServiceProviderEnum::AMAZON],
            language: 'en',
            option: 'MALE',
            audioFormat: 'mp3',
            rate: 2,
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
            ->and($array['text'])->toBe('Convert this to speech')
            ->and($array['option'])->toBe('MALE')
            ->and($array['audio_format'])->toBe('mp3')
            ->and($array['rate'])->toBe(2)
            ->and($array['pitch'])->toBe(1)
            ->and($array['volume'])->toBe(1);
    });

    test('toArray excludes null optional parameters', function (): void {
        $request = new TextToSpeechRequest(
            text: 'Simple text to speech',
            providers: [ServiceProviderEnum::MICROSOFT],
            language: 'en',
            option: null,
            audioFormat: null,
            rate: null,
            pitch: null,
            volume: null,
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

    test('providers array of ServiceProviderEnum is handled', function (): void {
        $request = new TextToSpeechRequest(
            text: 'Multi-provider test',
            providers: [
                ServiceProviderEnum::GOOGLE,
                ServiceProviderEnum::AMAZON,
                ServiceProviderEnum::MICROSOFT,
                ServiceProviderEnum::DEEPGRAM,
            ],
            language: 'fr',
        );

        $array = $request->toArray();

        expect($array['providers'])->toBe(['google', 'amazon', 'microsoft', 'deepgram'])
            ->and($array['language'])->toBe('fr');
    });

    test('validation for empty text string', function (): void {
        expect(fn () => new TextToSpeechRequest(
            text: '',
            providers: [ServiceProviderEnum::OPENAI],
            language: 'en',
        ))->toThrow(InvalidArgumentException::class, 'Text cannot be empty');
    });

    test('default language is en', function (): void {
        $request = new TextToSpeechRequest(
            text: 'Testing default language',
            providers: [ServiceProviderEnum::AZURE],
        );

        expect($request->language)->toBe('en')
            ->and($request->toArray()['language'])->toBe('en');
    });

    test('partial optional parameters are handled correctly', function (): void {
        $request = new TextToSpeechRequest(
            text: 'Partial parameters test',
            providers: [ServiceProviderEnum::REV_AI],
            language: 'es',
            rate: 1,
            pitch: null,
            volume: 1,
        );

        $array = $request->toArray();

        expect($array)->toHaveKey('rate')
            ->and($array)->toHaveKey('pitch')
            ->and($array)->toHaveKey('volume')
            ->and($array)->not->toHaveKey('option')
            ->and($array['rate'])->toBe(1)
            ->and($array['pitch'])->toBe(0)
            ->and($array['volume'])->toBe(1);
    });

    test('negative rate pitch and volume values are allowed', function (): void {
        // Server-side validation - we just accept any int values
        $request = new TextToSpeechRequest(
            text: 'Testing negative values',
            providers: [ServiceProviderEnum::IBMWATSON],
            rate: -1,
            pitch: -1,
            volume: -1,
        );

        $array = $request->toArray();

        expect($array['rate'])->toBe(-1)
            ->and($array['pitch'])->toBe(-1)
            ->and($array['volume'])->toBe(-1);
    });
});
