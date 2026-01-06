<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Ocr;

use DateTimeImmutable;
use Droath\Edenai\DTOs\AbstractResponseDTO;

/**
 * Response DTO for asynchronous OCR API endpoint.
 *
 * Contains job tracking metadata returned when submitting an image for
 * asynchronous OCR processing. The response includes a job ID for status
 * polling, the list of providers processing the request, and the submission
 * timestamp.
 *
 * This DTO contains ONLY job tracking metadata and does not echo request
 * parameters. Clients use the job ID to poll for completion and retrieve
 * OCR results.
 *
 * Example API response structure:
 * ```json
 * {
 *   "public_id": "job_12345abc",
 *   "results": {
 *     "google": {"status": "pending"},
 *     "amazon": {"status": "pending"}
 *   }
 * }
 * ```
 *
 * Usage:
 * ```php
 * $response = OcrAsyncResponse::fromResponse($apiData);
 * $jobId = $response->publicId;
 * $submittedAt = $response->submittedAt->format('Y-m-d H:i:s');
 * ```
 */
final class OcrAsyncResponse extends AbstractResponseDTO
{
    /**
     * Create a new OCR async response DTO.
     *
     * @param string $publicId The unique public identifier for the job
     * @param array<int, string> $providers List of AI provider identifiers processing the request
     * @param DateTimeImmutable $submittedAt The timestamp when the job was submitted
     */
    public function __construct(
        public readonly string $publicId,
        public readonly array $providers,
        public readonly DateTimeImmutable $submittedAt,
    ) {}

    /**
     * Create a response DTO from API response data.
     *
     * Parses the Eden AI async job response with lenient handling of unknown
     * keys. Extracts the job ID from the public_id field and generates the
     * current timestamp for submission time. The provider list is extracted
     * from the result object keys.
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
            publicId: (string) ($data['public_id'] ?? ''),
            providers: $providers,
            submittedAt: new DateTimeImmutable(),
        );
    }
}
