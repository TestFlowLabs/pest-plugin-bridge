<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge\Http;

/**
 * Fake HTTP client for testing.
 *
 * Allows configuring responses and tracking requests.
 */
final class FakeHttpClient implements HttpClientInterface
{
    /** @var array<string, int> URL -> HTTP status code mapping */
    private array $checkResponses = [];

    /** @var array<string, string|false> URL -> response body mapping */
    private array $getResponses = [];

    /** @var array<array{method: string, url: string, timeout: int}> */
    private array $requests = [];

    /** @var int Default check response when URL not configured */
    private int $defaultCheckResponse = 200;

    /** @var string|false Default get response when URL not configured */
    private string|false $defaultGetResponse = '';

    /**
     * Configure response for check() calls.
     */
    public function fakeCheck(string $url, int $httpCode): self
    {
        $this->checkResponses[$url] = $httpCode;

        return $this;
    }

    /**
     * Configure response for get() calls.
     */
    public function fakeGet(string $url, string|false $body): self
    {
        $this->getResponses[$url] = $body;

        return $this;
    }

    /**
     * Set default response for check() when URL is not configured.
     */
    public function setDefaultCheckResponse(int $httpCode): self
    {
        $this->defaultCheckResponse = $httpCode;

        return $this;
    }

    /**
     * Set default response for get() when URL is not configured.
     */
    public function setDefaultGetResponse(string|false $body): self
    {
        $this->defaultGetResponse = $body;

        return $this;
    }

    /**
     * Get all recorded requests.
     *
     * @return array<array{method: string, url: string, timeout: int}>
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * Check if a specific URL was requested.
     */
    public function wasRequested(string $url, ?string $method = null): bool
    {
        foreach ($this->requests as $request) {
            if ($request['url'] === $url && ($method === null || $request['method'] === $method)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Count how many times a URL was requested.
     */
    public function requestCount(string $url, ?string $method = null): int
    {
        $count = 0;

        foreach ($this->requests as $request) {
            if ($request['url'] !== $url) {
                continue;
            }
            if ($method !== null && $request['method'] !== $method) {
                continue;
            }
            $count++;
        }

        return $count;
    }

    /**
     * Clear all recorded requests.
     */
    public function clearRequests(): self
    {
        $this->requests = [];

        return $this;
    }

    /**
     * Reset all configured responses and requests.
     */
    public function reset(): self
    {
        $this->checkResponses       = [];
        $this->getResponses         = [];
        $this->requests             = [];
        $this->defaultCheckResponse = 200;
        $this->defaultGetResponse   = '';

        return $this;
    }

    public function check(string $url, int $timeout = 1): int
    {
        $this->requests[] = [
            'method'  => 'HEAD',
            'url'     => $url,
            'timeout' => $timeout,
        ];

        return $this->checkResponses[$url] ?? $this->defaultCheckResponse;
    }

    public function get(string $url, int $timeout = 10): string|false
    {
        $this->requests[] = [
            'method'  => 'GET',
            'url'     => $url,
            'timeout' => $timeout,
        ];

        return $this->getResponses[$url] ?? $this->defaultGetResponse;
    }
}
