<?php

declare(strict_types=1);

namespace Droath\Edenai\Traits;

use Droath\Edenai\Exceptions\FileUploadException;
use Droath\Edenai\Exceptions\ValidationException;

/**
 * Provides file validation functionality for file existence, readability, and format checking.
 *
 * This trait encapsulates common file validation logic that can be reused across
 * DTOs and resource classes that need to validate file uploads before processing.
 *
 * @package Droath\Edenai\Traits
 */
trait FileValidationTrait
{
    /**
     * Supported audio file formats.
     */
    private const SUPPORTED_AUDIO_FORMATS = ['mp3', 'wav', 'flac', 'ogg'];

    /**
     * Validate that an audio file exists, is readable, and has a supported format.
     *
     * @param string $filePath Absolute path to the audio file
     *
     * @throws FileUploadException If file does not exist or is not readable
     * @throws ValidationException If file format is not supported
     */
    protected function validateAudioFile(string $filePath): void
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
        if (! in_array($extension, self::SUPPORTED_AUDIO_FORMATS, true)) {
            throw new ValidationException(
                "Unsupported audio format: {$extension}. Supported formats: " . implode(', ', self::SUPPORTED_AUDIO_FORMATS),
                ['file' => ["Unsupported audio format: {$extension}"]]
            );
        }
    }

    /**
     * Get the list of supported audio formats.
     *
     * @return array<int, string> Array of supported file extensions
     */
    protected function getSupportedAudioFormats(): array
    {
        return self::SUPPORTED_AUDIO_FORMATS;
    }
}
