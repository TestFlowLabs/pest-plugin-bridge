<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

use InvalidArgumentException;

/**
 * Configuration manager for external frontend URL.
 */
final class Configuration
{
    private static ?string $externalUrl = null;

    /**
     * Set the external frontend base URL.
     *
     * @throws InvalidArgumentException If the URL is invalid.
     */
    public static function setExternalUrl(string $url): void
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid URL provided: {$url}");
        }

        self::$externalUrl = $url;
    }

    /**
     * Get the external frontend base URL.
     * Falls back to PEST_BRIDGE_EXTERNAL_URL environment variable.
     *
     * @throws InvalidArgumentException If no URL is configured.
     */
    public static function getExternalUrl(): string
    {
        if (self::$externalUrl !== null) {
            return self::$externalUrl;
        }

        $envUrl = self::getEnvironmentVariable('PEST_BRIDGE_EXTERNAL_URL');

        if ($envUrl !== null) {
            return $envUrl;
        }

        throw new InvalidArgumentException(
            'External URL not configured. Set PEST_BRIDGE_EXTERNAL_URL environment variable '.
            'or call Configuration::setExternalUrl() in your Pest.php'
        );
    }

    /**
     * Check if an external URL is configured.
     */
    public static function hasExternalUrl(): bool
    {
        if (self::$externalUrl !== null) {
            return true;
        }

        return self::getEnvironmentVariable('PEST_BRIDGE_EXTERNAL_URL') !== null;
    }

    /**
     * Build a full URL from a path.
     */
    public static function buildUrl(string $path = '/'): string
    {
        $baseUrl = self::getExternalUrl();

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    /**
     * Reset the configuration (useful for testing).
     */
    public static function reset(): void
    {
        self::$externalUrl = null;
    }

    /**
     * Get an environment variable value.
     */
    private static function getEnvironmentVariable(string $name): ?string
    {
        $value = $_ENV[$name] ?? getenv($name);

        if ($value === false || $value === '') {
            return null;
        }

        return $value;
    }
}
