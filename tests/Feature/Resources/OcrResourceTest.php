<?php

declare(strict_types=1);

namespace Droath\Edenai\Tests\Feature\Resources;

use Mockery;
use DateTimeImmutable;
use Droath\Edenai\DTOs\FileDTO;
use Droath\Edenai\Http\ApiClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;
use Droath\Edenai\DTOs\Ocr\OcrRequest;
use Droath\Edenai\Enums\JobStatusEnum;
use Psr\Http\Message\RequestInterface;
use Droath\Edenai\DTOs\Ocr\OcrResponse;
use Psr\Http\Message\ResponseInterface;
use Droath\Edenai\Resources\OcrResource;
use Droath\Edenai\DTOs\Ocr\OcrAsyncRequest;
use Droath\Edenai\DTOs\Ocr\OcrAsyncResponse;
use Droath\Edenai\Enums\ServiceProviderEnum;
use Droath\Edenai\DTOs\Ocr\OcrAsyncJobListResponse;
use Droath\Edenai\DTOs\Ocr\OcrAsyncJobResultResponse;

/**
 * Feature tests for OcrResource covering OCR endpoint methods.
 *
 * These tests verify that the OcrResource properly:
 * - Creates multipart requests for file uploads (local file path)
 * - Creates JSON requests for URL-based operations
 * - Parses responses into strongly-typed response DTOs
 * - Integrates with ApiClient middleware pipeline for authentication
 */
describe('Feature: OcrResource - ocr() with file path', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('ocr creates multipart request for path-based file and returns OcrResponse', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'test-ocr-key-12345';

        $testFilePath = sys_get_temp_dir().'/test_ocr_image.png';
        file_put_contents($testFilePath, 'fake png content');

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(json_encode([
            'google' => [
                'status' => 'success',
                'text' => 'Hello World',
                'bounding_boxes' => [
                    ['text' => 'Hello', 'left' => 10.0, 'top' => 20.0, 'width' => 50.0, 'height' => 15.0],
                ],
                'cost' => 0.005,
            ],
        ]));
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request): bool {
                $contentType = $request->getHeader('Content-Type');
                return ! empty($contentType) && str_contains($contentType[0], 'multipart/form-data');
            }))
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new OcrResource($client);

        $requestDTO = new OcrRequest(
            file: FileDTO::fromPath($testFilePath),
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
        );

        $result = $resource->ocr($requestDTO);

        expect($result)->toBeInstanceOf(OcrResponse::class)
            ->and($result->results)->toHaveCount(1)
            ->and($result->results[0]->provider)->toBe('google')
            ->and($result->results[0]->text)->toBe('Hello World')
            ->and($result->results[0]->boundingBoxes)->toHaveCount(1);

        unlink($testFilePath);
    });
});

describe('Feature: OcrResource - ocr() with URL', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('ocr creates JSON request for URL-based file and returns OcrResponse', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'test-ocr-key-12345';

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(json_encode([
            'amazon' => [
                'status' => 'success',
                'text' => 'URL-based OCR result',
                'bounding_boxes' => [],
            ],
        ]));
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request): bool {
                $contentType = $request->getHeader('Content-Type');
                if (empty($contentType) || ! in_array('application/json', $contentType, true)) {
                    return false;
                }

                $body = $request->getBody()->getContents();
                $data = json_decode($body, true);

                return isset($data['file_url']) && $data['file_url'] === 'https://example.com/image.png';
            }))
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new OcrResource($client);

        $requestDTO = new OcrRequest(
            file: FileDTO::fromUrl('https://example.com/image.png'),
            providers: [ServiceProviderEnum::AMAZON],
            language: 'en',
        );

        $result = $resource->ocr($requestDTO);

        expect($result)->toBeInstanceOf(OcrResponse::class)
            ->and($result->results)->toHaveCount(1)
            ->and($result->results[0]->provider)->toBe('amazon')
            ->and($result->results[0]->text)->toBe('URL-based OCR result');
    });
});

describe('Feature: OcrResource - ocrAsync()', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('ocrAsync creates request and returns OcrAsyncResponse', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'test-ocr-key-12345';

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(json_encode([
            'public_id' => 'ocr-job-123',
            'results' => [
                'google' => ['status' => 'pending'],
                'amazon' => ['status' => 'pending'],
            ],
        ]));
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request): bool {
                return str_contains($request->getUri()->getPath(), '/ocr_async');
            }))
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new OcrResource($client);

        $requestDTO = new OcrAsyncRequest(
            file: FileDTO::fromUrl('https://example.com/image.png'),
            providers: [ServiceProviderEnum::GOOGLE, ServiceProviderEnum::AMAZON],
            language: 'en',
        );

        $result = $resource->ocrAsync($requestDTO);

        expect($result)->toBeInstanceOf(OcrAsyncResponse::class)
            ->and($result->publicId)->toBe('ocr-job-123')
            ->and($result->providers)->toBe(['google', 'amazon'])
            ->and($result->submittedAt)->toBeInstanceOf(DateTimeImmutable::class);
    });
});

describe('Feature: OcrResource - listOcrAsyncJobs()', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('listOcrAsyncJobs returns OcrAsyncJobListResponse', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'test-ocr-key-12345';

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(json_encode([
            [
                'providers' => "['google']",
                'nb' => 1,
                'nb_ok' => 1,
                'public_id' => 'ocr-job-list-123',
                'state' => 'finished',
                'created_at' => '2024-12-23T10:30:00Z',
            ],
        ]));
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request): bool {
                return $request->getMethod() === 'GET'
                    && str_contains($request->getUri()->getPath(), '/ocr_async');
            }))
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new OcrResource($client);

        $result = $resource->listOcrAsyncJobs();

        expect($result)->toBeInstanceOf(OcrAsyncJobListResponse::class)
            ->and($result->jobs)->toHaveCount(1)
            ->and($result->jobs[0]->publicId)->toBe('ocr-job-list-123')
            ->and($result->jobs[0]->state)->toBe('finished');
    });
});

describe('Feature: OcrResource - getOcrAsyncJobResult()', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('getOcrAsyncJobResult returns OcrAsyncJobResultResponse', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'test-ocr-key-12345';

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(json_encode([
            'public_id' => 'ocr-job-result-456',
            'status' => 'finished',
            'error' => null,
            'results' => [
                'google' => [
                    'status' => 'success',
                    'text' => 'Async OCR Result',
                    'bounding_boxes' => [],
                ],
            ],
        ]));
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request): bool {
                return $request->getMethod() === 'GET'
                    && str_contains($request->getUri()->getPath(), '/ocr_async/ocr-job-result-456');
            }))
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new OcrResource($client);

        $result = $resource->getOcrAsyncJobResult('ocr-job-result-456');

        expect($result)->toBeInstanceOf(OcrAsyncJobResultResponse::class)
            ->and($result->publicId)->toBe('ocr-job-result-456')
            ->and($result->status)->toBe(JobStatusEnum::FINISHED)
            ->and($result->results)->toHaveCount(1)
            ->and($result->results[0]->text)->toBe('Async OCR Result');
    });
});

describe('Feature: OcrResource - deleteOcrAsyncJobs()', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('deleteOcrAsyncJobs sends DELETE request and returns void', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'test-ocr-key-12345';

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(204);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn('');
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request): bool {
                return $request->getMethod() === 'DELETE'
                    && str_contains($request->getUri()->getPath(), '/ocr_async');
            }))
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new OcrResource($client);

        $resource->deleteOcrAsyncJobs();

        expect(true)->toBeTrue();
    });
});

describe('Feature: OcrResource - getBasePath()', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('getBasePath returns /v2/ocr', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: 'test-key',
        );

        $resource = new OcrResource($client);

        expect($resource->getBasePath())->toBe('/v2/ocr');
    });
});

describe('Feature: OcrResource - Authentication', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    test('authentication via Bearer token is inherited from AbstractResource', function (): void {
        $httpClient = Mockery::mock(ClientInterface::class);
        $apiKey = 'secret-bearer-token-xyz';

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn(json_encode([
            'google' => [
                'status' => 'success',
                'text' => 'Authenticated OCR',
                'bounding_boxes' => [],
            ],
        ]));
        $response->shouldReceive('getBody')->andReturn($stream);

        $httpClient->shouldReceive('sendRequest')
            ->once()
            ->with(Mockery::on(function (RequestInterface $request) use ($apiKey): bool {
                $authHeaders = $request->getHeader('Authorization');

                return in_array("Bearer {$apiKey}", $authHeaders, true);
            }))
            ->andReturn($response);

        $client = new ApiClient(
            httpClient: $httpClient,
            baseUrl: 'https://api.edenai.run',
            apiKey: $apiKey,
        );

        $resource = new OcrResource($client);

        $requestDTO = new OcrRequest(
            file: FileDTO::fromUrl('https://example.com/image.png'),
            providers: [ServiceProviderEnum::GOOGLE],
            language: 'en',
        );

        $result = $resource->ocr($requestDTO);

        expect($result)->toBeInstanceOf(OcrResponse::class);
    });
});
