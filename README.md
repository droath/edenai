# EdenAI PHP SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/droath/edenai.svg?style=flat-square)](https://packagist.org/packages/droath/edenai)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/droath/edenai/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/droath/edenai/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/droath/edenai/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/droath/edenai/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/droath/edenai.svg?style=flat-square)](https://packagist.org/packages/droath/edenai)

A modern, type-safe PHP SDK for the EdenAI API. Built with strict type safety, immutability, and PSR standards compliance, this SDK provides a robust foundation for integrating AI capabilities into your PHP applications.

**Features:**
- Fully typed with PHP 8.3+ features (readonly properties, enums, strict types)
- PSR-compliant (PSR-7, PSR-17, PSR-18)
- Extensible middleware pipeline architecture
- Automatic retry logic with exponential backoff
- Comprehensive exception handling
- Immutable DTOs for type-safe request/response handling

## Requirements

- PHP 8.3 or higher
- A PSR-18 HTTP client (e.g., Guzzle)
- PSR-17 HTTP factories

## Installation

Install the package via Composer:

```bash
composer require droath/edenai
```

You'll also need a PSR-18 HTTP client. We recommend Guzzle:

```bash
composer require guzzlehttp/guzzle
```

## Configuration

Set your EdenAI API credentials as environment variables:

```bash
# .env file
EDENAI_BASE_URL=https://api.edenai.run
EDENAI_API_KEY=your-api-key-here
```

Or configure them directly when instantiating the client (see Basic Usage below).

## Basic Usage

### Quick Start

The simplest way to get started is to use environment variables:

```php
<?php

declare(strict_types=1);

use Droath\Edenai\Http\ApiClient;
use Droath\Edenai\Resources\AudioResource;

// Client auto-discovers HTTP client and loads config from environment
$client = new ApiClient();

// Use a resource to make API calls
$audioResource = new AudioResource($client);
```

### Explicit Configuration

You can also configure the client explicitly:

```php
<?php

declare(strict_types=1);

use GuzzleHttp\Client as GuzzleClient;
use Droath\Edenai\Http\ApiClient;

$httpClient = new GuzzleClient();

$client = new ApiClient(
    httpClient: $httpClient,
    baseUrl: 'https://api.edenai.run',
    apiKey: 'your-api-key-here',
);
```

## Audio Resources

The SDK provides comprehensive support for Eden AI's audio processing endpoints:

### Speech-to-Text (Async)

Upload audio files for asynchronous transcription:

```php
<?php

declare(strict_types=1);

use Droath\Edenai\Http\ApiClient;
use Droath\Edenai\Resources\AudioResource;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncRequest;
use Droath\Edenai\Enums\ServiceProviderEnum;

$client = new ApiClient();
$audioResource = new AudioResource($client);

// Create request with file upload
$request = new SpeechToTextAsyncRequest(
    file: '/path/to/audio.mp3',
    providers: [ServiceProviderEnum::GOOGLE, ServiceProviderEnum::DEEPGRAM],
    language: 'en',
);

// Upload file and get job ID for tracking
$response = $audioResource->speechToTextAsync($request);

echo "Job ID: {$response->jobId}";
echo "Providers: " . implode(', ', $response->providers);
echo "Created: {$response->timestamp->format('Y-m-d H:i:s')}";
```

**Supported Audio Formats:** mp3, wav, flac, ogg

### Text-to-Speech (Sync)

Generate audio from text synchronously:

```php
<?php

declare(strict_types=1);

use Droath\Edenai\Http\ApiClient;
use Droath\Edenai\Resources\AudioResource;
use Droath\Edenai\DTOs\Audio\TextToSpeechRequest;
use Droath\Edenai\Enums\ServiceProviderEnum;

$client = new ApiClient();
$audioResource = new AudioResource($client);

// Create request with all optional parameters
$request = new TextToSpeechRequest(
    text: 'Hello world',
    providers: [ServiceProviderEnum::AMAZON],
    language: 'en',
    option: 'FEMALE',
    audioFormat: 'mp3',
    rate: 1.0,
    pitch: 0.0,
    volume: 1.0,
    voiceModel: 'neural',
);

// Get generated audio immediately
$response = $audioResource->textToSpeech($request);

// Save audio to file (already decoded from Base64)
file_put_contents('output.mp3', $response->audioData);

echo "Audio type: {$response->contentType}";
echo "Duration: {$response->duration} seconds";
```

### Text-to-Speech (Async)

Generate audio from text asynchronously:

```php
<?php

declare(strict_types=1);

use Droath\Edenai\Http\ApiClient;
use Droath\Edenai\Resources\AudioResource;
use Droath\Edenai\DTOs\Audio\TextToSpeechAsyncRequest;
use Droath\Edenai\Enums\ServiceProviderEnum;

$client = new ApiClient();
$audioResource = new AudioResource($client);

// Create async request
$request = new TextToSpeechAsyncRequest(
    text: 'This is a longer text that will be processed asynchronously',
    providers: [ServiceProviderEnum::MICROSOFT, ServiceProviderEnum::AZURE],
    language: 'en-US',
    option: 'MALE',
    audioFormat: 'wav',
);

// Get job ID for polling
$response = $audioResource->textToSpeechAsync($request);

echo "Job ID: {$response->jobId}";
echo "Check status later to retrieve generated audio";
```

### AI Service Providers

The `ServiceProviderEnum` provides type-safe provider selection:

```php
<?php

declare(strict_types=1);

use Droath\Edenai\Enums\ServiceProviderEnum;

// Available providers:
ServiceProviderEnum::GOOGLE      // Google Cloud
ServiceProviderEnum::AMAZON      // Amazon AWS
ServiceProviderEnum::MICROSOFT   // Microsoft Azure
ServiceProviderEnum::OPENAI      // OpenAI
ServiceProviderEnum::DEEPGRAM    // Deepgram
ServiceProviderEnum::ASSEMBLY_AI // AssemblyAI
ServiceProviderEnum::REV_AI      // Rev.ai
ServiceProviderEnum::SPEECHMATICS // Speechmatics
ServiceProviderEnum::IBMWATSON   // IBM Watson
ServiceProviderEnum::AZURE       // Azure Cognitive Services
```

### Error Handling for Audio Endpoints

Audio operations include specific exceptions:

```php
<?php

declare(strict_types=1);

use Droath\Edenai\Http\ApiClient;
use Droath\Edenai\Resources\AudioResource;
use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncRequest;
use Droath\Edenai\Enums\ServiceProviderEnum;
use Droath\Edenai\Exceptions\FileUploadException;
use Droath\Edenai\Exceptions\ValidationException;
use InvalidArgumentException;

$client = new ApiClient();
$audioResource = new AudioResource($client);

try {
    $request = new SpeechToTextAsyncRequest(
        file: '/path/to/audio.mp3',
        providers: [ServiceProviderEnum::GOOGLE],
        language: 'en',
    );
    $response = $audioResource->speechToTextAsync($request);
} catch (InvalidArgumentException $e) {
    // Request DTO validation failed (e.g., empty text)
    echo 'Invalid request: ' . $e->getMessage();
} catch (FileUploadException $e) {
    // File not found or not readable
    echo 'File upload error: ' . $e->getMessage();
} catch (ValidationException $e) {
    // Unsupported audio format or API validation error
    echo 'Validation error: ' . $e->getMessage();
    print_r($e->getErrors());
}
```

## Advanced Usage

### Custom Middleware

Extend the middleware pipeline with custom middleware:

```php
<?php

declare(strict_types=1);

use Droath\Edenai\Http\ApiClient;
use Droath\Edenai\Middleware\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class LoggingMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        // Log before request
        error_log('Request: ' . $request->getMethod() . ' ' . $request->getUri());

        // Execute next middleware
        $response = $next($request);

        // Log after response
        error_log('Response: ' . $response->getStatusCode());

        return $response;
    }
}

// Custom middleware is prepended before default middleware
$client = new ApiClient(
    middleware: [new LoggingMiddleware()],
);
```

The middleware pipeline executes in this order:
1. Custom middleware (if provided)
2. AuthenticationMiddleware (injects API key)
3. ErrorHandlingMiddleware (maps HTTP errors to exceptions)
4. RetryMiddleware (retries transient failures)
5. HTTP client (sends the request)

### Exception Handling

The SDK provides typed exceptions for different error scenarios:

```php
<?php

declare(strict_types=1);

use Droath\Edenai\Http\ApiClient;
use Droath\Edenai\Resources\AudioResource;
use Droath\Edenai\DTOs\Audio\TextToSpeechRequest;
use Droath\Edenai\Enums\ServiceProviderEnum;
use Droath\Edenai\Exceptions\AuthenticationException;
use Droath\Edenai\Exceptions\ValidationException;
use Droath\Edenai\Exceptions\RateLimitException;
use Droath\Edenai\Exceptions\ServerException;
use Droath\Edenai\Exceptions\NetworkException;

$client = new ApiClient();
$audioResource = new AudioResource($client);

try {
    $request = new TextToSpeechRequest(
        text: 'Hello world',
        providers: [ServiceProviderEnum::GOOGLE],
    );
    $response = $audioResource->textToSpeech($request);
} catch (AuthenticationException $e) {
    // 401 - Invalid or missing API credentials
    echo 'Authentication failed: ' . $e->getMessage();
    echo 'Status: ' . $e->getStatusCode();
} catch (ValidationException $e) {
    // 422 - Request validation failed
    echo 'Validation errors: ';
    print_r($e->getErrors());
} catch (RateLimitException $e) {
    // 429 - Rate limit exceeded
    echo 'Rate limited. Retry after: ' . $e->getRetryAfter();
} catch (ServerException $e) {
    // 500+ - Server error
    echo 'Server error: ' . $e->getMessage();
} catch (NetworkException $e) {
    // Network/connection error
    echo 'Network error: ' . $e->getMessage();
}
```

### Request DTOs

Request DTOs provide validation and type safety for API requests. All audio request DTOs validate their inputs at construction time:

```php
<?php

declare(strict_types=1);

use Droath\Edenai\DTOs\Audio\TextToSpeechRequest;
use Droath\Edenai\Enums\ServiceProviderEnum;
use InvalidArgumentException;

// Valid request
$request = new TextToSpeechRequest(
    text: 'Hello world',
    providers: [ServiceProviderEnum::GOOGLE, ServiceProviderEnum::AMAZON],
    language: 'en',
);

// Serialize to array for HTTP request
$payload = $request->toArray();

// Invalid requests throw exceptions at construction time
try {
    $invalid = new TextToSpeechRequest(
        text: '', // Empty text
        providers: [ServiceProviderEnum::GOOGLE],
    );
} catch (InvalidArgumentException $e) {
    echo 'Validation error: ' . $e->getMessage();
    // Output: "Text cannot be empty"
}
```

### Response DTOs

Response DTOs parse API responses with type transformations:

```php
<?php

declare(strict_types=1);

use Droath\Edenai\DTOs\Audio\SpeechToTextAsyncResponse;

// Parse API response
$data = [
    'job_id' => 'job-abc123',
    'providers' => ['google', 'deepgram'],
    'submitted_at' => '2024-11-09 15:30:00',
    'unknown_field' => 'ignored', // Unknown fields are ignored
];

$response = SpeechToTextAsyncResponse::fromResponse($data);

// Access typed properties
echo $response->jobId; // string: "job-abc123"
print_r($response->providers); // array: ['google', 'deepgram']
echo $response->submittedAt->format('Y-m-d'); // DateTimeImmutable: "2024-11-09"

// Properties are readonly and immutable
$response->jobId = 'xyz'; // Error: Cannot modify readonly property
```

### Response Metadata

Access HTTP metadata separately from business data:

```php
<?php

declare(strict_types=1);

use Droath\Edenai\DTOs\ResponseMetadata;
use Psr\Http\Message\ResponseInterface;

/** @var ResponseInterface $psrResponse */
$metadata = ResponseMetadata::fromResponse($psrResponse);

// Access rate limit information
echo 'Requests remaining: ' . $metadata->getRateLimitRemaining();
echo 'Rate limit resets at: ' . date('Y-m-d H:i:s', $metadata->getRateLimitReset());

// Access request ID for debugging
echo 'Request ID: ' . $metadata->getRequestId();

// Access all headers
$headers = $metadata->getHeaders();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Travis Tomka](https://github.com/droath)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
