<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

use InvalidArgumentException;

/**
 * Configuration registry for external frontend URLs.
 *
 * Supports a default frontend and multiple named frontends for testing
 * applications with multiple frontend services.
 */
final class Bridge
{
    private static ?string $defaultUrl = null;

    /** @var array<string, string> */
    private static array $frontends = [];

    /**
     * Set the default frontend URL.
     *
     * @throws InvalidArgumentException If the URL is invalid
     */
    public static function setDefault(string $url): FrontendDefinition
    {
        self::validateUrl($url);
        self::$defaultUrl = $url;

        $definition = new FrontendDefinition($url);
        FrontendManager::instance()->register($definition);

        return $definition;
    }

    /**
     * Add a named frontend.
     *
     * @throws InvalidArgumentException If the name is empty or URL is invalid
     */
    public static function frontend(string $name, string $url): FrontendDefinition
    {
        if ($name === '') {
            throw new InvalidArgumentException('Frontend name cannot be empty');
        }

        self::validateUrl($url);
        self::$frontends[$name] = $url;

        $definition = new FrontendDefinition($url, $name);
        FrontendManager::instance()->register($definition);

        return $definition;
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
                    'Default frontend not configured. Call Bridge::setDefault() in tests/Pest.php'
                );
            }

            return self::$defaultUrl;
        }

        if (! isset(self::$frontends[$name])) {
            throw new InvalidArgumentException(
                "Frontend '{$name}' not configured. Call Bridge::frontend('{$name}', \$url) in tests/Pest.php"
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
     */
    public static function reset(): void
    {
        self::$defaultUrl = null;
        self::$frontends = [];
        FrontendManager::reset();
    }

    /**
     * Validate a URL.
     *
     * @throws InvalidArgumentException If the URL is invalid
     */
    private static function validateUrl(string $url): void
    {
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid URL: {$url}");
        }
    }
}
