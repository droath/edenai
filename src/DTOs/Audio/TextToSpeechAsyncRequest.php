<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Audio;

use InvalidArgumentException;
use Droath\Edenai\DTOs\AbstractRequestDTO;
use Droath\Edenai\Enums\ServiceProviderEnum;

/**
 * Request DTO for asynchronous text-to-speech conversion.
 *
 * This DTO has an identical structure to TextToSpeechRequest but maintains
 * type safety by being a separate class for async vs. sync operations. This
 * allows the type system to enforce the correct usage of sync vs. async endpoints
 * at compile time.
 *
 * It validates that text is non-empty at construction time following the
 * fail-fast principle. Audio parameter ranges (rate, pitch, volume) are
 * validated server-side by Eden AI, so client-side range validation is omitted.
 *
 * All optional audio parameters are nullable to support provider defaults.
 * When null, these parameters are excluded from the API request, allowing
 * the provider to use its default values.
 *
 * @package Droath\Edenai\DTOs\Audio
 */
final class TextToSpeechAsyncRequest extends AbstractRequestDTO
{
    /**
     * Create a new asynchronous text-to-speech request.
     *
     * @param string $text The text to convert to speech (non-empty string)
     * @param array<int, ServiceProviderEnum> $providers AI service providers to use for synthesis
     * @param string $language ISO language code for speech synthesis (default: 'en')
     * @param string|null $option Voice gender/type option (provider-specific, e.g., 'MALE', 'FEMALE')
     * @param string|null $audioFormat Desired output audio format (e.g., 'mp3', 'wav')
     * @param float|null $rate Speech rate modifier (provider-specific range, typically 0.5-2.0)
     * @param float|null $pitch Voice pitch modifier (provider-specific range)
     * @param float|null $volume Audio volume modifier (provider-specific range, typically 0.0-1.0)
     * @param string|null $voiceModel Specific voice model identifier (provider-specific)
     *
     * @throws InvalidArgumentException If text is empty
     */
    public function __construct(
        public readonly string $text,
        public readonly array $providers,
        public readonly string $language = 'en',
        public readonly ?string $option = null,
        public readonly ?string $audioFormat = null,
        public readonly ?float $rate = null,
        public readonly ?float $pitch = null,
        public readonly ?float $volume = null,
        public readonly ?string $voiceModel = null,
    ) {
        // Validate text is non-empty
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
        ];

        // Include optional parameters only if set
        if ($this->option !== null) {
            $data['option'] = $this->option;
        }

        if ($this->audioFormat !== null) {
            $data['audio_format'] = $this->audioFormat;
        }

        if ($this->rate !== null) {
            $data['rate'] = $this->rate;
        }

        if ($this->pitch !== null) {
            $data['pitch'] = $this->pitch;
        }

        if ($this->volume !== null) {
            $data['volume'] = $this->volume;
        }

        if ($this->voiceModel !== null) {
            $data['voice_model'] = $this->voiceModel;
        }

        return $data;
    }
}
