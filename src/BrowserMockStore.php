<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

/**
 * Singleton store for browser mocks.
 *
 * Uses a class-based singleton pattern to avoid PHP trait static
 * property scope issues when calling trait methods directly.
 *
 * @internal
 */
final class BrowserMockStore
{
    /**
     * Stored browser mocks for the current test.
     *
     * @var array<string, array{status?: int, body?: mixed, headers?: array<string, string>}>
     */
    private static array $mocks = [];

    /**
     * Register browser-level HTTP mocks.
     *
     * @param  array<string, array{status?: int, body?: mixed, headers?: array<string, string>}>  $mocks
     */
    public static function set(array $mocks): void
    {
        self::$mocks = $mocks;
    }

    /**
     * Get all registered mocks.
     *
     * @return array<string, array{status?: int, body?: mixed, headers?: array<string, string>}>
     */
    public static function get(): array
    {
        return self::$mocks;
    }

    /**
     * Clear all browser mocks.
     */
    public static function clear(): void
    {
        self::$mocks = [];
    }

    /**
     * Check if browser mocks are registered.
     */
    public static function hasMocks(): bool
    {
        return self::$mocks !== [];
    }

    /**
     * Get the count of registered mocks.
     */
    public static function count(): int
    {
        return count(self::$mocks);
    }
}
