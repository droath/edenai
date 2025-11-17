<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Audio;

use Droath\Edenai\DTOs\AbstractResponseDTO;

/**
 * Response DTO for text-to-speech async job result retrieval endpoint.
 *
 * Contains the complete results of an asynchronous text-to-speech job including
 * job status, error information, and provider-specific results with generated
 * audio data. Each provider result includes final processing status, audio data
 * (Base64-encoded), voice type, and audio resource URL.
 *
 * This DTO represents the full lifecycle state of a TTS async job and allows
 * clients to retrieve generated audio once processing is complete.
 *
 * Example API response:
 * ```json
 * {
 *   "public_id": "c0b09268-1514-49e2-9858-ac575da6225e",
 *   "status": "finished",
 *   "error": null,
 *   "results": {
 *     "amazon": {
 *       "error": null,
 *       "id": "ac4afc96-6b03-45ff-8476-187b369f807a",
 *       "final_status": "finished",
 *       "audio": "[AUDIO_DATA]",
 *       "voice_type": 1,
 *       "audio_resource_url": "https://example.com/audio.mp3"
 *     }
 *   }
 * }
 * ```
 *
 * Usage:
 * ```php
 * $response = TextToSpeechAsyncJobResultResponse::fromResponse($apiData);
 * if ($response->status === 'finished') {
 *     foreach ($response->results as $result) {
 *         file_put_contents("audio_{$result->provider}.mp3", $result->audioData);
 *     }
 * }
 * ```
 */
final class TextToSpeechAsyncJobResultResponse extends AbstractResponseDTO
{
    /**
     * Create a new text-to-speech async job result response DTO.
     *
     * @param string $publicId The unique public identifier for the job
     * @param string $status The overall job status (e.g., 'finished', 'processing', 'failed')
     * @param string|null $error Optional error message if job failed
     * @param array<int, ProviderResult> $results Array of provider-specific results
     */
    public function __construct(
        public readonly string $publicId,
        public readonly string $status,
        public readonly ?string $error,
        public readonly array $results,
    ) {}

    /**
     * Create a response DTO from API response data.
     *
     * Parses the Eden AI async job result response, extracting job metadata
     * and transforming provider results into strongly typed ProviderResult DTOs.
     * Handles nullable error fields and missing optional data gracefully.
     *
     * @param array<string, mixed> $data The API response data
     *
     * @return static The constructed response DTO
     */
    public static function fromResponse(array $data): static
    {
        $results = [];

        if (isset($data['results']) && is_array($data['results'])) {
            foreach ($data['results'] as $provider => $providerData) {
                if (is_array($providerData)) {
                    $results[] = ProviderResult::fromResponse($provider, $providerData);
                }
            }
        }

        return new self(
            publicId: (string) ($data['public_id'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            error: isset($data['error']) ? (string) $data['error'] : null,
            results: $results,
        );
    }
}
