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
            // Recursively delete directory contents
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->fixturesDir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                // Ensure we have permissions to delete
                if ($item->isDir()) {
                    chmod($item->getPathname(), 0755);
                    rmdir($item->getPathname());
                } else {
                    chmod($item->getPathname(), 0644);
                    unlink($item->getPathname());
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

    test('FileUploadException for directory path instead of file', function (): void {
        // Create a directory and attempt to use it as a file path
        // This tests that the validation properly checks is_file()
        $directory = $this->fixturesDir.'/test_directory.flac';
        mkdir($directory, 0755);

        expect(fn () => new SpeechToTextAsyncRequest(
            file: $directory,
            providers: [ServiceProviderEnum::MICROSOFT],
            language: 'en',
        ))->toThrow(FileUploadException::class, 'Path is not a file');
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

    test('settings parameter is excluded from toArray when null', function (): void {
        $audioFile = $this->fixturesDir.'/speech.mp3';
        file_put_contents($audioFile, 'fake audio content');

        $request = new SpeechToTextAsyncRequest(
            file: $audioFile,
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
            settings: null,
        );

        $array = $request->toArray();

        expect($array)->not->toHaveKey('settings');
    });

    test('settings parameter is included in toArray when provided', function (): void {
        $audioFile = $this->fixturesDir.'/speech.wav';
        file_put_contents($audioFile, 'fake audio content');

        $settings = [
            'google' => 'video',
            'ibm' => 'telephony',
        ];

        $request = new SpeechToTextAsyncRequest(
            file: $audioFile,
            providers: [ServiceProviderEnum::GOOGLE, ServiceProviderEnum::IBMWATSON],
            language: 'en',
            settings: $settings,
        );

        $array = $request->toArray();

        expect($array)->toHaveKey('settings')
            ->and($array['settings'])->toBe($settings)
            ->and($array['settings']['google'])->toBe('video')
            ->and($array['settings']['ibm'])->toBe('telephony');
    });

    test('settings parameter with single provider for speech-to-text', function (): void {
        $audioFile = $this->fixturesDir.'/speech.flac';
        file_put_contents($audioFile, 'fake audio content');

        $request = new SpeechToTextAsyncRequest(
            file: $audioFile,
            providers: [ServiceProviderEnum::AMAZON],
            language: 'en',
            settings: ['amazon' => 'medical'],
        );

        $array = $request->toArray();

        expect($array)->toHaveKey('settings')
            ->and($array['settings'])->toBe(['amazon' => 'medical']);
    });

    test('settings parameter with multiple providers for speech-to-text', function (): void {
        $audioFile = $this->fixturesDir.'/speech.ogg';
        file_put_contents($audioFile, 'fake audio content');

        $settings = [
            'google' => 'latest_long',
            'microsoft' => 'conversation',
            'deepgram' => 'nova-2',
        ];

        $request = new SpeechToTextAsyncRequest(
            file: $audioFile,
            providers: [ServiceProviderEnum::GOOGLE, ServiceProviderEnum::MICROSOFT, ServiceProviderEnum::DEEPGRAM],
            language: 'en',
            settings: $settings,
        );

        $array = $request->toArray();

        expect($array)->toHaveKey('settings')
            ->and($array['settings'])->toBe($settings)
            ->and($array['settings'])->toHaveCount(3);
    });
});
