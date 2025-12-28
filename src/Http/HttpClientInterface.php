<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge\Http;

/**
 * Interface for HTTP client operations.
 *
 * Abstracts HTTP calls to allow mocking in tests.
 */
interface HttpClientInterface
{
    /**
     * Check if a URL is responding (HEAD request).
     *
     * @return int HTTP status code (0 if connection failed)
     */
    public function check(string $url, int $timeout = 1): int;

    /**
     * Fetch content from a URL (GET request).
     *
     * @return string|false Response body or false on failure
     */
    public function get(string $url, int $timeout = 10): string|false;
}
