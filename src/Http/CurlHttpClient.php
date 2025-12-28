<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge\Http;

/**
 * cURL-based HTTP client implementation.
 */
final class CurlHttpClient implements HttpClientInterface
{
    public function check(string $url, int $timeout = 1): int
    {
        $ch = curl_init($url);

        if ($ch === false) {
            return 0;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_NOBODY         => true,
        ]);

        curl_exec($ch);

        return curl_getinfo($ch, CURLINFO_HTTP_CODE);
    }

    public function get(string $url, int $timeout = 10): string|false
    {
        $ch = curl_init($url);

        if ($ch === false) {
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => (int) ceil($timeout / 2),
        ]);

        // With CURLOPT_RETURNTRANSFER, curl_exec returns string on success, false on failure
        // @phpstan-ignore return.type
        return curl_exec($ch);
    }
}
