<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Audio;

use DateTimeImmutable;
use DateMalformedStringException;
use Droath\Edenai\DTOs\AbstractResponseDTO;

/**
 * Response DTO for asynchronous text-to-speech API endpoint.
 *
 * Contains job tracking metadata returned when submitting text for asynchronous
 * speech synthesis. The response includes a job ID for status polling, the list
 * of providers processing the request, and the submission timestamp.
 *
 * This DTO has an identical structure to SpeechToTextAsyncResponse for consistency
 * across async audio operations. It contains ONLY job tracking metadata and does
 * not echo request parameters. Clients use the job ID to poll for completion and
 * retrieve generated audio results.
 *
 * Example API response:
 * ```json
 * {
 *   "job_id": "tts_job_12345abc",
 *   "providers": ["google", "amazon"],
 *   "submitted_at": "2024-01-15 10:30:00"
 * }
 * ```
 *
 * Usage:
 * ```php
 * $response = TextToSpeechAsyncResponse::fromResponse($apiData);
 * $jobId = $response->jobId;
 * $submittedAt = $response->submittedAt->format('Y-m-d H:i:s');
 * ```
 */
final class TextToSpeechAsyncResponse extends AbstractResponseDTO
{
    /**
     * Create a new text-to-speech async response DTO.
     *
     * @param string $jobId The unique job identifier for polling status
     * @param array<int, string> $providers List of AI provider identifiers processing the request
     * @param DateTimeImmutable $submittedAt The timestamp when the job was submitted
     */
    public function __construct(
        public readonly string $jobId,
        public readonly array $providers,
        public readonly DateTimeImmutable $submittedAt,
    ) {}

    /**
     * Create a response DTO from API response data.
     *
     * Parses the Eden AI async job response with lenient handling of unknown
     * keys.
     * Extracts the job ID from the public_id field and generates the current
     * timestamp for submission time.
     * The provider list is extracted from the result object keys.
     *
     * @param array<string, mixed> $data The API response data
     *
     * @return static The constructed response DTO
     */
    public static function fromResponse(array $data): static
    {
        $providers = [];

        if (isset($data['results']) && is_array($data['results'])) {
            $providers = array_keys($data['results']);
        }

        return new self(
            jobId: (string) ($data['public_id'] ?? ''),
            providers: $providers,
            submittedAt: new DateTimeImmutable(),
        );
    }
}
