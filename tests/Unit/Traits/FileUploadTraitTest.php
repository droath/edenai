<?php

declare(strict_types=1);

use Psr\Http\Message\RequestInterface;
use Droath\Edenai\Traits\FileUploadTrait;
use Droath\Edenai\Exceptions\FileUploadException;
use Droath\Edenai\Exceptions\ValidationException;

describe('FileUploadTrait', function (): void {
    beforeEach(function (): void {
        // Create a test class that uses the trait
        $this->trait = new class {
            use FileUploadTrait;

            public function createMultipartRequestPublic(string $filePath, array $params): RequestInterface
            {
                return $this->createMultipartRequest($filePath, $params);
            }
        };

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

    test('createMultipartRequest validates file exists', function (): void {
        $nonExistentFile = $this->fixturesDir.'/nonexistent.mp3';

        expect(fn () => $this->trait->createMultipartRequestPublic($nonExistentFile, []))
            ->toThrow(FileUploadException::class, 'File not found');
    });

    test('exception thrown for missing or unreadable files', function (): void {
        // Create a directory with no read permissions containing a file
        // This ensures the file path exists but is not readable across all platforms
        $unreadableDir = $this->fixturesDir.'/unreadable_dir';
        mkdir($unreadableDir, 0777);

        $unreadableFile = $unreadableDir.'/file.mp3';
        file_put_contents($unreadableFile, 'test content');

        // Remove read permissions from the directory, making files inside unreadable
        chmod($unreadableDir, 0000);

        try {
            expect(fn () => $this->trait->createMultipartRequestPublic($unreadableFile, []))
                ->toThrow(FileUploadException::class);
        } finally {
            // Restore permissions for cleanup
            chmod($unreadableDir, 0755);
        }
    });

    test('multipart form-data Content-Type header is set', function (): void {
        $audioFile = $this->fixturesDir.'/test.mp3';
        file_put_contents($audioFile, 'fake audio content');

        $request = $this->trait->createMultipartRequestPublic($audioFile, ['language' => 'en']);

        $contentType = $request->getHeader('Content-Type')[0] ?? '';

        expect($contentType)->toContain('multipart/form-data');
    });

    test('supported audio format validation for mp3', function (): void {
        $mp3File = $this->fixturesDir.'/audio.mp3';
        file_put_contents($mp3File, 'fake mp3 content');

        $request = $this->trait->createMultipartRequestPublic($mp3File, []);

        expect($request)->toBeInstanceOf(RequestInterface::class);
    });

    test('supported audio format validation for wav', function (): void {
        $wavFile = $this->fixturesDir.'/audio.wav';
        file_put_contents($wavFile, 'fake wav content');

        $request = $this->trait->createMultipartRequestPublic($wavFile, []);

        expect($request)->toBeInstanceOf(RequestInterface::class);
    });

    test('supported audio format validation for flac', function (): void {
        $flacFile = $this->fixturesDir.'/audio.flac';
        file_put_contents($flacFile, 'fake flac content');

        $request = $this->trait->createMultipartRequestPublic($flacFile, []);

        expect($request)->toBeInstanceOf(RequestInterface::class);
    });

    test('supported audio format validation for ogg', function (): void {
        $oggFile = $this->fixturesDir.'/audio.ogg';
        file_put_contents($oggFile, 'fake ogg content');

        $request = $this->trait->createMultipartRequestPublic($oggFile, []);

        expect($request)->toBeInstanceOf(RequestInterface::class);
    });

    test('ValidationException thrown for unsupported format', function (): void {
        $unsupportedFile = $this->fixturesDir.'/audio.aac';
        file_put_contents($unsupportedFile, 'fake aac content');

        expect(fn () => $this->trait->createMultipartRequestPublic($unsupportedFile, []))
            ->toThrow(ValidationException::class, 'Unsupported audio format');
    });
});
