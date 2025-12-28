<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

/**
 * Defines a frontend configuration with optional serve command.
 *
 * This class uses the builder pattern for fluent configuration
 * of frontend servers that need to be started before tests.
 */
final class FrontendDefinition
{
    private ?string $serveCommand     = null;
    private ?string $workingDirectory = null;

    /**
     * Warmup delay in milliseconds after server is ready.
     * Useful for large frontends that need extra time to fully initialize.
     */
    private int $warmupDelayMs = 0;

    /**
     * Default pattern covers most frontend dev servers:
     * - Nuxt: "Local: http://localhost:3000"
     * - Vite: "VITE ready in 500ms", "http://localhost:5173"
     * - Next.js: "ready - started server", "http://localhost:3000"
     * - CRA: "Compiled successfully!"
     * - Angular: "listening on localhost:4200"
     * - Generic: any http:// or https:// URL output
     */
    private string $readyPattern = 'ready|localhost|started|listening|compiled|http://|https://';

    public function __construct(
        public readonly string $url,
        public readonly ?string $name = null,
    ) {}

    /**
     * Set the command to start the frontend server.
     *
     * @param  string  $command  The shell command to run (e.g., 'npm run dev')
     * @param  string|null  $cwd  The working directory for the command
     */
    public function serve(string $command, ?string $cwd = null): self
    {
        $this->serveCommand     = $command;
        $this->workingDirectory = $cwd;

        return $this;
    }

    /**
     * Set the pattern to detect when the server is ready.
     *
     * @param  string  $pattern  Regex pattern to match in server output
     */
    public function readyWhen(string $pattern): self
    {
        $this->readyPattern = $pattern;

        return $this;
    }

    /**
     * Set a warmup delay after the server is ready.
     *
     * Large frontends may need extra time after reporting "ready"
     * before they can handle page loads efficiently. This delay
     * is applied after the ready pattern matches and HTTP is accessible.
     *
     * @param  int  $milliseconds  Delay in milliseconds (default: 0)
     */
    public function warmup(int $milliseconds): self
    {
        $this->warmupDelayMs = $milliseconds;

        return $this;
    }

    /**
     * Check if this definition has a serve command.
     */
    public function hasServeCommand(): bool
    {
        return $this->serveCommand !== null;
    }

    /**
     * Get the serve command.
     */
    public function getServeCommand(): ?string
    {
        return $this->serveCommand;
    }

    /**
     * Get the working directory for the serve command.
     */
    public function getWorkingDirectory(): ?string
    {
        return $this->workingDirectory;
    }

    /**
     * Get the ready detection pattern.
     */
    public function getReadyPattern(): string
    {
        return $this->readyPattern;
    }

    /**
     * Get the warmup delay in milliseconds.
     */
    public function getWarmupDelayMs(): int
    {
        return $this->warmupDelayMs;
    }
}
