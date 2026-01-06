<?php

declare(strict_types=1);

namespace Droath\Edenai\Traits;

use Droath\Edenai\Exceptions\FileUploadException;
use Droath\Edenai\Exceptions\ValidationException;

/**
 * Provides file validation functionality for image files.
 *
 * This trait encapsulates common image file validation logic that can be reused
 * across DTOs and resource classes that need to validate image uploads before
 * processing. It validates file existence, readability, and format support.
 */
trait ImageFileValidationTrait
{
    /**
     * Supported image file formats.
     *
     * @var array<int, string>
     */
    private const SUPPORTED_IMAGE_FORMATS = ['png', 'jpg', 'jpeg', 'gif', 'tiff', 'bmp', 'pdf'];

    /**
     * Validate that an image file exists, is readable, and has a supported format.
     *
     * @param string $filePath Absolute path to the image file
     *
     * @throws FileUploadException If file does not exist or is not readable
     * @throws ValidationException If file format is not supported
     */
    protected function validateImageFile(string $filePath): void
    {
        if (! file_exists($filePath)) {
            throw new FileUploadException("File not found: {$filePath}");
        }

        if (! is_file($filePath)) {
            throw new FileUploadException("Path is not a file: {$filePath}");
        }

        if (! is_readable($filePath)) {
            throw new FileUploadException("File is not readable: {$filePath}");
        }

        $extension = mb_strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (! in_array($extension, self::SUPPORTED_IMAGE_FORMATS, true)) {
            throw new ValidationException(
                "Unsupported image format: {$extension}. Supported formats: ".implode(', ', self::SUPPORTED_IMAGE_FORMATS),
                ['file' => ["Unsupported image format: {$extension}"]],
            );
        }
    }

    /**
     * Get the list of supported image formats.
     *
     * @return array<int, string> Array of supported file extensions
     */
    protected function getSupportedImageFormats(): array
    {
        return self::SUPPORTED_IMAGE_FORMATS;
    }
}
