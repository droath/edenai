<?php

declare(strict_types=1);

namespace Droath\Edenai\Resources;

use JsonException;
use Droath\Edenai\Http\ApiClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Abstract base class for all API resource implementations.
 *
 * This class provides the foundation for concrete API resources by offering:
 * - HTTP verb methods (GET, POST, PUT, PATCH, DELETE)
 * - Access to the ApiClient middleware pipeline
 * - URI construction from base URL + base path + relative path
 * - Request object creation using PSR-17 factories
 *
 * Concrete resources must implement getBasePath() to define their API endpoint
 * prefix (e.g., '/text', '/image', '/audio'). The resource pattern allows
 * grouping related API operations under a common path.
 *
 * Usage pattern:
 * ```php
 * final class TextResource extends AbstractResource
 * {
 *     public function getBasePath(): string
 *     {
 *         return '/text';
 *     }
 *
 *     public function analyze(TextAnalysisRequest $request): TextAnalysisResponse
 *     {
 *         $response = $this->post('/analysis', $request->toArray());
 *         $data = json_decode($response->getBody()->getContents(), true);
 *         return TextAnalysisResponse::fromResponse($data);
 *     }
 * }
 * ```
 *
 * URI construction:
 * - Base URL: From ApiClient (e.g., 'https://api.edenai.run')
 * - Base Path: From getBasePath() (e.g., '/text')
 * - Relative Path: From method call (e.g., '/analysis')
 * - Full URI: 'https://api.edenai.run/text/analysis'
 */
abstract class AbstractResource
{
    /**
     * PSR-17 request factory for creating HTTP requests.
     */
    private readonly RequestFactoryInterface $requestFactory;

    /**
     * PSR-17 stream factory for creating request bodies.
     */
    private readonly StreamFactoryInterface $streamFactory;

    /**
     * Create a new resource instance.
     *
     * @param ApiClient $client The API client for HTTP operations
     */
    public function __construct(
        protected readonly ApiClient $client,
    ) {
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * Get the base path for this resource.
     *
     * The base path is prepended to all relative paths in HTTP verb methods.
     * It should start with a forward slash and represent the resource group.
     *
     * Examples:
     * - '/text' for text analysis operations
     * - '/image' for image generation operations
     * - '/audio' for audio processing operations
     *
     * @return string The base path for this resource (e.g., '/text')
     */
    abstract public function getBasePath(): string;

    /**
     * Send a GET request to the specified path.
     *
     * @param string $path Relative path (will be appended to base path)
     * @param array<string, string> $headers Optional headers to include
     *
     * @return ResponseInterface The PSR-7 HTTP response
     *
     * @throws JsonException
     */
    protected function get(string $path, array $headers = []): ResponseInterface
    {
        $request = $this->createRequest('GET', $path, $headers);

        return $this->client->sendRequest($request);
    }

    /**
     * Send a POST request to the specified path with a JSON payload.
     *
     * @param string $path Relative path (will be appended to base path)
     * @param array<string, mixed> $payload Request payload to send as JSON
     * @param array<string, string> $headers Optional headers to include
     *
     * @return ResponseInterface The PSR-7 HTTP response
     *
     * @throws JsonException
     */
    protected function post(string $path, array $payload = [], array $headers = []): ResponseInterface
    {
        $request = $this->createRequest('POST', $path, $headers, $payload);

        return $this->client->sendRequest($request);
    }

    /**
     * Send a PUT request to the specified path with a JSON payload.
     *
     * @param string $path Relative path (will be appended to base path)
     * @param array<string, mixed> $payload Request payload to send as JSON
     * @param array<string, string> $headers Optional headers to include
     *
     * @return ResponseInterface The PSR-7 HTTP response
     *
     * @throws JsonException
     */
    protected function put(string $path, array $payload = [], array $headers = []): ResponseInterface
    {
        $request = $this->createRequest('PUT', $path, $headers, $payload);

        return $this->client->sendRequest($request);
    }

    /**
     * Send a PATCH request to the specified path with a JSON payload.
     *
     * @param string $path Relative path (will be appended to base path)
     * @param array<string, mixed> $payload Request payload to send as JSON
     * @param array<string, string> $headers Optional headers to include
     *
     * @return ResponseInterface The PSR-7 HTTP response
     *
     * @throws JsonException
     */
    protected function patch(string $path, array $payload = [], array $headers = []): ResponseInterface
    {
        $request = $this->createRequest('PATCH', $path, $headers, $payload);

        return $this->client->sendRequest($request);
    }

    /**
     * Send a DELETE request to the specified path.
     *
     * @param string $path Relative path (will be appended to base path)
     * @param array<string, string> $headers Optional headers to include
     *
     * @return ResponseInterface The PSR-7 HTTP response
     *
     * @throws JsonException
     */
    protected function delete(string $path, array $headers = []): ResponseInterface
    {
        $request = $this->createRequest('DELETE', $path, $headers);

        return $this->client->sendRequest($request);
    }

    /**
     * Create a PSR-7 request with the full URI and optional JSON payload.
     *
     * Combines base URL + base path + relative path to create the full URI.
     * If a payload is provided, it is JSON-encoded and added to the request
     * body with the appropriate Content-Type header.
     *
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param string $path Relative path (will be appended to base path)
     * @param array<string, string> $headers Optional headers to include
     * @param array<string, mixed> $payload Optional request payload to send as
     *                                      JSON
     *
     * @return RequestInterface The PSR-7 HTTP request
     *
     * @throws JsonException
     */
    private function createRequest(
        string $method,
        string $path,
        array $headers = [],
        array $payload = []
    ): RequestInterface {
        $uri = rtrim($this->client->getBaseUrl(), '/').$this->getBasePath().$path;

        $request = $this->requestFactory->createRequest($method, $uri);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($payload !== []) {
            $jsonBody = json_encode($payload, JSON_THROW_ON_ERROR);
            $stream = $this->streamFactory->createStream($jsonBody);
            $request = $request->withBody($stream);
            $request = $request->withHeader('Content-Type', 'application/json');
        }

        return $request;
    }
}
