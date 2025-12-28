<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

use RuntimeException;
use Pest\Browser\ServerManager;
use Symfony\Component\Process\Process;

/**
 * Manages the lifecycle of a frontend development server.
 *
 * Uses Symfony Process to start and stop frontend servers,
 * automatically injecting the Laravel API URL as environment variables.
 */
final class FrontendServer
{
    private ?Process $process = null;

    public function __construct(
        private readonly FrontendDefinition $definition,
    ) {}

    /**
     * Start the frontend server if not already running.
     *
     * @throws RuntimeException If the server fails to start
     */
    public function start(): void
    {
        if ($this->isRunning()) {
            return;
        }

        $command = $this->definition->getServeCommand();
        if ($command === null) {
            return;
        }

        $this->process = Process::fromShellCommandline(
            $command,
            $this->definition->getWorkingDirectory(),
            $this->getEnvironmentVariables(),
        );

        $this->process->setTimeout(0);
        $this->process->start();

        $pattern = $this->definition->getReadyPattern();
        $output  = '';

        // Wait for server to be ready
        $ready = $this->process->waitUntil(
            function (string $type, string $data) use ($pattern, &$output): bool {
                $output .= $data;

                return preg_match("/{$pattern}/i", $output) === 1;
            }
        );

        if (!$ready && !$this->isRunning()) {
            throw new RuntimeException(
                "Frontend server failed to start: {$command}\nOutput: {$output}"
            );
        }

        if (!$this->isRunning()) {
            throw new RuntimeException(
                "Frontend server exited unexpectedly: {$command}\nOutput: {$output}"
            );
        }
    }

    /**
     * Stop the frontend server if running.
     */
    public function stop(): void
    {
        if ($this->process instanceof Process && $this->isRunning()) {
            $this->process->stop(
                timeout: 0.1,
                signal: PHP_OS_FAMILY === 'Windows' ? null : SIGTERM,
            );
        }

        $this->process = null;
    }

    /**
     * Check if the frontend server is running.
     */
    public function isRunning(): bool
    {
        return $this->process instanceof Process
            && $this->process->isRunning();
    }

    /**
     * Get environment variables with API URL injected.
     *
     * @return array<string, string>
     */
    private function getEnvironmentVariables(): array
    {
        $apiUrl = $this->getApiUrl();

        $apiVariables = [
            // Generic
            'API_URL'      => $apiUrl,
            'API_BASE_URL' => $apiUrl,
            'BACKEND_URL'  => $apiUrl,

            // Vite
            'VITE_API_URL'      => $apiUrl,
            'VITE_API_BASE_URL' => $apiUrl,

            // Nuxt 3
            'NUXT_PUBLIC_API_BASE' => $apiUrl,
            'NUXT_PUBLIC_API_URL'  => $apiUrl,

            // Next.js
            'NEXT_PUBLIC_API_URL'      => $apiUrl,
            'NEXT_PUBLIC_API_BASE_URL' => $apiUrl,

            // Create React App
            'REACT_APP_API_URL'      => $apiUrl,
            'REACT_APP_API_BASE_URL' => $apiUrl,
        ];

        // Merge with existing environment, keeping only string values
        /** @var array<string, string> $env */
        $env = array_filter($_ENV, is_string(...));

        return array_merge($env, $apiVariables);
    }

    /**
     * Get the Laravel API URL from pest-plugin-browser's HTTP server.
     *
     * Ensures the HTTP server is started before getting the URL.
     * Uses the rewrite() method to get the properly formatted URL.
     */
    private function getApiUrl(): string
    {
        // @phpstan-ignore staticMethod.internalClass
        $httpServer = ServerManager::instance()->http();

        // Ensure the HTTP server is started before getting the URL
        // @phpstan-ignore method.internalInterface
        $httpServer->start();

        // Use rewrite to get the proper URL with host and port
        // @phpstan-ignore method.internalInterface
        return rtrim($httpServer->rewrite('/'), '/');
    }
}
