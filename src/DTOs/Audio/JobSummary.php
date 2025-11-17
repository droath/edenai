<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Audio;

use DateTimeImmutable;
use DateMalformedStringException;

/**
 * Job summary DTO for individual text-to-speech async jobs.
 *
 * Represents a summary of a single async TTS job including provider
 * information, processing counts, status, and creation timestamp.
 */
final readonly class JobSummary
{
    /**
     * Create a new job summary DTO.
     *
     * @param string $providers String representation of provider list (e.g.,
     *                          "['amazon', 'lovoai']")
     * @param int $nb Total number of providers processing the job
     * @param int $nbOk Number of providers that completed successfully
     * @param string $publicId The unique public identifier for the job
     * @param string $state The job state (e.g., 'finished', 'processing',
     *                      'failed')
     * @param DateTimeImmutable $createdAt The timestamp when the job was
     *                                     created
     */
    public function __construct(
        public string $providers,
        public int $nb,
        public int $nbOk,
        public string $publicId,
        public string $state,
        public DateTimeImmutable $createdAt,
    ) {}

    /**
     * Create a job summary DTO from API response data.
     *
     * Parses the job summary data and transforms the creation timestamp string
     * into a DateTimeImmutable object for type safety.
     *
     * @param array<string, mixed> $data The job summary data
     *
     * @return static The constructed job summary DTO
     *
     * @throws DateMalformedStringException
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            providers: (string) ($data['providers'] ?? ''),
            nb: (int) ($data['nb'] ?? 0),
            nbOk: (int) ($data['nb_ok'] ?? 0),
            publicId: (string) ($data['public_id'] ?? ''),
            state: (string) ($data['state'] ?? ''),
            createdAt: isset($data['created_at'])
                ? new DateTimeImmutable((string) $data['created_at'])
                : new DateTimeImmutable(),
        );
    }
}
