<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

use InvalidArgumentException;

/**
 * Configuration registry for bridged frontend URLs.
 *
 * Supports a default frontend and multiple named "bridged frontends" for testing
 * applications with multiple frontend services.
 */
final class Bridge
{
    /**
     * File path for HTTP fake configuration.
     * Used for cross-process communication between test and server.
     */
    private const string FAKE_CONFIG_PATH = 'bridge_http_fakes.json';

    private static ?string $defaultUrl = null;

    /** @var array<string, string> */
    private static array $frontends = [];

    /**
     * Add a bridged frontend.
     *
     * When called without a name, sets the default frontend.
     * When called with a name, adds a named bridged frontend.
     *
     * @param  string  $url  The frontend URL
     * @param  string|null  $name  Optional name for the bridged frontend
     *
     * @throws InvalidArgumentException If the URL is invalid
     */
    public static function add(string $url, ?string $name = null): FrontendDefinition
    {
        self::validateUrl($url);

        if ($name === '') {
            throw new InvalidArgumentException('Frontend name cannot be empty');
        }

        if ($name === null) {
            self::$defaultUrl = $url;
            $definition       = new FrontendDefinition($url);
        } else {
            self::$frontends[$name] = $url;
            $definition             = new FrontendDefinition($url, $name);
        }

        FrontendManager::instance()->register($definition);

        return $definition;
    }

    /**
     * Register a child frontend (called internally by FrontendDefinition::child()).
     *
     * @internal
     */
    public static function registerChild(string $parentUrl, string $path, string $name): FrontendDefinition
    {
        $childUrl = rtrim($parentUrl, '/').'/'.ltrim($path, '/');

        self::$frontends[$name] = $childUrl;

        // Child definitions don't get registered with FrontendManager
        // They share the parent's server process
        return new FrontendDefinition($childUrl, $name);
    }

    /**
     * Get the URL for a frontend.
     *
     * @param  string|null  $name  Frontend name, or null for default
     *
     * @throws InvalidArgumentException If the frontend is not configured
     */
    public static function url(?string $name = null): string
    {
        if ($name === null) {
            if (self::$defaultUrl === null) {
                throw new InvalidArgumentException(
                    'Default frontend not configured. Call Bridge::add() in tests/Pest.php'
                );
            }

            return self::$defaultUrl;
        }

        if (!isset(self::$frontends[$name])) {
            throw new InvalidArgumentException(
                "Frontend '{$name}' not configured. Call Bridge::add(\$url, '{$name}') in tests/Pest.php"
            );
        }

        return self::$frontends[$name];
    }

    /**
     * Check if a frontend is configured.
     *
     * @param  string|null  $name  Frontend name, or null for default
     */
    public static function has(?string $name = null): bool
    {
        if ($name === null) {
            return self::$defaultUrl !== null;
        }

        return isset(self::$frontends[$name]);
    }

    /**
     * Build a full URL from a path.
     *
     * @param  string  $path  The path to append
     * @param  string|null  $frontend  Frontend name, or null for default
     *
     * @throws InvalidArgumentException If the frontend is not configured
     */
    public static function buildUrl(string $path = '/', ?string $frontend = null): string
    {
        $baseUrl = self::url($frontend);

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    /**
     * Reset all configuration.
     *
     * Clears frontend URLs, stops all servers, and removes any HTTP fakes.
     */
    public static function reset(): void
    {
        self::$defaultUrl = null;
        self::$frontends  = [];
        FrontendManager::reset();
        self::clearFakes();
    }

    /**
     * Register fake HTTP responses for external API calls.
     *
     * This enables faking external HTTP calls (like Stripe, SendGrid) in browser tests.
     * Works across process boundaries by writing config to a file that the Laravel
     * middleware reads.
     *
     * Usage:
     * ```php
     * Bridge::fake([
     *     'https://api.stripe.com/*' => [
     *         'status' => 200,
     *         'body' => ['id' => 'ch_123', 'status' => 'succeeded'],
     *     ],
     *     'https://api.sendgrid.com/*' => [
     *         'status' => 202,
     *         'body' => ['message' => 'queued'],
     *     ],
     * ]);
     * ```
     *
     * Note: Requires BridgeHttpFakeMiddleware to be registered in your Laravel app.
     *
     * @param  array<string, array{status?: int, body?: array<mixed>, headers?: array<string, string>}>  $fakes
     */
    public static function fake(array $fakes): void
    {
        $path = self::getFakeConfigPath();
        file_put_contents($path, json_encode($fakes, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }

    /**
     * Clear all registered HTTP fakes.
     *
     * Called automatically after each test via shutdown handler.
     */
    public static function clearFakes(): void
    {
        $path = self::getFakeConfigPath();

        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Check if any HTTP fakes are registered.
     */
    public static function hasFakes(): bool
    {
        return file_exists(self::getFakeConfigPath());
    }

    /**
     * Get the current fake configuration.
     *
     * @return array<string, array{status?: int, body?: array<mixed>, headers?: array<string, string>}>
     */
    public static function getFakes(): array
    {
        $path = self::getFakeConfigPath();

        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return [];
        }

        /** @var array<string, array{status?: int, body?: array<mixed>, headers?: array<string, string>}> $decoded */
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /**
     * Get the path to the fake configuration file.
     */
    public static function getFakeConfigPath(): string
    {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR.self::FAKE_CONFIG_PATH;
    }

    /**
     * Validate a URL.
     *
     * @throws InvalidArgumentException If the URL is invalid
     */
    private static function validateUrl(string $url): void
    {
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid URL: {$url}");
        }
    }
}
