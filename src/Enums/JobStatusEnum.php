<?php

declare(strict_types=1);

namespace Droath\Edenai\Enums;

/**
 * Enumeration of job statuses for asynchronous operations.
 *
 * This enum provides type-safe status handling for async job tracking.
 * Each case maps to the Eden AI job status string used in API responses.
 *
 * @package Droath\Edenai\Enums
 */
enum JobStatusEnum: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case FINISHED = 'finished';
    case FAILED = 'failed';
}
