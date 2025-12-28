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

        // Pass null for env to inherit the current process environment
        $this->process = Process::fromShellCommandline(
            $command,
            $this->definition->getWorkingDirectory(),
            null, // Inherit environment
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

        // Wait for HTTP server to actually respond (not just console output)
        $this->waitForHttpReady();

        // Apply warmup delay if configured (for large frontends)
        $warmupMs = $this->definition->getWarmupDelayMs();
        if ($warmupMs > 0) {
            usleep($warmupMs * 1000);
        }

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
        // Use getenv() to get all environment variables including PATH, HOME, etc.
        // $_ENV may be incomplete depending on PHP configuration
        $env = getenv();

        // Ensure it's an array of strings
        /** @var array<string, string> $env */
        $env = array_filter($env, is_string(...));

        return $env;
    }

    /**
     * Wait for the frontend HTTP server to actually respond to requests.
     *
     * The console output may indicate "ready" before the HTTP server
     * is fully accepting connections. This method polls the frontend
     * URL until it responds or times out.
     */
    private function waitForHttpReady(int $maxAttempts = 30, int $delayMs = 100): void
    {
        $url = $this->definition->url;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 1,
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_NOBODY         => true,
            ]);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode > 0) {
                // Server is responding, now warm it up with a full page request
                $this->warmupRequest();

                return;
            }

            usleep($delayMs * 1000);
        }

        // Server never responded, but don't throw - let the test fail with a better error
    }

    /**
     * Make a request to prime the frontend server's HTTP layer.
     *
     * Note: This only fetches the HTML shell. Full module compilation
     * in dev servers like Vite happens when the browser requests JS files.
     * Use the warmup() option on FrontendDefinition for additional delay.
     */
    private function warmupRequest(): void
    {
        $url = $this->definition->url;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        curl_exec($ch);
        curl_close($ch);
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
