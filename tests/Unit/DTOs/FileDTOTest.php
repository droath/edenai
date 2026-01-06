<?php

declare(strict_types=1);

use Droath\Edenai\DTOs\FileDTO;

describe('FileDTO', function (): void {
    test('fromPath creates valid path-based DTO', function (): void {
        $filePath = '/path/to/image.png';

        $dto = FileDTO::fromPath($filePath);

        expect($dto)->toBeInstanceOf(FileDTO::class)
            ->and($dto->getPath())->toBe($filePath)
            ->and($dto->getUrl())->toBeNull();
    });

    test('fromUrl creates valid URL-based DTO', function (): void {
        $fileUrl = 'https://example.com/image.png';

        $dto = FileDTO::fromUrl($fileUrl);

        expect($dto)->toBeInstanceOf(FileDTO::class)
            ->and($dto->getUrl())->toBe($fileUrl)
            ->and($dto->getPath())->toBeNull();
    });

    test('isPath returns true for path-based DTO', function (): void {
        $dto = FileDTO::fromPath('/path/to/document.pdf');

        expect($dto->isPath())->toBeTrue()
            ->and($dto->isUrl())->toBeFalse();
    });

    test('isUrl returns true for URL-based DTO', function (): void {
        $dto = FileDTO::fromUrl('https://example.com/document.pdf');

        expect($dto->isUrl())->toBeTrue()
            ->and($dto->isPath())->toBeFalse();
    });
});
