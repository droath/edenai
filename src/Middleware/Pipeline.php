<?php

declare(strict_types=1);

namespace Droath\Edenai\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Executes a chain of middleware in sequence using the Chain of Responsibility pattern.
 *
 * The pipeline takes an array of middleware instances and executes them in order,
 * with each middleware receiving the next middleware as a callable. This creates
 * a nested chain where each middleware can perform operations before and after
 * the next middleware in the sequence.
 *
 * Execution flow:
 * 1. First middleware receives request and callable to second middleware
 * 2. Second middleware receives request and callable to third middleware
 * 3. ... continues until last middleware
 * 4. Last middleware receives request and callable to final handler (HTTP client)
 * 5. Response bubbles back up through the chain
 *
 * Example usage:
 * ```php
 * $pipeline = new Pipeline([
 *     new AuthenticationMiddleware($apiKey),
 *     new ErrorHandlingMiddleware(),
 *     new RetryMiddleware(['max_attempts' => 3]),
 * ]);
 *
 * $response = $pipeline->process($request, function ($req) use ($httpClient) {
 *     return $httpClient->sendRequest($req);
 * });
 * ```
 */
final class Pipeline
{
    /**
     * Create a new middleware pipeline.
     *
     * @param array<MiddlewareInterface> $middleware Array of middleware instances
     *                                               to execute in order
     */
    public function __construct(
        private readonly array $middleware,
    ) {}

    /**
     * Process a request through the middleware pipeline.
     *
     * Builds a nested chain of callables from the middleware array and executes
     * them in sequence. Each middleware receives the request and a callable to
     * the next middleware. The final callable in the chain is the provided
     * $final handler, typically the HTTP client's sendRequest method.
     *
     * If the middleware array is empty, the request is passed directly to the
     * final handler without any processing.
     *
     * @param RequestInterface $request The PSR-7 HTTP request to process
     * @param callable $final The final handler to invoke after all middleware
     *                        Signature: fn(RequestInterface): ResponseInterface
     *
     * @return ResponseInterface The PSR-7 HTTP response
     */
    public function process(RequestInterface $request, callable $final): ResponseInterface
    {
        // Build the middleware chain from the end backwards
        $next = $final;

        // Iterate middleware in reverse to build nested chain
        foreach (array_reverse($this->middleware) as $middleware) {
            $next = function (RequestInterface $req) use ($middleware, $next): ResponseInterface {
                return $middleware->handle($req, $next);
            };
        }

        // Execute the chain
        return $next($request);
    }
}
