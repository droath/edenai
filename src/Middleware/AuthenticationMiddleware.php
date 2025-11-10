<?php

declare(strict_types=1);

namespace Droath\Edenai\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Injects API authentication credentials into HTTP requests.
 *
 * This middleware implements Bearer token authentication by adding an
 * Authorization header with the API key to all outbound requests. If no
 * API key is provided (null), the middleware passes the request through
 * unchanged, allowing for unauthenticated requests or alternative
 * authentication strategies.
 *
 * Position: This middleware should be first in the pipeline to ensure
 * all subsequent middleware and the final HTTP client receive authenticated
 * requests.
 *
 * Example usage:
 * ```php
 * $middleware = new AuthenticationMiddleware('your-api-key-here');
 * $response = $middleware->handle($request, $next);
 * // Request now includes: Authorization: Bearer your-api-key-here
 * ```
 */
final class AuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * Create a new authentication middleware instance.
     *
     * @param string|null $apiKey The API key for Bearer token authentication,
     *                            or null to skip authentication
     */
    public function __construct(
        private readonly ?string $apiKey,
    ) {
    }

    /**
     * Inject the Authorization header with Bearer token into the request.
     *
     * If an API key is configured, adds the "Authorization: Bearer {key}" header
     * to the request. If the API key is null, passes the request through unchanged.
     *
     * @param RequestInterface $request The PSR-7 HTTP request
     * @param callable $next The next middleware in the chain
     *
     * @return ResponseInterface The PSR-7 HTTP response
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        if ($this->apiKey !== null) {
            $request = $request->withHeader('Authorization', "Bearer {$this->apiKey}");
        }

        return $next($request);
    }
}
