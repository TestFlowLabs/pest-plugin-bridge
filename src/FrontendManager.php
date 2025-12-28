<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

/**
 * Manages the lifecycle of all frontend servers.
 *
 * This singleton class coordinates starting and stopping
 * all registered frontend servers during test execution.
 */
final class FrontendManager
{
    private static ?FrontendManager $instance = null;

    /** @var array<string, FrontendDefinition> */
    private array $definitions = [];

    /** @var array<string, FrontendServer> */
    private array $servers = [];

    private bool $started = false;

    /**
     * Get the singleton instance.
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Register a frontend definition.
     *
     * The definition is stored and servers are created lazily
     * when startAll() is called, allowing fluent configuration.
     */
    public function register(FrontendDefinition $definition): void
    {
        $key                     = $definition->name ?? 'default';
        $this->definitions[$key] = $definition;
    }

    /**
     * Start all registered frontend servers.
     *
     * Creates FrontendServer instances for definitions with serve commands
     * and starts them. This is called lazily on first bridge() call.
     */
    public function startAll(): void
    {
        if ($this->started) {
            return;
        }

        // Create servers from definitions that have serve commands
        foreach ($this->definitions as $key => $definition) {
            if ($definition->hasServeCommand()) {
                $this->servers[$key] = new FrontendServer($definition);
            }
        }

        // Start all servers
        foreach ($this->servers as $server) {
            $server->start();
        }

        $this->started = true;
    }

    /**
     * Stop all running frontend servers.
     */
    public function stopAll(): void
    {
        foreach ($this->servers as $server) {
            $server->stop();
        }

        $this->definitions = [];
        $this->servers     = [];
        $this->started     = false;
    }

    /**
     * Check if any servers are registered.
     */
    public function hasServers(): bool
    {
        // Check definitions for serve commands since servers are created lazily
        foreach ($this->definitions as $definition) {
            if ($definition->hasServeCommand()) {
                return true;
            }
        }

        return $this->servers !== [];
    }

    /**
     * Reset the manager and stop all servers.
     */
    public static function reset(): void
    {
        self::$instance?->stopAll();
        self::$instance = null;
    }
}
