<?php

declare(strict_types=1);

// Load Composer autoloader
require_once __DIR__.'/../vendor/autoload.php';

// Load environment variables from .env.testing if it exists
// This allows sandbox tests to access EDENAI_API_KEY for real API testing
$envTestingPath = __DIR__.'/../.env.testing';
if (file_exists($envTestingPath)) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..', '.env.testing');
    $dotenv->safeLoad();

    // Dotenv loads into $_ENV but not into getenv() by default
    // Manually set it in the process environment for getenv() compatibility
    if (isset($_ENV['EDENAI_API_KEY'])) {
        putenv('EDENAI_API_KEY=' . $_ENV['EDENAI_API_KEY']);
    }
}
