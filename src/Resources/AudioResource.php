<?php

declare(strict_types=1);

namespace Droath\Edenai\Resources;

use JsonException;
use Droath\Edenai\Traits\FileUploadTrait;
use Http\Discovery\Psr17FactoryDiscovery;
use Droath\Edenai\DTOs\Audio\TextToSpeechRequest;
use Droath\Edenai\DTOs\Audio\TextToSpeechResponse;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncRequest;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncRequest;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncResponse;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncResponse;

/**
 * AudioResource implementation for Eden AI audio API endpoints.
 *
 * This resource provides access to three audio API operations:
 * - Speech-to-text async: Transcribe audio files asynchronously
 * - Text-to-speech sync: Generate audio from text synchronously
 * - Text-to-speech async: Generate audio from text asynchronously
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
}
