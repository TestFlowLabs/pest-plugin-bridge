<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

/**
 * Manages the lifecycle of all frontend servers.
 *
 * This static class coordinates starting and stopping
 * all registered frontend servers during test execution.
 */
final class FrontendManager
{
    /** @var array<string, FrontendDefinition> */
    private static array $definitions = [];

    /** @var array<string, FrontendServer> */
    private static array $servers = [];

    private static bool $started = false;

    /**
     * Register a frontend definition.
     *
     * The definition is stored and servers are created lazily
     * when startAll() is called, allowing fluent configuration.
     */
    public static function register(FrontendDefinition $definition): void
    {
        $key                     = $definition->name ?? 'default';
        self::$definitions[$key] = $definition;
    }

    /**
     * Start all registered frontend servers.
     *
     * Creates FrontendServer instances for definitions with serve commands
     * and starts them. This is called lazily on first bridge() call.
     */
    public static function startAll(): void
    {
        if (self::$started) {
            return;
        }

        // Create servers from definitions that have serve commands
        foreach (self::$definitions as $key => $definition) {
            if ($definition->hasServeCommand()) {
                self::$servers[$key] = new FrontendServer($definition);
            }
        }

        // Start all servers
        foreach (self::$servers as $server) {
            $server->start();
        }

        self::$started = true;
    }

    /**
     * Stop all running frontend servers.
     */
    public static function stopAll(): void
    {
        foreach (self::$servers as $server) {
            $server->stop();
        }

        self::$definitions = [];
        self::$servers     = [];
        self::$started     = false;
    }

    /**
     * Check if any servers are registered.
     */
    public static function hasServers(): bool
    {
        // Check definitions for serve commands since servers are created lazily
        foreach (self::$definitions as $definition) {
            if ($definition->hasServeCommand()) {
                return true;
            }
        }

        return self::$servers !== [];
    }

    /**
     * Reset the manager and stop all servers.
     */
    public static function reset(): void
    {
        self::stopAll();
    }
}
