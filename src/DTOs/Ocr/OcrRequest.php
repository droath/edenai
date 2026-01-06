<?php


declare(strict_types=1);

namespace Droath\Edenai\DTOs\Ocr;

use Droath\Edenai\DTOs\FileDTO;
use Droath\Edenai\DTOs\AbstractRequestDTO;
use Droath\Edenai\Enums\ServiceProviderEnum;
use Droath\Edenai\Exceptions\FileUploadException;
use Droath\Edenai\Exceptions\ValidationException;
use Droath\Edenai\Traits\ImageFileValidationTrait;

/**
 * Request DTO for synchronous OCR text extraction.
 *
 * This DTO encapsulates all parameters required for the Eden AI OCR endpoint.
 * It supports both local file uploads (via FileDTO::fromPath) and remote URL
 * references (via FileDTO::fromUrl).
 *
 * When using path-based files, validation occurs at construction time following
 * the fail-fast principle. URL-based files are not validated locally as the
 * remote resource is accessed by the API directly.
 *
 * The file property is excluded from toArray() for path-based files, as the
 * actual file upload is handled separately by FileUploadTrait through
 * multipart/form-data. For URL-based files, the URL is included as file_url.
 *
 * Supported image formats: png, jpg, jpeg, gif, tiff, bmp, pdf
 *
 * @package Droath\Edenai\DTOs\Ocr
 */
final class OcrRequest extends AbstractRequestDTO
{
    use ImageFileValidationTrait;

    /**
     * Create a new OCR request.
     *
     * @param FileDTO $file File to process (local path or remote URL)
     * @param array<int, ServiceProviderEnum> $providers AI service providers to use for OCR
     * @param string $language ISO language code for OCR (default: 'en')
     * @param array<int, ServiceProviderEnum>|null $fallbackProviders Fallback providers if primary fails
     *
     * @throws FileUploadException If a path-based file does not exist or is not readable
     * @throws ValidationException If a path-based file format is not supported
     */
    public function __construct(
        public readonly FileDTO $file,
        public readonly array $providers,
        public readonly string $language = 'en',
        public readonly ?array $fallbackProviders = null,
    ) {
        if ($this->file->isPath()) {
            $this->validateImageFile($this->file->getPath());
        }
    }

    /**
     * Convert the DTO to an array for serialization to the HTTP request body.
     *
     * For path-based files, the file property is excluded as it is handled
     * separately by FileUploadTrait through multipart/form-data upload.
     *
     * For URL-based files, the URL is included as file_url for the API to
     * fetch the remote resource directly.
     *
     * Providers are serialized as a comma-separated string for API compatibility.
     *
     * @return array<string, mixed> The serialized DTO data
     */
    public function toArray(): array
    {
        $data = [
            'providers' => $this->serializeProviders($this->providers),
            'language' => $this->language,
        ];

        if ($this->file->isUrl()) {
            $data['file_url'] = $this->file->getUrl();
        }

        if ($this->fallbackProviders !== null) {
            $data['fallback_providers'] = $this->serializeProviders($this->fallbackProviders);
        }

        return $data;
    }

    /**
     * Serialize an array of ServiceProviderEnum to a comma-separated string.
     *
     * @param array<int, ServiceProviderEnum> $providers Array of providers to serialize
     *
     * @return string Comma-separated provider values
     */
    private function serializeProviders(array $providers): string
    {
        return implode(',', array_map(
            static fn (ServiceProviderEnum $provider): string => $provider->value,
            $providers
        ));
    }
}
