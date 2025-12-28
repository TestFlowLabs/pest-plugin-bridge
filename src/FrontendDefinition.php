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
    private ?string $serveCommand = null;

    private ?string $workingDirectory = null;

    private string $readyPattern = 'ready|localhost|started|listening';

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
        $this->serveCommand = $command;
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
}
