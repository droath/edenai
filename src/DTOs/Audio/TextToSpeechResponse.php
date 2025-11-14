<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Audio;

use Droath\Edenai\DTOs\AbstractResponseDTO;

/**
 * Response DTO for synchronous text-to-speech API endpoint.
 *
 * Contains the generated audio data (decoded from Base64) ready for immediate use,
 * along with content type and optional duration metadata. The audio data is stored
 * in a memory-efficient raw binary format suitable for file writing or streaming.
 *
 * The fromResponse() factory automatically decodes Base64-encoded audio from the
 * API response to raw binary data, eliminating the need for manual decoding.
 *
 * Example API response:
 * ```json
 * {
 *   "audio": "UklGRiQAAABXQVZFZm10IBAAA...", // Base64-encoded audio
 *   "content_type": "audio/wav",
 *   "duration": 3.5
 * }
 * ```
 *
 * Usage:
 * ```php
 * $response = TextToSpeechResponse::fromResponse($apiData);
 * file_put_contents('output.wav', $response->audioData);
 * echo "Content-Type: {$response->contentType}";
 * echo "Duration: {$response->duration} seconds";
 * ```
 */
final class TextToSpeechResponse extends AbstractResponseDTO
{
    /**
     * Create a new text-to-speech response DTO.
     *
     * @param string $audioData Raw binary audio data (decoded from Base64)
     * @param string $contentType MIME type of the audio (e.g., 'audio/wav', 'audio/mpeg')
     * @param float|null $duration Optional audio duration in seconds
     */
    public function __construct(
        public readonly string $audioData,
        public readonly string $contentType,
        public readonly ?float $duration = null,
    ) {
    }

    /**
     * Create a response DTO from API response data.
     *
     * Automatically decodes Base64-encoded audio to raw binary format for
     * immediate use. Handles missing optional fields (duration) with null defaults.
     * Parses response leniently, ignoring any unknown keys from the API.
     *
     * The API returns provider-specific results with provider names as keys.
     * This method extracts the first provider's audio data.
     *
     * @param array<string, mixed> $data The API response data
     *
     * @return static The constructed response DTO
     */
    public static function fromResponse(array $data): static
    {
        $providerResult = reset($data);

        if (! is_array($providerResult)) {
            $providerResult = [];
        }

        return new self(
            audioData: base64_decode((string) ($providerResult['audio'] ?? ''), true) ?: '',
            contentType: 'audio/mpeg', // Default to audio/mpeg for MP3
            duration: isset($providerResult['duration']) ? (float) $providerResult['duration'] : null,
        );
    }
}
