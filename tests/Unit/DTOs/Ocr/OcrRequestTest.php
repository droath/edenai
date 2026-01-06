<?php

declare(strict_types=1);

use Droath\Edenai\DTOs\FileDTO;
use Droath\Edenai\DTOs\Ocr\OcrRequest;
use Droath\Edenai\Enums\ServiceProviderEnum;
use Droath\Edenai\Exceptions\FileUploadException;
use Droath\Edenai\Exceptions\ValidationException;

describe('OcrRequest', function (): void {
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

    test('constructor accepts FileDTO and validates path-based files', function (): void {
        $imageFile = $this->fixturesDir.'/image.png';
        file_put_contents($imageFile, 'fake image content');

        $fileDTO = FileDTO::fromPath($imageFile);
        $request = new OcrRequest(
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
        $fileDTO = FileDTO::fromUrl('https://example.com/image.png');
        $request = new OcrRequest(
            file: $fileDTO,
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'fr',
        );

        $array = $request->toArray();

        expect($array)->toHaveKey('file_url')
            ->and($array['file_url'])->toBe('https://example.com/image.png')
            ->and($array['providers'])->toBe('google')
            ->and($array['language'])->toBe('fr');
    });

    test('toArray excludes file property for path-based FileDTO', function (): void {
        $imageFile = $this->fixturesDir.'/image.jpg';
        file_put_contents($imageFile, 'fake image content');

        $fileDTO = FileDTO::fromPath($imageFile);
        $request = new OcrRequest(
            file: $fileDTO,
            providers: [ServiceProviderEnum::AMAZON],
            language: 'en',
        );

        $array = $request->toArray();

        expect($array)->not->toHaveKey('file')
            ->and($array)->not->toHaveKey('file_url')
            ->and($array)->toHaveKey('providers')
            ->and($array)->toHaveKey('language');
    });

    test('serializes providers array to comma-separated string', function (): void {
        $fileDTO = FileDTO::fromUrl('https://example.com/document.pdf');
        $request = new OcrRequest(
            file: $fileDTO,
            providers: [
                ServiceProviderEnum::GOOGLE,
                ServiceProviderEnum::AMAZON,
                ServiceProviderEnum::MICROSOFT,
            ],
            language: 'en',
        );

        $array = $request->toArray();

        expect($array['providers'])->toBe('google,amazon,microsoft');
    });

    test('validation throws FileUploadException for non-existent image file', function (): void {
        $nonExistentFile = $this->fixturesDir.'/nonexistent.png';
        $fileDTO = FileDTO::fromPath($nonExistentFile);

        expect(fn () => new OcrRequest(
            file: $fileDTO,
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
        ))->toThrow(FileUploadException::class, 'File not found');
    });

    test('validation throws ValidationException for unsupported image format', function (): void {
        $unsupportedFile = $this->fixturesDir.'/image.webp';
        file_put_contents($unsupportedFile, 'fake image content');

        $fileDTO = FileDTO::fromPath($unsupportedFile);

        expect(fn () => new OcrRequest(
            file: $fileDTO,
            providers: [ServiceProviderEnum::AMAZON],
            language: 'en',
        ))->toThrow(ValidationException::class, 'Unsupported image format');
    });

    test('fallback providers are included in toArray when provided', function (): void {
        $fileDTO = FileDTO::fromUrl('https://example.com/image.png');
        $request = new OcrRequest(
            file: $fileDTO,
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
            fallbackProviders: [ServiceProviderEnum::AMAZON, ServiceProviderEnum::MICROSOFT],
        );

        $array = $request->toArray();

        expect($array)->toHaveKey('fallback_providers')
            ->and($array['fallback_providers'])->toBe('amazon,microsoft');
    });

    test('fallback providers are excluded from toArray when null', function (): void {
        $fileDTO = FileDTO::fromUrl('https://example.com/image.png');
        $request = new OcrRequest(
            file: $fileDTO,
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
            fallbackProviders: null,
        );

        $array = $request->toArray();

        expect($array)->not->toHaveKey('fallback_providers');
    });

    test('default language is en', function (): void {
        $fileDTO = FileDTO::fromUrl('https://example.com/image.png');
        $request = new OcrRequest(
            file: $fileDTO,
            providers: [ServiceProviderEnum::GOOGLE],
        );

        expect($request->language)->toBe('en')
            ->and($request->toArray()['language'])->toBe('en');
    });

    test('URL-based FileDTO does not trigger file validation', function (): void {
        $fileDTO = FileDTO::fromUrl('https://example.com/nonexistent.png');

        $request = new OcrRequest(
            file: $fileDTO,
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
        );

        expect($request->file->isUrl())->toBeTrue();
    });

    test('validates supported image formats for path-based files', function (): void {
        $supportedFormats = ['png', 'jpg', 'jpeg', 'gif', 'tiff', 'bmp', 'pdf'];

        foreach ($supportedFormats as $format) {
            $imageFile = $this->fixturesDir.'/image_'.$format.'.'.$format;
            file_put_contents($imageFile, 'fake image content');

            $fileDTO = FileDTO::fromPath($imageFile);
            $request = new OcrRequest(
                file: $fileDTO,
                providers: [ServiceProviderEnum::GOOGLE],
                language: 'en',
            );

            expect($request->file)->toBe($fileDTO);
        }
    });
});
