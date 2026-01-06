<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Ocr;

use DateMalformedStringException;
use Droath\Edenai\DTOs\Audio\JobSummary;
use Droath\Edenai\DTOs\AbstractResponseDTO;

/**
 * Response DTO for OCR async job list endpoint.
 *
 * Contains an array of job summaries representing all asynchronous OCR jobs
 * submitted to the API. Each job summary includes provider information,
 * processing counts, status, and creation timestamp.
 *
 * This DTO reuses the JobSummary DTO from Audio since the structure is
 * identical between Audio and OCR async job listings.
 *
 * Example API response structure:
 * ```json
 * [
 *   {
 *     "providers": "['google', 'amazon']",
 *     "nb": 2,
 *     "nb_ok": 2,
 *     "public_id": "job-123",
 *     "state": "finished",
 *     "created_at": "2024-12-15T10:30:00Z"
 *   }
 * ]
 * ```
 *
 * Usage:
 * ```php
 * $response = OcrAsyncJobListResponse::fromResponse($apiData);
 * foreach ($response->jobs as $job) {
 *     echo "{$job->publicId}: {$job->state}";
 * }
 * ```
 */
final class OcrAsyncJobListResponse extends AbstractResponseDTO
{
    /**
     * Create a new OCR async job list response DTO.
     *
     * @param array<int, JobSummary> $jobs Array of job summary objects
     */
    public function __construct(
        public readonly array $jobs,
    ) {}

    /**
     * Create a response DTO from API response data.
     *
     * Parses the Eden AI async job list response, transforming each job entry
     * into a strongly typed JobSummary DTO. The response is expected to be an
     * array of job objects.
     *
     * @param array<int|string, mixed> $data The API response data (array of
     *                                       job objects)
     *
     * @return static The constructed response DTO
     *
     * @throws DateMalformedStringException
     */
    public static function fromResponse(array $data): static
    {
        $jobs = [];

        foreach ($data as $jobData) {
            if (is_array($jobData)) {
                $jobs[] = JobSummary::fromResponse($jobData);
            }
        }

        return new self(
            jobs: $jobs,
        );
    }
}
