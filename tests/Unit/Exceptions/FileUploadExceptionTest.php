<?php

declare(strict_types=1);

use Droath\Edenai\Exceptions\FileUploadException;

describe('FileUploadException', function (): void {
    test('exception message is accessible', function (): void {
        $message = 'File not found: /path/to/audio.mp3';
        $exception = new FileUploadException($message);

        expect($exception->getMessage())->toBe($message);
    });

    test('exception can be thrown and caught', function (): void {
        expect(function (): void {
            throw new FileUploadException('Upload failed');
        })->toThrow(FileUploadException::class, 'Upload failed');
    });

    test('exception extends base Exception not ApiException', function (): void {
        $exception = new FileUploadException('File error');

        expect($exception)->toBeInstanceOf(Exception::class)
            ->and($exception)->not->toBeInstanceOf(Droath\Edenai\Exceptions\ApiException::class);
    });

    test('exception does not include HTTP status code', function (): void {
        $exception = new FileUploadException('File is unreadable');

        // FileUploadException should not have getStatusCode() method since it's client-side
        expect(method_exists($exception, 'getStatusCode'))->toBeFalse();
    });

    test('preserves previous exception in chain', function (): void {
        $previous = new RuntimeException('Original error');
        $exception = new FileUploadException('Wrapped error', 0, $previous);

        expect($exception->getPrevious())->toBe($previous);
    });
});
