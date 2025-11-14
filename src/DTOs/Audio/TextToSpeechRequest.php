<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Audio;

use InvalidArgumentException;
use Droath\Edenai\DTOs\AbstractRequestDTO;
use Droath\Edenai\Enums\ServiceProviderEnum;

/**
 * Request DTO for synchronous text-to-speech conversion.
 *
 * This DTO encapsulates all parameters required for the Eden AI text-to-speech
 * endpoint. It validates that text is non-empty at construction time following
 * the fail-fast principle. Audio parameter ranges (rate, pitch, volume) are
 * validated server-side by Eden AI, so client-side range validation is omitted.
 *
 * All optional audio parameters (option, audioFormat, rate, pitch, volume, voiceModel)
 * are nullable to support provider defaults. When null, these parameters are excluded
 * from the API request, allowing the provider to use its default values.
 *
 * @package Droath\Edenai\DTOs\Audio
 */
final class TextToSpeechRequest extends AbstractRequestDTO
{
    /**
     * Create a new text-to-speech request.
     *
     * @param string $text The text to convert to speech (non-empty string)
     * @param array<int, ServiceProviderEnum> $providers AI service providers to use for synthesis
     * @param string $language ISO language code for speech synthesis (default: 'en')
     * @param string|null $option Voice gender/type option (provider-specific, e.g., 'MALE', 'FEMALE')
     * @param string|null $audioFormat Desired output audio format (e.g., 'mp3', 'wav')
     * @param int|null $rate Speech rate modifier (provider-specific range, typically 0.5-2.0)
     * @param int|null $pitch Voice pitch modifier (provider-specific range)
     * @param int|null $volume Audio volume modifier (provider-specific range, typically 0.0-1.0)
     */
    public function __construct(
        public readonly string $text,
        public readonly array $providers,
        public readonly string $language = 'en',
        public readonly ?string $option = null,
        public readonly ?string $audioFormat = null,
        public readonly ?int $rate = null,
        public readonly ?int $pitch = null,
        public readonly ?int $volume = null
    ) {
        if ($this->text === '') {
            throw new InvalidArgumentException('Text cannot be empty');
        }
    }

    /**
     * Convert the DTO to an array for serialization to the HTTP request body.
     *
     * Serializes providers enum values to their string representations and
     * uses snake_case for API compatibility. Excludes null optional parameters
     * to allow provider defaults.
     *
     * @return array<string, mixed> The serialized DTO data
     */
    public function toArray(): array
    {
        $data = [
            'text' => $this->text,
            'providers' => array_map(
                static fn (ServiceProviderEnum $provider): string => $provider->value,
                $this->providers
            ),
            'language' => $this->language,
            'rate' => $this->rate ?? 0,
            'pitch' => $this->pitch ?? 0,
            'volume' => $this->volume ?? 0,
        ];

        if ($this->option !== null) {
            $data['option'] = $this->option;
        }

        if ($this->audioFormat !== null) {
            $data['audio_format'] = $this->audioFormat;
        }

        return $data;
    }
}
