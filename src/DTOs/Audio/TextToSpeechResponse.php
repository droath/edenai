<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Audio;

use Droath\Edenai\DTOs\AbstractResponseDTO;

/**
 * Response DTO for synchronous text-to-speech API endpoint.
 *
 * Contains results from all requested AI providers, each with generated audio
 * data (decoded from Base64), voice type, resource URL, and cost information.
 * The audio data is stored in a memory-efficient raw binary format suitable
 * for file writing or streaming.
 *
 * The fromResponse() factory automatically decodes Base64-encoded audio from the
 * API response to raw binary data, eliminating the need for manual decoding.
 * Results from all providers are preserved in the response.
 *
 * Example API response:
 * ```json
 * {
 *   "openai": {
 *     "audio": "//PkxABh5DnAA1rAAHnMkHAaGmLBwxGcw40yZczZ80p82Sw...",
 *     "voice_type": 1,
 *     "audio_resource_url": "https://example.com/audio.mp3",
 *     "cost": 0
 *   },
 *   "deepgram": {
 *     "audio": "//NgxAAb+cYwAnoSnAhgGQ6JWPWdavfq80FA8TiGGgE...",
 *     "voice_type": 1,
 *     "audio_resource_url": "https://example.com/audio2.mp3",
 *     "cost": 0
 *   }
 * }
 * ```
 *
 * Usage:
 * ```php
 * $response = TextToSpeechResponse::fromResponse($apiData);
 * foreach ($response->results as $result) {
 *     file_put_contents("audio_{$result->provider}.mp3", $result->audioData);
 *     echo "Provider: {$result->provider}, Cost: {$result->cost}\n";
 * }
 * ```
 */
final class TextToSpeechResponse extends AbstractResponseDTO
{
    /**
     * Create a new text-to-speech response DTO.
     *
     * @param array<int, ProviderResult> $results Array of provider-specific results
     */
    public function __construct(
        public readonly array $results,
    ) {}

    /**
     * Create a response DTO from API response data.
     *
     * Parses the Eden AI synchronous text-to-speech response, extracting results
     * from all requested providers and transforming them into strongly typed
     * ProviderResult DTOs. Automatically decodes Base64-encoded audio to raw
     * binary format for immediate use.
     *
     * The synchronous API returns provider-specific results with provider names
     * as top-level keys (e.g., 'openai', 'deepgram'). This method preserves all
     * provider results, unlike the previous implementation which only extracted
     * the first provider.
     *
     * @param array<string, mixed> $data The API response data
     *
     * @return static The constructed response DTO
     */
    public static function fromResponse(array $data): static
    {
        $results = [];

        foreach ($data as $provider => $providerData) {
            if (is_array($providerData)) {
                $results[] = ProviderResult::fromResponse($provider, $providerData);
            }
        }

        return new self(
            results: $results,
        );
    }
}
