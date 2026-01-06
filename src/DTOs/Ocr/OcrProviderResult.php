<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Ocr;

/**
 * Provider-specific result DTO for OCR operations.
 *
 * Represents the output from a single AI provider's OCR processing,
 * including status, extracted text, bounding boxes for detected text regions,
 * and optional error/cost information.
 *
 * Example API response structure:
 * ```json
 * {
 *   "status": "success",
 *   "text": "Hello World",
 *   "bounding_boxes": [
 *     {"text": "Hello", "left": 10, "top": 20, "width": 50, "height": 15}
 *   ],
 *   "cost": 0.005
 * }
 * ```
 *
 * Usage:
 * ```php
 * $result = OcrProviderResult::fromResponse('google', $providerData);
 * echo $result->text;
 * foreach ($result->boundingBoxes as $box) {
 *     echo "{$box->text} at ({$box->left}, {$box->top})";
 * }
 * ```
 */
final readonly class OcrProviderResult
{
    /**
     * Create a new OCR provider result DTO.
     *
     * @param string $provider The provider identifier (e.g., 'google', 'amazon', 'microsoft')
     * @param string $status The processing status (e.g., 'success', 'failed')
     * @param string $text The full extracted text from the image
     * @param array<int, BoundingBox> $boundingBoxes Array of bounding boxes with text coordinates
     * @param string|null $error Optional error message if the provider failed
     * @param float|null $cost Optional cost of the operation in provider-specific units
     */
    public function __construct(
        public string $provider,
        public string $status,
        public string $text,
        public array $boundingBoxes,
        public ?string $error,
        public ?float $cost,
    ) {}

    /**
     * Create an OCR provider result DTO from API response data.
     *
     * Parses the provider-specific response data, transforming the nested
     * bounding_boxes array into typed BoundingBox objects. Handles missing
     * optional fields with null or empty defaults.
     *
     * @param string $provider The provider identifier
     * @param array<string, mixed> $data The provider result data
     *
     * @return static The constructed provider result DTO
     */
    public static function fromResponse(string $provider, array $data): static
    {
        $boundingBoxes = [];

        if (isset($data['bounding_boxes']) && is_array($data['bounding_boxes'])) {
            foreach ($data['bounding_boxes'] as $boxData) {
                if (is_array($boxData)) {
                    $boundingBoxes[] = BoundingBox::fromResponse($boxData);
                }
            }
        }

        return new self(
            provider: $provider,
            status: (string) ($data['status'] ?? ''),
            text: (string) ($data['text'] ?? ''),
            boundingBoxes: $boundingBoxes,
            error: isset($data['error']) ? (string) $data['error'] : null,
            cost: isset($data['cost']) ? (float) $data['cost'] : null,
        );
    }
}
