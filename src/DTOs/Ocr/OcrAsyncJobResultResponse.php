<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Ocr;

use Droath\Edenai\Enums\JobStatusEnum;
use Droath\Edenai\DTOs\AbstractResponseDTO;

/**
 * Response DTO for OCR async job result retrieval endpoint.
 *
 * Contains the complete results of an asynchronous OCR job, including job
 * status, error information, and provider-specific results with extracted
 * text and bounding boxes. Each provider result includes processing status,
 * full text, and individual bounding box coordinates.
 *
 * This DTO represents the full lifecycle state of an OCR async job and
 * allows clients to retrieve extracted text once processing is complete.
 *
 * Example API response structure:
 * ```json
 * {
 *   "public_id": "c0b09268-1514-49e2-9858-ac575da6225e",
 *   "status": "finished",
 *   "error": null,
 *   "results": {
 *     "google": {
 *       "status": "success",
 *       "text": "Hello World",
 *       "bounding_boxes": [
 *         {"text": "Hello", "left": 10, "top": 20, "width": 50, "height": 15}
 *       ]
 *     }
 *   }
 * }
 * ```
 *
 * Usage:
 * ```php
 * $response = OcrAsyncJobResultResponse::fromResponse($apiData);
 * if ($response->status === JobStatusEnum::FINISHED) {
 *     foreach ($response->results as $result) {
 *         echo "{$result->provider}: {$result->text}";
 *     }
 * }
 * ```
 */
final class OcrAsyncJobResultResponse extends AbstractResponseDTO
{
    /**
     * Create a new OCR async job result response DTO.
     *
     * @param string $publicId The unique public identifier for the job
     * @param JobStatusEnum $status The overall job status
     * @param string|null $error Optional error message if a job failed
     * @param array<int, OcrProviderResult> $results Array of provider-specific OCR results
     */
    public function __construct(
        public readonly string $publicId,
        public readonly JobStatusEnum $status,
        public readonly ?string $error,
        public readonly array $results,
    ) {}

    /**
     * Create a response DTO from API response data.
     *
     * Parses the Eden AI async job result response, extracting job metadata
     * and transforming provider results into strongly typed OcrProviderResult
     * DTOs. Handles nullable error fields and missing optional data gracefully.
     * The status string is parsed into a JobStatusEnum for type safety.
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
                if (is_string($provider) && is_array($providerData)) {
                    $results[] = OcrProviderResult::fromResponse($provider, $providerData);
                }
            }
        }

        $statusString = (string) ($data['status'] ?? 'pending');
        $status = JobStatusEnum::tryFrom($statusString) ?? JobStatusEnum::PENDING;

        return new self(
            publicId: (string) ($data['public_id'] ?? ''),
            status: $status,
            error: isset($data['error']) ? (string) $data['error'] : null,
            results: $results,
        );
    }
}
