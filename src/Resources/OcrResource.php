<?php

declare(strict_types=1);

namespace Droath\Edenai\Resources;

use JsonException;
use Droath\Edenai\DTOs\Ocr\OcrRequest;
use Droath\Edenai\DTOs\Ocr\OcrResponse;
use Http\Discovery\Psr17FactoryDiscovery;
use Droath\Edenai\DTOs\Ocr\OcrAsyncRequest;
use Droath\Edenai\DTOs\Ocr\OcrAsyncResponse;
use Droath\Edenai\Traits\ImageFileUploadTrait;
use Droath\Edenai\Exceptions\FileUploadException;
use Droath\Edenai\Exceptions\ValidationException;
use Droath\Edenai\DTOs\Ocr\OcrAsyncJobListResponse;
use Droath\Edenai\DTOs\Ocr\OcrAsyncJobResultResponse;

/**
 * OcrResource implementation for Eden AI OCR API endpoints.
 *
 * This resource provides access to OCR (Optical Character Recognition) API operations:
 * - Synchronous OCR: Extract text from images immediately
 * - Asynchronous OCR: Submit images for background text extraction
 * - Async job management: List jobs, retrieve results, delete jobs
 *
 * The resource supports two modes of file handling:
 * - Path-based: Uses multipart/form-data file upload via ImageFileUploadTrait
 * - URL-based: Sends JSON payload with file_url for remote file access
 *
 * All methods return strongly typed response DTOs parsed from API responses.
 * Authentication is handled automatically via ApiClient Bearer token middleware.
 *
 * Usage example:
 * ```php
 * $client = new ApiClient(
 *     baseUrl: 'https://api.edenai.run',
 *     apiKey: 'your-api-key',
 * );
 *
 * $ocrResource = new OcrResource($client);
 *
 * // Synchronous OCR with local file
 * $ocrRequest = new OcrRequest(
 *     file: FileDTO::fromPath('/path/to/image.png'),
 *     providers: [ServiceProviderEnum::GOOGLE],
 *     language: 'en',
 * );
 * $ocrResponse = $ocrResource->ocr($ocrRequest);
 * foreach ($ocrResponse->results as $result) {
 *     echo "{$result->provider}: {$result->text}\n";
 * }
 *
 * // Synchronous OCR with URL
 * $ocrRequest = new OcrRequest(
 *     file: FileDTO::fromUrl('https://example.com/image.png'),
 *     providers: [ServiceProviderEnum::AMAZON],
 *     language: 'en',
 * );
 * $ocrResponse = $ocrResource->ocr($ocrRequest);
 *
 * // Asynchronous OCR
 * $asyncRequest = new OcrAsyncRequest(
 *     file: FileDTO::fromUrl('https://example.com/large-image.png'),
 *     providers: [ServiceProviderEnum::GOOGLE, ServiceProviderEnum::AMAZON],
 *     language: 'en',
 * );
 * $asyncResponse = $ocrResource->ocrAsync($asyncRequest);
 * echo "Job ID: {$asyncResponse->publicId}";
 *
 * // Get async job result
 * $jobResult = $ocrResource->getOcrAsyncJobResult($asyncResponse->publicId);
 * if ($jobResult->status === JobStatusEnum::FINISHED) {
 *     foreach ($jobResult->results as $result) {
 *         echo "{$result->provider}: {$result->text}\n";
 *     }
 * }
 *
 * // List all async jobs
 * $jobList = $ocrResource->listOcrAsyncJobs();
 * foreach ($jobList->jobs as $job) {
 *     echo "Job {$job->publicId}: {$job->state}\n";
 * }
 *
 * // Delete all async jobs
 * $ocrResource->deleteOcrAsyncJobs();
 * ```
 */
final class OcrResource extends AbstractResource
{
    use ImageFileUploadTrait;

    /**
     * Get the base path for OCR endpoints.
     *
     * All OCR operations are prefixed with '/v2/ocr'.
     * For example:
     * - ocr() -> POST /v2/ocr/ocr
     * - ocrAsync() -> POST /v2/ocr/ocr_async
     * - listOcrAsyncJobs() -> GET /v2/ocr/ocr_async
     * - getOcrAsyncJobResult() -> GET /v2/ocr/ocr_async/{public_id}
     * - deleteOcrAsyncJobs() -> DELETE /v2/ocr/ocr_async
     *
     * @return string The base path '/v2/ocr'
     */
    public function getBasePath(): string
    {
        return '/v2/ocr';
    }

    /**
     * Extract text from an image synchronously.
     *
     * Submits an image to Eden AI for immediate OCR processing by the specified
     * AI providers. Returns extracted text with bounding box coordinates for
     * each detected text region.
     *
     * For path-based files, uses multipart/form-data for file upload via
     * ImageFileUploadTrait. For URL-based files, sends a JSON payload with
     * the file_url field for the API to fetch remotely.
     *
     * @param OcrRequest $request The validated request with file and parameters
     *
     * @return OcrResponse The OCR results from all requested providers
     *
     * @throws FileUploadException If a path-based file is missing or unreadable
     * @throws ValidationException If a path-based file format is unsupported
     * @throws JsonException If response JSON is invalid
     */
    public function ocr(OcrRequest $request): OcrResponse
    {
        if ($request->file->isPath()) {
            return $this->ocrWithMultipart($request);
        }

        return $this->ocrWithJson($request);
    }

    /**
     * Submit an image for asynchronous OCR processing.
     *
     * Submits an image to Eden AI for background OCR processing by the specified
     * AI providers. Returns a job ID that can be used to poll for results once
     * processing is complete.
     *
     * For path-based files, uses multipart/form-data for file upload via
     * ImageFileUploadTrait. For URL-based files, sends a JSON payload with
     * the file_url field for the API to fetch remotely.
     *
     * @param OcrAsyncRequest $request The validated request with file and parameters
     *
     * @return OcrAsyncResponse The job tracking metadata (job ID, providers, timestamp)
     *
     * @throws FileUploadException If a path-based file is missing or unreadable
     * @throws ValidationException If a path-based file format is unsupported
     * @throws JsonException If response JSON is invalid
     */
    public function ocrAsync(OcrAsyncRequest $request): OcrAsyncResponse
    {
        if ($request->file->isPath()) {
            return $this->ocrAsyncWithMultipart($request);
        }

        return $this->ocrAsyncWithJson($request);
    }

    /**
     * List all OCR async jobs.
     *
     * Retrieves a list of all OCR async jobs with their metadata, including
     * job identifiers, provider information, status, and creation timestamps.
     *
     * Use this method to track and manage multiple async OCR operations.
     *
     * @param bool $responseAsDict Whether to return response as dictionary (default: true)
     * @param bool $showOriginalResponse Whether to include the original provider response (default: false)
     *
     * @return OcrAsyncJobListResponse The list of job summaries
     *
     * @throws JsonException If response JSON is invalid
     */
    public function listOcrAsyncJobs(
        bool $responseAsDict = true,
        bool $showOriginalResponse = false,
    ): OcrAsyncJobListResponse {
        $queryParams = http_build_query([
            'response_as_dict' => $responseAsDict ? 'true' : 'false',
            'show_original_response' => $showOriginalResponse ? 'true' : 'false',
        ]);

        $response = $this->get("/ocr_async?{$queryParams}");

        $data = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return OcrAsyncJobListResponse::fromResponse($data);
    }

    /**
     * Get OCR async job result by job ID.
     *
     * Retrieves the complete results of an asynchronous OCR job including job
     * status, provider-specific results, extracted text, and bounding boxes.
     * Use this method to poll for job completion and retrieve OCR results once
     * processing is finished.
     *
     * The response includes the overall job status ('finished', 'processing', 'failed')
     * and individual provider results with extracted text and bounding box coordinates.
     *
     * @param string $publicId The unique public identifier for the async job
     * @param bool $responseAsDict Whether to return the response as dictionary (default: true)
     * @param bool $showOriginalResponse Whether to include the original provider response (default: false)
     *
     * @return OcrAsyncJobResultResponse The job result with status and provider outputs
     *
     * @throws JsonException If response JSON is invalid
     */
    public function getOcrAsyncJobResult(
        string $publicId,
        bool $responseAsDict = true,
        bool $showOriginalResponse = false,
    ): OcrAsyncJobResultResponse {
        $queryParams = http_build_query([
            'response_as_dict' => $responseAsDict ? 'true' : 'false',
            'show_original_response' => $showOriginalResponse ? 'true' : 'false',
        ]);

        $response = $this->get("/ocr_async/{$publicId}?{$queryParams}");

        $data = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return OcrAsyncJobResultResponse::fromResponse($data);
    }

    /**
     * Delete all OCR async jobs.
     *
     * Permanently deletes all OCR async jobs and their associated data from
     * the Eden AI system.
     *
     * This operation cannot be undone and will remove all job metadata and
     * OCR results.
     *
     * Use this method to clean up completed or failed jobs. The API returns
     * a 204 No Content response on successful deletion.
     *
     * @throws JsonException
     */
    public function deleteOcrAsyncJobs(): void
    {
        $this->delete('/ocr_async');
    }

    /**
     * Perform synchronous OCR with multipart/form-data file upload.
     *
     * @param OcrRequest $request The OCR request with path-based file
     *
     * @return OcrResponse The OCR results from all requested providers
     *
     * @throws FileUploadException If file is missing or unreadable
     * @throws ValidationException If file format is unsupported
     * @throws JsonException If response JSON is invalid
     */
    private function ocrWithMultipart(OcrRequest $request): OcrResponse
    {
        $multipartRequest = $this->createImageMultipartRequest(
            $request->file->getPath(),
            $request->toArray()
        );

        $uri = rtrim($this->client->getBaseUrl(), '/').$this->getBasePath().'/ocr';

        $multipartRequest = $multipartRequest->withUri(
            $multipartRequest->getUri()->withPath(parse_url($uri, PHP_URL_PATH) ?? '')
        );

        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $finalRequest = $requestFactory->createRequest('POST', $uri);

        foreach ($multipartRequest->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $finalRequest = $finalRequest->withHeader($name, $value);
            }
        }

        $finalRequest = $finalRequest->withBody($multipartRequest->getBody());

        $response = $this->client->sendRequest($finalRequest);

        $data = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return OcrResponse::fromResponse($data);
    }

    /**
     * Perform synchronous OCR with JSON payload for URL-based files.
     *
     * @param OcrRequest $request The OCR request with URL-based file
     *
     * @return OcrResponse The OCR results from all requested providers
     *
     * @throws JsonException If response JSON is invalid
     */
    private function ocrWithJson(OcrRequest $request): OcrResponse
    {
        $response = $this->post('/ocr', $request->toArray());

        $data = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return OcrResponse::fromResponse($data);
    }

    /**
     * Submit async OCR with multipart/form-data file upload.
     *
     * @param OcrAsyncRequest $request The async OCR request with path-based file
     *
     * @return OcrAsyncResponse The job tracking metadata
     *
     * @throws FileUploadException If file is missing or unreadable
     * @throws ValidationException If file format is unsupported
     * @throws JsonException If response JSON is invalid
     */
    private function ocrAsyncWithMultipart(OcrAsyncRequest $request): OcrAsyncResponse
    {
        $multipartRequest = $this->createImageMultipartRequest(
            $request->file->getPath(),
            $request->toArray()
        );

        $uri = rtrim($this->client->getBaseUrl(), '/').$this->getBasePath().'/ocr_async';

        $multipartRequest = $multipartRequest->withUri(
            $multipartRequest->getUri()->withPath(parse_url($uri, PHP_URL_PATH) ?? '')
        );

        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $finalRequest = $requestFactory->createRequest('POST', $uri);

        foreach ($multipartRequest->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $finalRequest = $finalRequest->withHeader($name, $value);
            }
        }

        $finalRequest = $finalRequest->withBody($multipartRequest->getBody());

        $response = $this->client->sendRequest($finalRequest);

        $data = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return OcrAsyncResponse::fromResponse($data);
    }

    /**
     * Submit async OCR with JSON payload for URL-based files.
     *
     * @param OcrAsyncRequest $request The async OCR request with URL-based file
     *
     * @return OcrAsyncResponse The job tracking metadata
     *
     * @throws JsonException If response JSON is invalid
     */
    private function ocrAsyncWithJson(OcrAsyncRequest $request): OcrAsyncResponse
    {
        $response = $this->post('/ocr_async', $request->toArray());

        $data = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return OcrAsyncResponse::fromResponse($data);
    }
}
