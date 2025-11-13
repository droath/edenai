<?php

declare(strict_types=1);

use Droath\Edenai\Enums\ServiceProviderEnum;
use Droath\Edenai\Exceptions\FileUploadException;
use Droath\Edenai\Exceptions\ValidationException;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncRequest;

describe('SpeechToTextAsyncRequest', function (): void {
    beforeEach(function (): void {
        // Create test fixtures directory
        $this->fixturesDir = sys_get_temp_dir().'/edenai_test_'.uniqid();
        mkdir($this->fixturesDir, 0777, true);
    });

    afterEach(function (): void {
        // Clean up test fixtures
        if (is_dir($this->fixturesDir)) {
            $files = glob($this->fixturesDir.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->fixturesDir);
        }
    });

    test('DTO creation with valid file path', function (): void {
        $audioFile = $this->fixturesDir.'/speech.mp3';
        file_put_contents($audioFile, 'fake audio content');

        $request = new SpeechToTextAsyncRequest(
            file: $audioFile,
            providers: [ServiceProviderEnum::GOOGLE, ServiceProviderEnum::OPENAI],
            language: 'en',
        );

        expect($request->file)->toBe($audioFile)
            ->and($request->providers)->toBe([ServiceProviderEnum::GOOGLE, ServiceProviderEnum::OPENAI])
            ->and($request->language)->toBe('en')
            ->and($request->speakers)->toBeNull()
            ->and($request->profanityFilter)->toBeNull();
    });

    test('toArray excludes file property', function (): void {
        $audioFile = $this->fixturesDir.'/speech.wav';
        file_put_contents($audioFile, 'fake audio content');

        $request = new SpeechToTextAsyncRequest(
            file: $audioFile,
            providers: [ServiceProviderEnum::AMAZON],
            language: 'en',
            speakers: 2,
            profanityFilter: true,
        );

        $array = $request->toArray();

        expect($array)->not->toHaveKey('file')
            ->and($array)->toHaveKey('providers')
            ->and($array)->toHaveKey('language')
            ->and($array)->toHaveKey('speakers')
            ->and($array)->toHaveKey('profanity_filter')
            ->and($array['speakers'])->toBe(2)
            ->and($array['profanity_filter'])->toBeTrue();
    });

    test('ValidationException for unsupported audio format', function (): void {
        $audioFile = $this->fixturesDir.'/speech.aac';
        file_put_contents($audioFile, 'fake audio content');

        expect(fn () => new SpeechToTextAsyncRequest(
            file: $audioFile,
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
        ))->toThrow(ValidationException::class, 'Unsupported audio format');
    });

    test('FileUploadException for non-existent file', function (): void {
        $nonExistentFile = $this->fixturesDir.'/nonexistent.mp3';

        expect(fn () => new SpeechToTextAsyncRequest(
            file: $nonExistentFile,
            providers: [ServiceProviderEnum::DEEPGRAM],
            language: 'en',
        ))->toThrow(FileUploadException::class, 'File not found');
    });

    test('FileUploadException for unreadable file', function (): void {
        $unreadableFile = $this->fixturesDir.'/unreadable.flac';
        file_put_contents($unreadableFile, 'fake audio content');
        chmod($unreadableFile, 0000);

        try {
            expect(fn () => new SpeechToTextAsyncRequest(
                file: $unreadableFile,
                providers: [ServiceProviderEnum::MICROSOFT],
                language: 'en',
            ))->toThrow(FileUploadException::class);
        } finally {
            // Restore permissions for cleanup
            chmod($unreadableFile, 0644);
        }
    });

    test('providers array of ServiceProviderEnum is handled', function (): void {
        $audioFile = $this->fixturesDir.'/speech.ogg';
        file_put_contents($audioFile, 'fake audio content');

        $request = new SpeechToTextAsyncRequest(
            file: $audioFile,
            providers: [
                ServiceProviderEnum::GOOGLE,
                ServiceProviderEnum::AMAZON,
                ServiceProviderEnum::DEEPGRAM,
                ServiceProviderEnum::ASSEMBLY_AI,
            ],
            language: 'fr',
        );

        $array = $request->toArray();

        expect($array['providers'])->toBe(['google', 'amazon', 'deepgram', 'assembly_ai'])
            ->and($array['language'])->toBe('fr');
    });

    test('toArray excludes null optional parameters', function (): void {
        $audioFile = $this->fixturesDir.'/speech.mp3';
        file_put_contents($audioFile, 'fake audio content');

        $request = new SpeechToTextAsyncRequest(
            file: $audioFile,
            providers: [ServiceProviderEnum::OPENAI],
            language: 'en',
            speakers: null,
            profanityFilter: null,
        );

        $array = $request->toArray();

        expect($array)->not->toHaveKey('speakers')
            ->and($array)->not->toHaveKey('profanity_filter')
            ->and($array)->toHaveKey('providers')
            ->and($array)->toHaveKey('language');
    });

    test('default language is en', function (): void {
        $audioFile = $this->fixturesDir.'/speech.wav';
        file_put_contents($audioFile, 'fake audio content');

        $request = new SpeechToTextAsyncRequest(
            file: $audioFile,
            providers: [ServiceProviderEnum::REV_AI],
        );

        expect($request->language)->toBe('en')
            ->and($request->toArray()['language'])->toBe('en');
    });
});
