<?php

declare(strict_types=1);

namespace Droath\Edenai\Resources;

use JsonException;
use DateMalformedStringException;
use Droath\Edenai\Traits\FileUploadTrait;
use Http\Discovery\Psr17FactoryDiscovery;
use Droath\Edenai\DTOs\Audio\TextToSpeechRequest;
use Droath\Edenai\DTOs\Audio\TextToSpeechResponse;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncRequest;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncRequest;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncResponse;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncResponse;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncJobListResponse;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncJobResultResponse;

/**
 * AudioResource implementation for Eden AI audio API endpoints.
 *
 * This resource provides access to audio API operations:
 * - Speech-to-text async: Transcribe audio files asynchronously
 * - Text-to-speech sync: Generate audio from text synchronously
 * - Text-to-speech async: Generate audio from text asynchronously
 * - Text-to-speech async job management: Retrieve results, list jobs, delete jobs
 *
 * The resource uses FileUploadTrait for multipart/form-data file uploads
 * in speech-to-text operations, while text-to-speech operations use
 * standard JSON payloads.
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
 * $audioResource = new AudioResource($client);
 *
 * // Speech-to-text async
 * $sttRequest = new SpeechToTextAsyncRequest(
 *     file: '/path/to/audio.mp3',
 *     providers: [ServiceProviderEnum::GOOGLE],
 *     language: 'en',
 * );
 * $sttResponse = $audioResource->speechToTextAsync($sttRequest);
 * echo "Job ID: {$sttResponse->jobId}";
 *
 * // Text-to-speech sync
 * $ttsRequest = new TextToSpeechRequest(
 *     text: 'Hello world',
 *     providers: [ServiceProviderEnum::AMAZON],
 *     language: 'en',
 * );
 * $ttsResponse = $audioResource->textToSpeech($ttsRequest);
 * file_put_contents('output.mp3', $ttsResponse->audioData);
 *
 * // Text-to-speech async
 * $ttsAsyncRequest = new TextToSpeechAsyncRequest(
 *     text: 'Hello async world',
 *     providers: [ServiceProviderEnum::MICROSOFT],
 *     language: 'en',
 * );
 * $ttsAsyncResponse = $audioResource->textToSpeechAsync($ttsAsyncRequest);
 * echo "Job ID: {$ttsAsyncResponse->jobId}";
 *
 * // Get async job results
 * $jobResult = $audioResource->getTextToSpeechAsyncJobResult($ttsAsyncResponse->jobId);
 * if ($jobResult->status === 'finished') {
 *     foreach ($jobResult->results as $result) {
 *         file_put_contents("audio_{$result->provider}.mp3", $result->audioData);
 *     }
 * }
 *
 * // List all async jobs
 * $jobList = $audioResource->listTextToSpeechAsyncJobs();
 * foreach ($jobList->jobs as $job) {
 *     echo "Job {$job->publicId}: {$job->state}\n";
 * }
 *
 * // Delete all async jobs
 * $audioResource->deleteTextToSpeechAsyncJobs();
 * ```
 */
final class AudioResource extends AbstractResource
{
    use FileUploadTrait;

    /**
     * Get the base path for audio endpoints.
     *
     * All audio operations are prefixed with '/v2/audio'.
     * For example,
     * - speechToTextAsync() -> POST /v2/audio/speech_to_text_async
     * - textToSpeech() -> POST /v2/audio/text_to_speech
     * - textToSpeechAsync() -> POST /v2/audio/text_to_speech_async
     * - getTextToSpeechAsyncJobResult() -> GET /v2/audio/text_to_speech_async/{public_id}/
     * - listTextToSpeechAsyncJobs() -> GET /v2/audio/text_to_speech_async/
     * - deleteTextToSpeechAsyncJobs() -> DELETE /v2/audio/text_to_speech_async/
     *
     * @return string The base path '/v2/audio'
     */
    public function getBasePath(): string
    {
        return '/v2/audio';
    }

    /**
     * Convert speech to text asynchronously.
     *
     * Uploads an audio file to Eden AI for asynchronous transcription by the
     * specified AI providers. Returns a job ID that can be used to poll for
     * transcription results once processing is complete.
     *
     * This method uses multipart/form-data for file upload via FileUploadTrait.
     * The file is validated for existence, readability, and supported format
     * (mp3, wav, flac, ogg) before upload.
     *
     * @param SpeechToTextAsyncRequest $request The validated request with a file and parameters
     *
     * @return SpeechToTextAsyncResponse The job tracking metadata (job ID, providers, timestamp)
     *
     * @throws \Droath\Edenai\Exceptions\FileUploadException If a file is missing or unreadable
     * @throws \Droath\Edenai\Exceptions\ValidationException If a file format is unsupported
     * @throws \Droath\Edenai\Exceptions\ApiException On HTTP errors from the API
     * @throws JsonException If response JSON is invalid
     */
    public function speechToTextAsync(SpeechToTextAsyncRequest $request): SpeechToTextAsyncResponse
    {
        // Create a multipart request with file upload
        $multipartRequest = $this->createMultipartRequest(
            $request->file,
            $request->toArray()
        );

        // Build full URI for the endpoint
        $uri = rtrim($this->client->getBaseUrl(), '/').$this->getBasePath().'/speech_to_text_async';

        // Update request URI (createMultipartRequest creates request with empty URI)
        $multipartRequest = $multipartRequest->withUri(
            $multipartRequest->getUri()->withPath(parse_url($uri, PHP_URL_PATH) ?? '')
        );

        // Update the request with the correct URI
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $finalRequest = $requestFactory->createRequest('POST', $uri);

        // Copy headers from multipart request
        foreach ($multipartRequest->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $finalRequest = $finalRequest->withHeader($name, $value);
            }
        }

        // Copy body from a multipart request
        $finalRequest = $finalRequest->withBody($multipartRequest->getBody());

        // Send request through ApiClient middleware pipeline
        $response = $this->client->sendRequest($finalRequest);

        // Parse response with JSON_THROW_ON_ERROR
        $data = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // Return strongly typed response DTO
        return SpeechToTextAsyncResponse::fromResponse($data);
    }

    /**
     * Convert text to speech synchronously.
     *
     * Sends text to Eden AI for immediate audio generation by the specified
     * AI providers. Returns the generated audio data (decoded from Base64)
     * ready for file writing or streaming, along with content type and
     * optional duration metadata.
     *
     * This method uses a standard JSON payload for the request body.
     *
     * @param TextToSpeechRequest $request The validated request with text and parameters
     *
     * @return TextToSpeechResponse The generated audio data with metadata
     *
     * @throws JsonException If response JSON is invalid
     */
    public function textToSpeech(TextToSpeechRequest $request): TextToSpeechResponse
    {
        // Send POST request with JSON payload
        $response = $this->post('/text_to_speech', $request->toArray());

        // Parse response with JSON_THROW_ON_ERROR
        $data = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // Return strongly typed response DTO (automatically decodes Base64 audio)
        return TextToSpeechResponse::fromResponse($data);
    }

    /**
     * Convert text to speech asynchronously.
     *
     * Sends text to Eden AI for asynchronous audio generation by the specified
     * AI providers. Returns a job ID that can be used to poll for the generated
     * audio once processing is complete.
     *
     * This method uses a standard JSON payload for the request body.
     *
     * @param TextToSpeechAsyncRequest $request The validated request with text and parameters
     *
     * @return TextToSpeechAsyncResponse The job tracking metadata (job ID, providers, timestamp)
     *
     * @throws JsonException If response JSON is invalid
     */
    public function textToSpeechAsync(TextToSpeechAsyncRequest $request): TextToSpeechAsyncResponse
    {
        // Send POST request with JSON payload
        $response = $this->post('/text_to_speech_async', $request->toArray());

        // Parse response with JSON_THROW_ON_ERROR
        $data = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // Return strongly typed response DTO
        return TextToSpeechAsyncResponse::fromResponse($data);
    }

    /**
     * Get text-to-speech async job result by job ID.
     *
     * Retrieves the complete results of an asynchronous text-to-speech job
     * including job status, provider-specific results, and generated audio data.
     * Use this method to poll for job completion and retrieve audio files once
     * processing is finished.
     *
     * The response includes the overall job status ('finished', 'processing', 'failed')
     * and individual provider results with audio data (decoded from Base64), voice
     * type, and resource URLs.
     *
     * @param string $publicId The unique public identifier for the async job
     * @param bool $responseAsDict Whether to return the response as dictionary (default: true)
     * @param bool $showBase64 Whether to include Base64-encoded audio in response (default: true)
     * @param bool $showOriginalResponse Whether to include the original provider response (default: false)
     *
     * @return TextToSpeechAsyncJobResultResponse The job result with status and provider outputs
     *
     * @throws JsonException If response JSON is invalid
     */
    public function getTextToSpeechAsyncJobResult(
        string $publicId,
        bool $responseAsDict = true,
        bool $showBase64 = true,
        bool $showOriginalResponse = false,
    ): TextToSpeechAsyncJobResultResponse {
        // Build query parameters
        $queryParams = http_build_query([
            'response_as_dict' => $responseAsDict ? 'true' : 'false',
            'show_base_64' => $showBase64 ? 'true' : 'false',
            'show_original_response' => $showOriginalResponse ? 'true' : 'false',
        ]);

        // Send GET request with public ID in path and query parameters
        $response = $this->get("/text_to_speech_async/{$publicId}?{$queryParams}");

        // Parse response with JSON_THROW_ON_ERROR
        $data = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // Return strongly typed response DTO
        return TextToSpeechAsyncJobResultResponse::fromResponse($data);
    }

    /**
     * List all text-to-speech async jobs.
     *
     * Retrieves a list of all text-to-speech async jobs with their metadata,
     * including job identifiers, provider information, status, and creation
     * timestamps.
     *
     * Use this method to track and manage multiple async TTS operations.
     *
     * Each job summary includes the list of providers, successful provider
     * count, job state, and creation time. This allows clients to monitor job
     * progress across multiple submissions.
     *
     * @param bool $responseAsDict Whether to return response as dictionary
     *                             (default: true)
     * @param bool $showBase64 Whether to include Base64-encoded audio in
     *                         response (default: true)
     * @param bool $showOriginalResponse Whether to include the original provider
     *                                   response (default: false)
     *
     * @return TextToSpeechAsyncJobListResponse The list of job summaries
     *
     * @throws JsonException If response JSON is invalid
     * @throws DateMalformedStringException
     */
    public function listTextToSpeechAsyncJobs(
        bool $responseAsDict = true,
        bool $showBase64 = true,
        bool $showOriginalResponse = false,
    ): TextToSpeechAsyncJobListResponse {
        // Build query parameters
        $queryParams = http_build_query([
            'response_as_dict' => $responseAsDict ? 'true' : 'false',
            'show_base_64' => $showBase64 ? 'true' : 'false',
            'show_original_response' => $showOriginalResponse ? 'true' : 'false',
        ]);

        // Send GET request with query parameters
        $response = $this->get("/text_to_speech_async?{$queryParams}");

        // Parse response with JSON_THROW_ON_ERROR
        $data = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // Return strongly typed response DTO
        return TextToSpeechAsyncJobListResponse::fromResponse($data);
    }

    /**
     * Delete all text-to-speech async jobs.
     *
     * Permanently deletes all text-to-speech async jobs and their associated
     * data from the Eden AI system.
     *
     * This operation cannot be undone and will remove all job metadata and
     * generated audio files.
     *
     * Use this method to clean up completed or failed jobs and free storage
     * resources. The API returns a 204 No Content response on successful
     * deletion.
     *
     * @throws JsonException
     */
    public function deleteTextToSpeechAsyncJobs(): void
    {
        // Send DELETE request (API returns 204 No Content)
        $this->delete('/text_to_speech_async');
    }
}
