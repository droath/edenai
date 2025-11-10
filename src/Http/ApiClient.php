<?php

declare(strict_types=1);

namespace Droath\Edenai\Http;

use Psr\Http\Client\ClientInterface;
use Droath\Edenai\Middleware\Pipeline;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Http\Discovery\Psr18ClientDiscovery;
use Droath\Edenai\Middleware\RetryMiddleware;
use Droath\Edenai\Middleware\MiddlewareInterface;
use Droath\Edenai\Middleware\ErrorHandlingMiddleware;
use Droath\Edenai\Middleware\AuthenticationMiddleware;

/**
 * Main HTTP client for the EdenAI API.
 *
 * The ApiClient coordinates PSR-18 HTTP client operations, middleware pipeline
 * execution, and configuration management. It provides a central point for all
 * HTTP operations within the SDK.
 *
 * Configuration options:
 * - httpClient: PSR-18 HTTP client (auto-discovered if not provided)
 * - baseUrl: API base URL (default: EDENAI_BASE_URL environment variable)
 * - apiKey: API authentication key (default: EDENAI_API_KEY environment variable)
 * - middleware: Custom middleware array prepended before default middleware
 *
 * Middleware ordering:
 * 1. Custom middleware (if provided) executed first
 * 2. AuthenticationMiddleware: Injects Authorization header
 * 3. ErrorHandlingMiddleware: Maps HTTP errors to exceptions
 * 4. RetryMiddleware: Retries transient failures
 * 5. HTTP client: Sends the actual request
 *
 * Usage example:
 * ```php
 * // Basic usage with environment variables
 * $client = new ApiClient();
 *
 * // Explicit configuration
 * $client = new ApiClient(
 *     httpClient: $guzzle,
 *     baseUrl: 'https://api.edenai.run',
 *     apiKey: 'your-api-key',
 * );
 *
 * // With custom middleware
 * $client = new ApiClient(
 *     middleware: [new LoggingMiddleware()],
 * );
 * ```
 */
final readonly class ApiClient
{
    /**
     * The middleware pipeline executor.
     */
    private Pipeline $pipeline;

    /**
     * Create a new API client instance.
     *
     * @param ClientInterface|null $httpClient PSR-18 HTTP client (auto-discovered if null)
     * @param string|null $baseUrl API base URL (from EDENAI_BASE_URL env if null)
     * @param string|null $apiKey API authentication key (from EDENAI_API_KEY env if null)
     * @param array<MiddlewareInterface> $middleware Custom middleware to prepend before default middleware
     */
    public function __construct(
        private ?ClientInterface $httpClient = null,
        private ?string $baseUrl = null,
        private ?string $apiKey = null,
        array $middleware = [],
    ) {
        $defaultMiddleware = [
            new AuthenticationMiddleware($this->getApiKey()),
            new ErrorHandlingMiddleware(),
            new RetryMiddleware(),
        ];

        $this->pipeline = new Pipeline(
            [...$middleware, ...$defaultMiddleware]
        );
    }

    /**
     * Send an HTTP request through the middleware pipeline.
     *
     * The request is processed through all middleware in order:
     * 1. Custom middleware (if any)
     * 2. Authentication middleware
     * 3. Error handling middleware
     * 4. Retry middleware
     * 5. HTTP client
     *
     * @param RequestInterface $request The PSR-7 HTTP request to send
     *
     * @return ResponseInterface The PSR-7 HTTP response
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $httpClient = $this->httpClient ?? Psr18ClientDiscovery::find();

        return $this->pipeline->process(
            $request,
            fn (RequestInterface $req): ResponseInterface => $httpClient->sendRequest($req)
        );
    }

    /**
     * Get the configured base URL.
     *
     * @return string The API base URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl ?? $this->getEnvironmentVariable('EDENAI_BASE_URL') ?? '';
    }

    /**
     * Get the configured API key.
     *
     * @return string|null The API authentication key
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey ?? $this->getEnvironmentVariable('EDENAI_API_KEY');
    }

    /**
     * Get an environment variable value.
     *
     * @param string $name The environment variable name
     *
     * @return string|null The environment variable value or null if not set
     */
    private function getEnvironmentVariable(string $name): ?string
    {
        $value = getenv($name);

        return $value !== false ? $value : null;
    }
}
