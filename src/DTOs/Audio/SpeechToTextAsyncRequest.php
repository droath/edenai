<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Audio;

use Droath\Edenai\DTOs\AbstractRequestDTO;
use Droath\Edenai\Enums\ServiceProviderEnum;
use Droath\Edenai\Traits\FileValidationTrait;
use Droath\Edenai\Exceptions\FileUploadException;
use Droath\Edenai\Exceptions\ValidationException;

/**
 * Request DTO for asynchronous speech-to-text transcription.
 *
 * This DTO encapsulates all parameters required for the Eden AI speech-to-text
 * async endpoint. It validates file existence, format support, and readability
 * at construction time following the fail-fast principle.
 *
 * The file property contains the path to the audio file to transcribe. The actual
 * file upload is handled separately by FileUploadTrait, so toArray() excludes
 * the file property and only serializes the form parameters.
 *
 * Supported audio formats: mp3, wav, flac, ogg
 *
 * @package Droath\Edenai\DTOs\Audio
 */
final class SpeechToTextAsyncRequest extends AbstractRequestDTO
{
    use FileValidationTrait;

    /**
     * Create a new speech-to-text async request.
     *
     * @param string $file Absolute path to the audio file to transcribe
     * @param array<int, ServiceProviderEnum> $providers AI service providers to use for transcription
     * @param string $language ISO language code for transcription (default: 'en')
     * @param int|null $speakers Number of speakers to detect (optional)
     * @param bool|null $profanityFilter Enable profanity filtering (optional)
     *
     * @throws FileUploadException If a file does not exist or is not readable
     * @throws ValidationException If a file format is not supported
     */
    public function __construct(
        public readonly string $file,
        public readonly array $providers,
        public readonly string $language = 'en',
        public readonly ?int $speakers = null,
        public readonly ?bool $profanityFilter = null,
    ) {
        $this->validateAudioFile($file);
    }

    /**
     * Convert the DTO to an array for serialization to the HTTP request body.
     *
     * Excludes the file property as it is handled separately by FileUploadTrait
     * through multipart/form-data upload. Serializes providers enum values to
     * their string representations and uses snake_case for API compatibility.
     *
     * @return array<string, mixed> The serialized DTO data
     */
    public function toArray(): array
    {
        $data = [
            'providers' => array_map(
                static fn (ServiceProviderEnum $provider): string => $provider->value,
                $this->providers
            ),
            'language' => $this->language,
        ];

        // Include optional parameters only if set
        if ($this->speakers !== null) {
            $data['speakers'] = $this->speakers;
        }

        if ($this->profanityFilter !== null) {
            $data['profanity_filter'] = $this->profanityFilter;
        }

        return $data;
    }

}
