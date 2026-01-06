<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs\Ocr;

/**
 * Bounding box value object for OCR text detection.
 *
 * Represents the spatial coordinates and extracted text from an OCR operation.
 * Coordinates define the position and dimensions of the detected text region.
 */
final readonly class BoundingBox
{
    /**
     * Create a new bounding box value object.
     *
     * @param string $text The extracted text content within the bounding region
     * @param float $left The left coordinate of the bounding box
     * @param float $top The top coordinate of the bounding box
     * @param float $width The width of the bounding box
     * @param float $height The height of the bounding box
     */
    public function __construct(
        public string $text,
        public float $left,
        public float $top,
        public float $width,
        public float $height,
    ) {}

    /**
     * Create a bounding box from API response data.
     *
     * Handles missing or null fields by providing sensible defaults:
     * - Empty string for missing text
     * - 0.0 for missing coordinate values
     *
     * @param array<string, mixed> $data The bounding box data from API response
     *
     * @return static The constructed bounding box value object
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            text: isset($data['text']) ? (string) $data['text'] : '',
            left: (float) ($data['left'] ?? 0.0),
            top: (float) ($data['top'] ?? 0.0),
            width: (float) ($data['width'] ?? 0.0),
            height: (float) ($data['height'] ?? 0.0),
        );
    }
}
