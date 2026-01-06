<?php

declare(strict_types=1);

use Droath\Edenai\DTOs\FileDTO;
use Droath\Edenai\DTOs\Ocr\OcrAsyncRequest;
use Droath\Edenai\Enums\ServiceProviderEnum;
use Droath\Edenai\Exceptions\FileUploadException;
use Droath\Edenai\Exceptions\ValidationException;

describe('OcrAsyncRequest', function (): void {
    beforeEach(function (): void {
        $this->fixturesDir = sys_get_temp_dir().'/edenai_test_'.uniqid();
        mkdir($this->fixturesDir, 0777, true);
    });

    afterEach(function (): void {
        if (is_dir($this->fixturesDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->fixturesDir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
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

    test('has identical behavior to OcrRequest for constructor', function (): void {
        $imageFile = $this->fixturesDir.'/image.png';
        file_put_contents($imageFile, 'fake image content');

        $fileDTO = FileDTO::fromPath($imageFile);
        $request = new OcrAsyncRequest(
            file: $fileDTO,
            providers: [ServiceProviderEnum::GOOGLE, ServiceProviderEnum::AMAZON],
            language: 'en',
        );

        expect($request->file)->toBe($fileDTO)
            ->and($request->providers)->toBe([ServiceProviderEnum::GOOGLE, ServiceProviderEnum::AMAZON])
            ->and($request->language)->toBe('en')
            ->and($request->fallbackProviders)->toBeNull();
    });

    test('toArray serializes correctly for URL-based FileDTO', function (): void {
        $fileDTO = FileDTO::fromUrl('https://example.com/document.pdf');
        $request = new OcrAsyncRequest(
            file: $fileDTO,
            providers: [ServiceProviderEnum::MICROSOFT],
            language: 'de',
        );

        $array = $request->toArray();

        expect($array)->toHaveKey('file_url')
            ->and($array['file_url'])->toBe('https://example.com/document.pdf')
            ->and($array['providers'])->toBe('microsoft')
            ->and($array['language'])->toBe('de');
    });

    test('toArray excludes file property for path-based FileDTO', function (): void {
        $imageFile = $this->fixturesDir.'/scan.tiff';
        file_put_contents($imageFile, 'fake image content');

        $fileDTO = FileDTO::fromPath($imageFile);
        $request = new OcrAsyncRequest(
            file: $fileDTO,
            providers: [ServiceProviderEnum::CLARIFAI],
            language: 'en',
        );

        $array = $request->toArray();

        expect($array)->not->toHaveKey('file')
            ->and($array)->not->toHaveKey('file_url')
            ->and($array)->toHaveKey('providers')
            ->and($array)->toHaveKey('language');
    });

    test('serializes providers array to comma-separated string', function (): void {
        $fileDTO = FileDTO::fromUrl('https://example.com/image.jpg');
        $request = new OcrAsyncRequest(
            file: $fileDTO,
            providers: [
                ServiceProviderEnum::API4AI,
                ServiceProviderEnum::BASE64,
                ServiceProviderEnum::MINDEE,
            ],
            language: 'en',
        );

        $array = $request->toArray();

        expect($array['providers'])->toBe('api4ai,base64,mindee');
    });

    test('validation throws FileUploadException for non-existent file', function (): void {
        $nonExistentFile = $this->fixturesDir.'/missing.jpeg';
        $fileDTO = FileDTO::fromPath($nonExistentFile);

        expect(fn () => new OcrAsyncRequest(
            file: $fileDTO,
            providers: [ServiceProviderEnum::SENTISIGHT],
            language: 'en',
        ))->toThrow(FileUploadException::class, 'File not found');
    });

    test('validation throws ValidationException for unsupported format', function (): void {
        $unsupportedFile = $this->fixturesDir.'/image.svg';
        file_put_contents($unsupportedFile, 'fake svg content');

        $fileDTO = FileDTO::fromPath($unsupportedFile);

        expect(fn () => new OcrAsyncRequest(
            file: $fileDTO,
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
        ))->toThrow(ValidationException::class, 'Unsupported image format');
    });

    test('fallback providers are serialized correctly', function (): void {
        $fileDTO = FileDTO::fromUrl('https://example.com/image.bmp');
        $request = new OcrAsyncRequest(
            file: $fileDTO,
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'es',
            fallbackProviders: [ServiceProviderEnum::MISTRAL, ServiceProviderEnum::BASE64],
        );

        $array = $request->toArray();

        expect($array)->toHaveKey('fallback_providers')
            ->and($array['fallback_providers'])->toBe('mistral,base64');
    });
});
