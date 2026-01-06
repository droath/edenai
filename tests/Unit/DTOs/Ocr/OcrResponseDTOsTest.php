<?php

declare(strict_types=1);

use Droath\Edenai\Enums\JobStatusEnum;
use Droath\Edenai\DTOs\Ocr\BoundingBox;
use Droath\Edenai\DTOs\Ocr\OcrResponse;
use Droath\Edenai\DTOs\Audio\JobSummary;
use Droath\Edenai\DTOs\Ocr\OcrAsyncResponse;
use Droath\Edenai\DTOs\Ocr\OcrProviderResult;
use Droath\Edenai\DTOs\Ocr\OcrAsyncJobListResponse;
use Droath\Edenai\DTOs\Ocr\OcrAsyncJobResultResponse;

describe('OcrProviderResult', function () {
    test('fromResponse() parses provider data correctly', function () {
        $data = [
            'status' => 'success',
            'text' => 'Hello World extracted text',
            'bounding_boxes' => [
                ['text' => 'Hello', 'left' => 10.0, 'top' => 20.0, 'width' => 50.0, 'height' => 15.0],
                ['text' => 'World', 'left' => 70.0, 'top' => 20.0, 'width' => 60.0, 'height' => 15.0],
            ],
            'cost' => 0.005,
        ];

        $result = OcrProviderResult::fromResponse('google', $data);

        expect($result)->toBeInstanceOf(OcrProviderResult::class)
            ->and($result->provider)->toBe('google')
            ->and($result->status)->toBe('success')
            ->and($result->text)->toBe('Hello World extracted text')
            ->and($result->cost)->toBe(0.005)
            ->and($result->error)->toBeNull();
    });

    test('parses bounding_boxes into typed BoundingBox array', function () {
        $data = [
            'status' => 'success',
            'text' => 'Sample text',
            'bounding_boxes' => [
                ['text' => 'First', 'left' => 5.0, 'top' => 10.0, 'width' => 30.0, 'height' => 12.0],
                ['text' => 'Second', 'left' => 40.0, 'top' => 10.0, 'width' => 35.0, 'height' => 12.0],
                ['text' => 'Third', 'left' => 80.0, 'top' => 10.0, 'width' => 25.0, 'height' => 12.0],
            ],
        ];

        $result = OcrProviderResult::fromResponse('amazon', $data);

        expect($result->boundingBoxes)->toBeArray()
            ->and($result->boundingBoxes)->toHaveCount(3)
            ->and($result->boundingBoxes[0])->toBeInstanceOf(BoundingBox::class)
            ->and($result->boundingBoxes[0]->text)->toBe('First')
            ->and($result->boundingBoxes[1]->text)->toBe('Second')
            ->and($result->boundingBoxes[2]->text)->toBe('Third');
    });
});

describe('OcrResponse', function () {
    test('fromResponse() creates array of OcrProviderResult', function () {
        $data = [
            'google' => [
                'status' => 'success',
                'text' => 'Google extracted text',
                'bounding_boxes' => [],
            ],
            'amazon' => [
                'status' => 'success',
                'text' => 'Amazon extracted text',
                'bounding_boxes' => [],
            ],
        ];

        $response = OcrResponse::fromResponse($data);

        expect($response)->toBeInstanceOf(OcrResponse::class)
            ->and($response->results)->toBeArray()
            ->and($response->results)->toHaveCount(2)
            ->and($response->results[0])->toBeInstanceOf(OcrProviderResult::class)
            ->and($response->results[0]->provider)->toBe('google')
            ->and($response->results[1]->provider)->toBe('amazon');
    });
});

describe('OcrAsyncResponse', function () {
    test('fromResponse() parses job submission response', function () {
        $data = [
            'public_id' => 'job-abc-123',
            'results' => [
                'google' => ['status' => 'pending'],
                'amazon' => ['status' => 'pending'],
            ],
        ];

        $response = OcrAsyncResponse::fromResponse($data);

        expect($response)->toBeInstanceOf(OcrAsyncResponse::class)
            ->and($response->publicId)->toBe('job-abc-123')
            ->and($response->providers)->toBeArray()
            ->and($response->providers)->toContain('google')
            ->and($response->providers)->toContain('amazon')
            ->and($response->submittedAt)->toBeInstanceOf(DateTimeImmutable::class);
    });
});

describe('OcrAsyncJobListResponse', function () {
    test('fromResponse() parses job list', function () {
        $data = [
            [
                'providers' => "['google', 'amazon']",
                'nb' => 2,
                'nb_ok' => 2,
                'public_id' => 'job-123',
                'state' => 'finished',
                'created_at' => '2024-12-15T10:30:00Z',
            ],
            [
                'providers' => "['microsoft']",
                'nb' => 1,
                'nb_ok' => 0,
                'public_id' => 'job-456',
                'state' => 'processing',
                'created_at' => '2024-12-15T11:00:00Z',
            ],
        ];

        $response = OcrAsyncJobListResponse::fromResponse($data);

        expect($response)->toBeInstanceOf(OcrAsyncJobListResponse::class)
            ->and($response->jobs)->toBeArray()
            ->and($response->jobs)->toHaveCount(2)
            ->and($response->jobs[0])->toBeInstanceOf(JobSummary::class)
            ->and($response->jobs[0]->publicId)->toBe('job-123')
            ->and($response->jobs[0]->state)->toBe('finished')
            ->and($response->jobs[1]->publicId)->toBe('job-456');
    });
});

describe('OcrAsyncJobResultResponse', function () {
    test('fromResponse() parses job result with status', function () {
        $data = [
            'public_id' => 'job-result-789',
            'status' => 'finished',
            'error' => null,
            'results' => [
                'google' => [
                    'status' => 'success',
                    'text' => 'Extracted OCR text',
                    'bounding_boxes' => [
                        ['text' => 'Sample', 'left' => 0.0, 'top' => 0.0, 'width' => 50.0, 'height' => 10.0],
                    ],
                ],
            ],
        ];

        $response = OcrAsyncJobResultResponse::fromResponse($data);

        expect($response)->toBeInstanceOf(OcrAsyncJobResultResponse::class)
            ->and($response->publicId)->toBe('job-result-789')
            ->and($response->status)->toBe(JobStatusEnum::FINISHED)
            ->and($response->error)->toBeNull()
            ->and($response->results)->toBeArray()
            ->and($response->results)->toHaveCount(1)
            ->and($response->results[0])->toBeInstanceOf(OcrProviderResult::class)
            ->and($response->results[0]->text)->toBe('Extracted OCR text');
    });

    test('JobStatusEnum is correctly assigned from string status', function () {
        $testCases = [
            ['status' => 'pending', 'expected' => JobStatusEnum::PENDING],
            ['status' => 'processing', 'expected' => JobStatusEnum::PROCESSING],
            ['status' => 'finished', 'expected' => JobStatusEnum::FINISHED],
            ['status' => 'failed', 'expected' => JobStatusEnum::FAILED],
        ];

        foreach ($testCases as $testCase) {
            $data = [
                'public_id' => 'test-job',
                'status' => $testCase['status'],
                'results' => [],
            ];

            $response = OcrAsyncJobResultResponse::fromResponse($data);

            expect($response->status)->toBe($testCase['expected']);
        }
    });
});

describe('Response DTOs handle missing/null fields gracefully', function () {
    test('OcrProviderResult handles missing fields', function () {
        $data = [
            'status' => 'success',
        ];

        $result = OcrProviderResult::fromResponse('test', $data);

        expect($result->text)->toBe('')
            ->and($result->boundingBoxes)->toBe([])
            ->and($result->error)->toBeNull()
            ->and($result->cost)->toBeNull();
    });

    test('OcrAsyncJobResultResponse handles missing error and results', function () {
        $data = [
            'public_id' => 'job-empty',
            'status' => 'pending',
        ];

        $response = OcrAsyncJobResultResponse::fromResponse($data);

        expect($response->error)->toBeNull()
            ->and($response->results)->toBe([]);
    });
});
