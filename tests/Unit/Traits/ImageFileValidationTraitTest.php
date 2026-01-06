<?php

declare(strict_types=1);

use Droath\Edenai\Exceptions\FileUploadException;
use Droath\Edenai\Exceptions\ValidationException;
use Droath\Edenai\Traits\ImageFileValidationTrait;

/**
 * Test class that uses the ImageFileValidationTrait for testing purposes.
 */
final class ImageFileValidationTestClass
{
    use ImageFileValidationTrait;

    public function validate(string $filePath): void
    {
        $this->validateImageFile($filePath);
    }

    public function getFormats(): array
    {
        return $this->getSupportedImageFormats();
    }
}

describe('ImageFileValidationTrait', function (): void {
    beforeEach(function (): void {
        $this->fixturesDir = sys_get_temp_dir().'/edenai_image_test_'.uniqid();
        mkdir($this->fixturesDir, 0777, true);
        $this->validator = new ImageFileValidationTestClass();
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

    test('validateImageFile validates supported formats', function (): void {
        $supportedFormats = ['png', 'jpg', 'jpeg', 'gif', 'tiff', 'bmp', 'pdf'];

        foreach ($supportedFormats as $format) {
            $filePath = $this->fixturesDir.'/image.'.$format;
            file_put_contents($filePath, 'fake image content');

            expect(fn () => $this->validator->validate($filePath))->not->toThrow(Exception::class);
        }
    });

    test('throws FileUploadException for missing files', function (): void {
        $nonExistentFile = $this->fixturesDir.'/nonexistent.png';

        expect(fn () => $this->validator->validate($nonExistentFile))
            ->toThrow(FileUploadException::class, 'File not found');
    });

    test('throws ValidationException for unsupported formats', function (): void {
        $unsupportedFile = $this->fixturesDir.'/document.docx';
        file_put_contents($unsupportedFile, 'fake document content');

        expect(fn () => $this->validator->validate($unsupportedFile))
            ->toThrow(ValidationException::class, 'Unsupported image format');
    });

    test('getSupportedImageFormats returns correct formats', function (): void {
        $formats = $this->validator->getFormats();

        expect($formats)->toBe(['png', 'jpg', 'jpeg', 'gif', 'tiff', 'bmp', 'pdf']);
    });
});
