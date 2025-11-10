<?php

declare(strict_types=1);

namespace Droath\Edenai\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when file upload validation or processing fails.
 *
 * This exception is used for client-side file errors that occur before
 * making an API request, such as file not found, unreadable files, or
 * size limit violations. Since it represents client-side validation,
 * it extends Exception directly rather than ApiException.
 *
 * @package Droath\Edenai\Exceptions
 */
final class FileUploadException extends Exception
{
    /**
     * Create a new file upload exception.
     *
     * @param string $message Error message describing the upload failure
     * @param int $code Error code (default 0)
     * @param Throwable|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
