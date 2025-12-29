<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

use RuntimeException;
use Pest\Browser\ServerManager;
use Symfony\Component\Process\Process;
use TestFlowLabs\PestPluginBridge\Http\CurlHttpClient;
use TestFlowLabs\PestPluginBridge\Http\HttpClientInterface;
use TestFlowLabs\PestPluginBridge\Exceptions\PortInUseException;

/**
 * Manages the lifecycle of a frontend development server.
 *
 * Uses Symfony Process to start and stop frontend servers,
 * automatically injecting the Laravel API URL as environment variables.
 *
 * Server Identification:
 * When Bridge starts a server, it writes a marker file to the system temp directory.
 * This marker contains port, CWD, command, PID, and timestamp. When the port is
 * already in use, Bridge checks the marker to determine if it's safe to reuse:
 * - Match: Same CWD, PID alive → safe to reuse (our server)
 * - Stale: Same CWD, PID dead → clean up and restart
 * - Mismatch: Different CWD → different app, throw exception
 * - None: No marker → unknown process, throw exception
 */
final class FrontendServer
{
    private ?Process $process = null;

    /**
     * Whether we're reusing an existing server (identified via marker).
     * When true, stop() should not try to stop anything.
     */
    private bool $reusingExisting = false;

    public function __construct(
        private readonly FrontendDefinition $definition,
        private readonly HttpClientInterface $httpClient = new CurlHttpClient(),
    ) {}

    /**
     * Start the frontend server if not already running.
     *
     * Uses marker files for safe server identification:
     * - Match: Our server is running, reuse it
     * - Stale: Our server died, clean up marker and restart
     * - Mismatch: Different app running, throw exception
     * - None: Unknown process OR trustExistingServer enabled → handle accordingly
     *
     * @throws RuntimeException If the server fails to start
     * @throws PortInUseException If port is in use by an unknown or different application
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

        $port = $this->extractPort($this->definition->url);
        $cwd  = $this->definition->getWorkingDirectory() ?? getcwd() ?: '.';

        // Check marker to determine what's running on this port
        $markerStatus = ServerMarker::verify($port, $cwd);

        if ($markerStatus === 'match') {
            // Our server is already running - safe to reuse
            $this->reusingExisting = true;

            return;
        }

        if ($markerStatus === 'mismatch') {
            // Different application running on this port
            $existingCwd = ServerMarker::getMarkerCwd($port);
            throw PortInUseException::differentApplication($port, $this->definition->url, $existingCwd ?? 'unknown');
        }

        // 'stale' or 'none' - check if port is actually in use
        if ($this->isPortInUse($port)) {
            if ($markerStatus === 'stale') {
                // Our server died but something else grabbed the port
                throw PortInUseException::staleMarker($port, $this->definition->url);
            }

            // No marker - unknown process
            if ($this->definition->shouldTrustExistingServer()) {
                // User explicitly said to trust it
                $this->reusingExisting = true;

                return;
            }

            throw PortInUseException::unknownProcess($port, $this->definition->url);
        }

        // Inject API URL environment variables so frontend calls the test server
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

                return preg_match("#{$pattern}#i", $output) === 1;
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

        // Write marker file so other test runs can identify this server
        if ($this->process instanceof Process) {
            $pid = $this->process->getPid();
            if ($pid !== null) {
                ServerMarker::write($port, $cwd, $command, $pid);
            }
        }
    }

    /**
     * Stop the frontend server if running.
     *
     * If we're reusing an existing server (not one we started),
     * we don't stop it - someone else owns that process.
     * Deletes the marker file when stopping a server we started.
     */
    public function stop(): void
    {
        // Don't stop a server we didn't start
        if ($this->reusingExisting) {
            $this->reusingExisting = false;

            return;
        }

        if ($this->process instanceof Process && $this->isRunning()) {
            // Delete marker file before stopping
            $port = $this->extractPort($this->definition->url);
            ServerMarker::delete($port);

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
        // If we're reusing an existing server, consider it "running"
        if ($this->reusingExisting) {
            return true;
        }

        return $this->process instanceof Process
            && $this->process->isRunning();
    }

    /**
     * Check if we're reusing an existing server.
     */
    public function isReusingExisting(): bool
    {
        return $this->reusingExisting;
    }

    /**
     * Get environment variables with API URL injected.
     *
     * Injects the Laravel test server URL into common frontend environment
     * variables so the frontend calls the test server instead of production.
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

        // Get the test Laravel server URL from pest-plugin-browser
        $apiUrl = $this->getApiUrl();

        // Inject API URL for various frontend frameworks
        // These override any .env.local values when the frontend starts
        $apiVariables = [
            // Generic
            'API_URL'      => $apiUrl,
            'API_BASE_URL' => $apiUrl,
            'BACKEND_URL'  => $apiUrl,

            // Vite (Vue, React, Svelte, etc.)
            'VITE_API_URL'      => $apiUrl,
            'VITE_API_BASE_URL' => $apiUrl,
            'VITE_BACKEND_URL'  => $apiUrl,

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

        // Add custom environment variables from definition
        // These allow project-specific API endpoints (e.g., /v1/admin/, /v1/retailer/)
        foreach ($this->definition->getCustomEnvVars() as $name => $pathSuffix) {
            $apiVariables[$name] = rtrim($apiUrl, '/').'/'.ltrim($pathSuffix, '/');
        }

        // Merge with inherited environment, API variables take precedence
        return array_merge($env, $apiVariables);
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
            $httpCode = $this->httpClient->check($url, timeout: 1);

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
        $this->httpClient->get($this->definition->url, timeout: 10);
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

    /**
     * Check if a port is currently in use.
     *
     * Attempts to open a socket connection to localhost on the given port.
     * If the connection succeeds, the port is in use.
     */
    private function isPortInUse(int $port): bool
    {
        $socket = @fsockopen('localhost', $port, $errno, $errstr, 1);

        if ($socket !== false) {
            fclose($socket);

            return true; // Port is in use
        }

        return false; // Port is available
    }

    /**
     * Extract the port number from a URL.
     *
     * Returns the explicit port from the URL, or the default port
     * based on the scheme (443 for https, 80 for http).
     */
    private function extractPort(string $url): int
    {
        $parsed = parse_url($url);

        if ($parsed !== false && isset($parsed['port'])) {
            return $parsed['port'];
        }

        // No explicit port - use default based on scheme
        $scheme = $parsed['scheme'] ?? 'http';

        return $scheme === 'https' ? 443 : 80;
    }
}
