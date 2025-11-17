<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Audio;

/**
 * Provider-specific result DTO for text-to-speech operations.
 *
 * Represents the output from a single AI provider's text-to-speech processing,
 * including status, audio data, voice type, resource URL, and optional cost.
 */
final readonly class ProviderResult
{
    /**
     * Create a new provider result DTO.
     *
     * @param string $provider The provider identifier (e.g., 'amazon', 'lovoai', 'openai', 'deepgram')
     * @param string|null $error Optional error message if the provider failed
     * @param string|null $id The provider's internal job identifier
     * @param string $finalStatus The final processing status (e.g.,'finished', 'failed')
     * @param string $audioData Raw binary audio data (decoded from Base64)
     * @param int|null $voiceType Optional voice type identifier (1=female, 2=male, etc.)
     * @param string|null $audioResourceUrl Optional URL to download the audio file
     * @param int|null $cost Optional cost of the operation in provider-specific units
     */
    public function __construct(
        public string $provider,
        public ?string $error,
        public ?string $id,
        public string $finalStatus,
        public string $audioData,
        public ?int $voiceType,
        public ?string $audioResourceUrl,
        public ?int $cost = null,
    ) {}

    /**
     * Create a provider result DTO from API response data.
     *
     * Automatically decodes Base64-encoded audio to raw binary format for
     * immediate use. Handles missing optional fields with null defaults.
     *
     * @param string $provider The provider identifier
     * @param array<string, mixed> $data The provider result data
     *
     * @return static The constructed provider result DTO
     */
    public static function fromResponse(string $provider, array $data): static
    {
        return new self(
            provider: $provider,
            error: isset($data['error']) ? (string) $data['error'] : null,
            id: isset($data['id']) ? (string) $data['id'] : null,
            finalStatus: (string) ($data['final_status'] ?? ''),
            audioData: base64_decode((string) ($data['audio'] ?? ''), true) ?: '',
            voiceType: isset($data['voice_type']) ? (int) $data['voice_type'] : null,
            audioResourceUrl: isset($data['audio_resource_url']) ? (string) $data['audio_resource_url'] : null,
            cost: isset($data['cost']) ? (int) $data['cost'] : null,
        );
    }
}
