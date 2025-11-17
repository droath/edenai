<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Audio;

use DateMalformedStringException;
use Droath\Edenai\DTOs\AbstractResponseDTO;

/**
 * Response DTO for text-to-speech async job list endpoint.
 *
 * Contains a list of all text-to-speech async jobs with their metadata, including
 * job identifiers, provider lists, status, and creation timestamps. This allows
 * clients to track and manage multiple async TTS operations.
 *
 * Each job summary includes the list of providers processing the request, the
 * number of providers, successful provider count, job state, and creation time.
 *
 * Example API response:
 * ```json
 * {
 *   "jobs": [
 *     {
 *       "providers": "['amazon', 'lovoai']",
 *       "nb": 2,
 *       "nb_ok": 2,
 *       "public_id": "a767b984-3178-43ce-9816-d3369efe5286",
 *       "state": "finished",
 *       "created_at": "2025-11-14 12:34:46.602755"
 *     }
 *   ]
 * }
 * ```
 *
 * Usage:
 * ```php
 * $response = TextToSpeechAsyncJobListResponse::fromResponse($apiData);
 * foreach ($response->jobs as $job) {
 *     echo "Job {$job->publicId}: {$job->state} ({$job->nbOk}/{$job->nb} providers)\n";
 * }
 * ```
 */
final class TextToSpeechAsyncJobListResponse extends AbstractResponseDTO
{
    /**
     * Create a new text-to-speech async job list response DTO.
     *
     * @param array<int, JobSummary> $jobs Array of job summary DTOs
     */
    public function __construct(
        public readonly array $jobs,
    ) {}

    /**
     * Create a response DTO from API response data.
     *
     * Parses the Eden AI job list response and transforms each job entry into
     * a strongly typed JobSummary DTO. Handles empty job lists gracefully.
     *
     * @param array<string, mixed> $data The API response data
     *
     * @return static The constructed response DTO
     *
     * @throws DateMalformedStringException
     */
    public static function fromResponse(array $data): static
    {
        $jobs = [];

        if (isset($data['jobs']) && is_array($data['jobs'])) {
            foreach ($data['jobs'] as $jobData) {
                if (is_array($jobData)) {
                    $jobs[] = JobSummary::fromResponse($jobData);
                }
            }
        }

        return new self(jobs: $jobs);
    }
}
