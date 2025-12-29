<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Symfony\Component\Process\Process;

/**
 * Get an available port for testing.
 *
 * Finds an available port in the specified range by checking
 * if each random port is available.
 *
 * @param  int  $min  Minimum port number (default: 18000)
 * @param  int  $max  Maximum port number (default: 19000)
 */
function getAvailablePort(int $min = 18000, int $max = 19000): int
{
    $attempts = 50;

    while ($attempts-- > 0) {
        $port = random_int($min, $max);

        if (isPortAvailable($port)) {
            return $port;
        }
    }

    // Last resort: return a random port in range
    return random_int($min, $max);
}

/**
 * Check if a port is available (not in use).
 */
function isPortAvailable(int $port): bool
{
    $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.5);

    if ($connection !== false) {
        fclose($connection);

        return false; // Port is in use
    }

    return true; // Port is available
}

/**
 * Wait for a server to become ready.
 *
 * Uses exponential backoff with polling instead of fixed usleep().
 *
 * @param  string  $url  URL to check
 * @param  int  $timeoutMs  Maximum time to wait in milliseconds
 * @param  int  $intervalMs  Initial polling interval in milliseconds
 *
 * @return bool True if server became ready, false if timeout
 */
function waitForServerReady(string $url, int $timeoutMs = 5000, int $intervalMs = 100): bool
{
    $startTime   = microtime(true) * 1000;
    $maxInterval = 500; // Cap interval at 500ms

    while ((microtime(true) * 1000 - $startTime) < $timeoutMs) {
        if (isUrlAccessible($url)) {
            return true;
        }

        usleep($intervalMs * 1000);

        // Exponential backoff (double interval up to max)
        $intervalMs = min($intervalMs * 2, $maxInterval);
    }

    return false;
}

/**
 * Check if a URL is accessible (returns 2xx or 3xx status).
 */
function isUrlAccessible(string $url, int $timeout = 1): bool
{
    $ch = curl_init($url);

    if ($ch === false) {
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_NOBODY         => true,
    ]);

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 400;
}

/**
 * Start a PHP built-in server for testing.
 *
 * @param  int  $port  Port to run on
 * @param  string  $docRoot  Document root path
 * @param  int  $waitMs  Max time to wait for server to be ready
 *
 * @return Process|null The started process, or null if failed
 */
function startTestServer(int $port, string $docRoot, int $waitMs = 3000): ?Process
{
    $process = new Process(['php', '-S', "localhost:{$port}", '-t', $docRoot]);
    $process->start();

    $url = "http://localhost:{$port}/";

    if (waitForServerReady($url, $waitMs)) {
        return $process;
    }

    // Server didn't start in time
    $process->stop();

    return null;
}

/**
 * Run a callback with a temporary test server.
 *
 * Ensures proper cleanup even if the callback throws.
 *
 * @param  string  $docRoot  Document root path
 * @param  callable  $callback  Callback that receives (port, process)
 * @param  int|null  $port  Specific port to use, or null for auto
 */
function withTestServer(string $docRoot, callable $callback, ?int $port = null): void
{
    $port    = $port ?? getAvailablePort();
    $process = startTestServer($port, $docRoot);

    if ($process === null) {
        throw new \RuntimeException("Failed to start test server on port {$port}");
    }

    try {
        $callback($port, $process);
    } finally {
        $process->stop();
    }
}
