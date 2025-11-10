<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Audio;

use DateTimeImmutable;
use DateMalformedStringException;
use Droath\Edenai\DTOs\AbstractResponseDTO;

/**
 * Response DTO for asynchronous speech-to-text API endpoint.
 *
 * Contains job tracking metadata returned when submitting an audio file for
 * asynchronous transcription. The response includes a job ID for status polling,
 * the list of providers processing the request, and the submission timestamp.
 *
 * This DTO contains ONLY job tracking metadata and does not echo request parameters.
 * Clients use the job ID to poll for completion and retrieve transcription results.
 *
 * Example API response:
 * ```json
 * {
 *   "job_id": "job_12345abc",
 *   "providers": ["google", "amazon"],
 *   "submitted_at": "2024-01-15 10:30:00"
 * }
 * ```
 *
 * Usage:
 * ```php
 * $response = SpeechToTextAsyncResponse::fromResponse($apiData);
 * $jobId = $response->jobId;
 * $submittedAt = $response->submittedAt->format('Y-m-d H:i:s');
 * ```
 */
final class SpeechToTextAsyncResponse extends AbstractResponseDTO
{
    /**
     * Create a new speech-to-text async response DTO.
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
     * keys. Transforms the timestamp string to DateTimeImmutable for type
     * safety.
     *
     * @param array<string, mixed> $data The API response data
     *
     * @return static The constructed response DTO
     *
     * @throws DateMalformedStringException
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            jobId: (string) $data['job_id'],
            providers: (array) $data['providers'],
            submittedAt: new DateTimeImmutable((string) $data['submitted_at']),
        );
        // Unknown keys in $data are automatically ignored (lenient parsing)
    }
}
