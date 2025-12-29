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
     * Path to env file to create/update with test server URLs.
     * Useful when process env vars don't override .env.local (Vite quirk).
     */
    private ?string $envFilePath = null;

    /**
     * Custom environment variables with path suffixes.
     * Keys are env var names, values are path suffixes (e.g., '/v1/retailer/').
     *
     * @var array<string, string>
     */
    private array $customEnvVars = [];

    /**
     * Whether to trust an unknown server on the port.
     *
     * By default, Bridge only reuses servers it can verify via marker files.
     * Enable this to trust any server running on the port (escape hatch for
     * CI environments or when running frontend manually without Bridge).
     */
    private bool $trustExistingServer = false;

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

    /**
     * Set the path to an env file to create with test server URLs.
     *
     * Vite's .env.local takes precedence over process environment variables.
     * This method allows creating a temporary env file (e.g., .env.test)
     * that will be read by the frontend when started with --mode test.
     *
     * @param  string  $path  Absolute path to the env file to create
     */
    public function envFile(string $path): self
    {
        $this->envFilePath = $path;

        return $this;
    }

    /**
     * Get the env file path.
     */
    public function getEnvFilePath(): ?string
    {
        return $this->envFilePath;
    }

    /**
     * Set custom environment variables with path suffixes.
     *
     * The test server URL will be prepended to each path suffix.
     * Use this for project-specific API endpoint environment variables.
     *
     * Example:
     * ```php
     * Bridge::add('http://localhost:5173')
     *     ->serve('npm run dev', cwd: '../frontend')
     *     ->env([
     *         'VITE_ADMIN_API'    => '/v1/admin/',
     *         'VITE_RETAILER_API' => '/v1/retailer/',
     *         'VITE_PUBLIC_API'   => '/v1/',
     *     ]);
     * ```
     *
     * @param  array<string, string>  $vars  Environment variable names and path suffixes
     */
    public function env(array $vars): self
    {
        $this->customEnvVars = $vars;

        return $this;
    }

    /**
     * Get custom environment variables.
     *
     * @return array<string, string>
     */
    public function getCustomEnvVars(): array
    {
        return $this->customEnvVars;
    }

    /**
     * Register a child frontend at a sub-path.
     *
     * Child frontends share the same server process as the parent.
     * This is useful when a single frontend serves multiple named sections
     * at different URL paths.
     *
     * Example:
     * ```php
     * Bridge::add('http://localhost:3001', 'admin')
     *     ->child('/analytics', 'analytics')
     *     ->child('/reports', 'reports')
     *     ->serve('npm run dev', cwd: '../admin-frontend');
     * ```
     *
     * @param  string  $path  The sub-path (e.g., '/analytics')
     * @param  string  $name  The name to register this child as
     */
    public function child(string $path, string $name): self
    {
        Bridge::registerChild($this->url, $path, $name);

        return $this;
    }

    /**
     * Trust any server running on the port (escape hatch).
     *
     * By default, Bridge uses marker files to identify servers it started.
     * This ensures you don't accidentally connect to the wrong application.
     *
     * Use this method when:
     * - Running frontend manually (not via Bridge's serve())
     * - CI environment where frontend is started separately
     * - You're certain the correct app is running
     *
     * Example:
     * ```php
     * Bridge::add('http://localhost:5173')
     *     ->serve('npm run dev', cwd: '../frontend')
     *     ->trustExistingServer();  // Skip marker verification
     * ```
     */
    public function trustExistingServer(): self
    {
        $this->trustExistingServer = true;

        return $this;
    }

    /**
     * Check if unknown servers should be trusted.
     */
    public function shouldTrustExistingServer(): bool
    {
        return $this->trustExistingServer;
    }
}
