<?php

declare(strict_types=1);

use Droath\Edenai\DTOs\Ocr\BoundingBox;

describe('BoundingBox', function () {
    test('fromResponse() creates valid object from complete data', function () {
        $data = [
            'text' => 'Hello World',
            'left' => 10.5,
            'top' => 20.3,
            'width' => 100.0,
            'height' => 50.0,
        ];

        $boundingBox = BoundingBox::fromResponse($data);

        expect($boundingBox)->toBeInstanceOf(BoundingBox::class)
            ->and($boundingBox->text)->toBe('Hello World')
            ->and($boundingBox->left)->toBe(10.5)
            ->and($boundingBox->top)->toBe(20.3)
            ->and($boundingBox->width)->toBe(100.0)
            ->and($boundingBox->height)->toBe(50.0);
    });

    test('fromResponse() handles missing fields with defaults', function () {
        $data = [
            'text' => 'Partial data',
        ];

        $boundingBox = BoundingBox::fromResponse($data);

        expect($boundingBox->text)->toBe('Partial data')
            ->and($boundingBox->left)->toBe(0.0)
            ->and($boundingBox->top)->toBe(0.0)
            ->and($boundingBox->width)->toBe(0.0)
            ->and($boundingBox->height)->toBe(0.0);
    });

    test('properties are accessible and correctly typed', function () {
        $data = [
            'text' => 'Typed values',
            'left' => 15,
            'top' => 25,
            'width' => 200,
            'height' => 75,
        ];

        $boundingBox = BoundingBox::fromResponse($data);

        expect($boundingBox->text)->toBeString()
            ->and($boundingBox->left)->toBeFloat()
            ->and($boundingBox->top)->toBeFloat()
            ->and($boundingBox->width)->toBeFloat()
            ->and($boundingBox->height)->toBeFloat();

        $reflection = new ReflectionClass($boundingBox);
        expect($reflection->isReadOnly())->toBeTrue();
    });

    test('handles null and empty text gracefully', function () {
        $dataWithNull = [
            'text' => null,
            'left' => 5.0,
            'top' => 10.0,
            'width' => 50.0,
            'height' => 25.0,
        ];

        $dataWithEmpty = [];

        $boundingBoxWithNull = BoundingBox::fromResponse($dataWithNull);
        $boundingBoxWithEmpty = BoundingBox::fromResponse($dataWithEmpty);

        expect($boundingBoxWithNull->text)->toBe('')
            ->and($boundingBoxWithEmpty->text)->toBe('')
            ->and($boundingBoxWithEmpty->left)->toBe(0.0)
            ->and($boundingBoxWithEmpty->top)->toBe(0.0)
            ->and($boundingBoxWithEmpty->width)->toBe(0.0)
            ->and($boundingBoxWithEmpty->height)->toBe(0.0);
    });
});
