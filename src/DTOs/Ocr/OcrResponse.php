<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Ocr;

use Droath\Edenai\DTOs\AbstractResponseDTO;

/**
 * Response DTO for synchronous OCR API endpoint.
 *
 * Contains the aggregated results from all requested providers, with each
 * provider's extracted text and bounding box coordinates accessible through
 * the results array.
 *
 * Example API response structure:
 * ```json
 * {
 *   "google": {
 *     "status": "success",
 *     "text": "Hello World",
 *     "bounding_boxes": [...]
 *   },
 *   "amazon": {
 *     "status": "success",
 *     "text": "Hello World",
 *     "bounding_boxes": [...]
 *   }
 * }
 * ```
 *
 * Usage:
 * ```php
 * $response = OcrResponse::fromResponse($apiData);
 * foreach ($response->results as $result) {
 *     echo "{$result->provider}: {$result->text}";
 * }
 * ```
 */
final class OcrResponse extends AbstractResponseDTO
{
    /**
     * Create a new OCR response DTO.
     *
     * @param array<int, OcrProviderResult> $results Array of provider-specific OCR results
     */
    public function __construct(
        public readonly array $results,
    ) {}

    /**
     * Create a response DTO from API response data.
     *
     * Parses the Eden AI OCR response, iterating over provider-keyed results
     * and transforming each into strongly typed OcrProviderResult DTOs.
     * Unknown keys are ignored, and the response is parsed leniently.
     *
     * @param array<string, mixed> $data The API response data (provider-keyed)
     *
     * @return static The constructed response DTO
     */
    public static function fromResponse(array $data): static
    {
        $results = [];

        foreach ($data as $provider => $providerData) {
            if (is_array($providerData)) {
                $results[] = OcrProviderResult::fromResponse(
                    $provider,
                    $providerData
                );
            }
        }

        return new self(
            results: $results,
        );
    }
}
