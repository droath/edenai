<?php

declare(strict_types=1);

namespace Droath\Edenai\Traits;

use JsonException;
use Psr\Http\Message\RequestInterface;
use Http\Discovery\Psr17FactoryDiscovery;
use Droath\Edenai\Exceptions\FileUploadException;
use Droath\Edenai\Exceptions\ValidationException;

/**
 * Provides multipart/form-data file upload functionality for API resources.
 *
 * This trait handles the creation of PSR-7 multipart requests with file uploads,
 * including validation of file existence, readability, and supported formats.
 * It is designed to be reusable across any resource class requiring file uploads.
 */
trait FileUploadTrait
{
    use FileValidationTrait;

    /**
     * Create a multipart/form-data request with file upload.
     *
     * Validates the file exists, is readable, and has a supported format before
     * creating the PSR-7 request with multipart body containing the file and
     * additional form parameters.
     *
     * @param string $filePath Absolute path to the file to upload
     * @param array<string, mixed> $params Additional form parameters to include
     *
     * @return RequestInterface PSR-7 request with multipart/form-data body
     *
     * @throws FileUploadException If file does not exist or is not readable
     * @throws ValidationException If file format is not supported
     * @throws JsonException
     */
    protected function createMultipartRequest(string $filePath, array $params): RequestInterface
    {
        $this->validateAudioFile($filePath);

        $boundary = uniqid('----EdenAI', true);

        $body = $this->buildMultipartBody($filePath, $params, $boundary);

        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

        $request = $requestFactory->createRequest('POST', '');

        $stream = $streamFactory->createStream($body);
        $request = $request->withBody($stream);

        $request = $request->withHeader('Content-Type', "multipart/form-data; boundary={$boundary}");

        return $request;
    }

    /**
     * Build the multipart/form-data request body.
     *
     * @param string $filePath Path to the file to upload
     * @param array<string, mixed> $params Additional form parameters
     * @param string $boundary Multipart boundary string
     *
     * @return string The complete multipart body
     */
    private function buildMultipartBody(string $filePath, array $params, string $boundary): string
    {
        $parts = [];

        $filename = basename($filePath);
        $fileContents = file_get_contents($filePath);
        $mimeType = $this->getMimeType($filePath);

        $parts[] = "--{$boundary}\r\n";
        $parts[] = "Content-Disposition: form-data; name=\"file\"; filename=\"{$filename}\"\r\n";
        $parts[] = "Content-Type: {$mimeType}\r\n\r\n";
        $parts[] = "{$fileContents}\r\n";

        foreach ($params as $name => $value) {
            $parts[] = "--{$boundary}\r\n";
            $parts[] = "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";

            if (is_array($value)) {
                $parts[] = json_encode($value, JSON_THROW_ON_ERROR)."\r\n";
            } elseif (is_bool($value)) {
                $parts[] = ($value ? 'true' : 'false')."\r\n";
            } else {
                $parts[] = "{$value}\r\n";
            }
        }

        $parts[] = "--{$boundary}--\r\n";

        return implode('', $parts);
    }

    /**
     * Get MIME type for the audio file.
     *
     * @param string $filePath Path to the file
     *
     * @return string MIME type string
     */
    private function getMimeType(string $filePath): string
    {
        $extension = mb_strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'flac' => 'audio/flac',
            'ogg' => 'audio/ogg',
            default => 'application/octet-stream',
        };
    }
}
