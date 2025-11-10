<?php

declare(strict_types=1);

namespace Droath\Edenai\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Defines the middleware contract for processing HTTP requests and responses.
 *
 * Middleware implements the Chain of Responsibility pattern, where each middleware
 * can perform operations before and after passing the request to the next handler
 * in the chain. This enables cross-cutting concerns like authentication, error handling,
 * and retry logic to be implemented as composable, reusable components.
 *
 * Example usage:
 * ```php
 * class LoggingMiddleware implements MiddlewareInterface
 * {
 *     public function handle(RequestInterface $request, callable $next): ResponseInterface
 *     {
 *         // Before: log the request
 *         error_log('Request: ' . $request->getMethod() . ' ' . $request->getUri());
 *
 *         // Pass to next middleware
 *         $response = $next($request);
 *
 *         // After: log the response
 *         error_log('Response: ' . $response->getStatusCode());
 *
 *         return $response;
 *     }
 * }
 * ```
 */
interface MiddlewareInterface
{
    /**
     * Process an HTTP request through the middleware chain.
     *
     * This method receives a PSR-7 request and a callable representing the next
     * middleware in the chain. The middleware can:
     * - Modify the request before passing it forward
     * - Execute logic before calling the next handler
     * - Call the next handler and receive its response
     * - Modify the response before returning it
     * - Short-circuit the chain by returning a response without calling next
     * - Throw exceptions to halt processing
     *
     * @param RequestInterface $request The PSR-7 HTTP request to process
     * @param callable $next The next handler in the middleware chain
     *                       Signature: fn(RequestInterface): ResponseInterface
     *
     * @return ResponseInterface The PSR-7 HTTP response
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface;
}
