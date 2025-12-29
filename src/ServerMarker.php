<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

/**
 * Manages marker files for frontend server identification.
 *
 * Marker files allow Bridge to identify servers it started vs unknown processes.
 * This enables safe automatic server reuse without risking connection to wrong apps.
 *
 * Marker file structure:
 * {
 *     "port": 5173,
 *     "cwd": "/path/to/frontend",
 *     "command": "npm run dev",
 *     "pid": 12345,
 *     "started_at": 1704288600
 * }
 */
final class ServerMarker
{
    private const string MARKER_PREFIX = 'bridge_server_';

    private const string MARKER_EXTENSION = '.json';

    /**
     * Write a marker file for a started server.
     */
    public static function write(int $port, string $cwd, string $command, int $pid): void
    {
        $marker = [
            'port'       => $port,
            'cwd'        => self::normalizePath($cwd),
            'command'    => $command,
            'pid'        => $pid,
            'started_at' => time(),
        ];

        file_put_contents(
            self::getMarkerPath($port),
            json_encode($marker, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Read a marker file for a port.
     *
     * @return array{port: int, cwd: string, command: string, pid: int, started_at: int}|null
     */
    public static function read(int $port): ?array
    {
        $path = self::getMarkerPath($port);

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        try {
            /** @var array{port: int, cwd: string, command: string, pid: int, started_at: int} $marker */
            $marker = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            return $marker;
        } catch (\JsonException) {
            // Corrupted marker file - delete it
            self::delete($port);

            return null;
        }
    }

    /**
     * Delete a marker file.
     */
    public static function delete(int $port): void
    {
        $path = self::getMarkerPath($port);

        if (file_exists($path)) {
            @unlink($path);
        }
    }

    /**
     * Check if a marker exists and matches the expected configuration.
     *
     * Returns:
     * - 'match': Marker exists, CWD matches, PID is alive → safe to reuse
     * - 'stale': Marker exists, CWD matches, PID is dead → clean up and start fresh
     * - 'mismatch': Marker exists but CWD is different → different app running
     * - 'none': No marker file → unknown process
     */
    public static function verify(int $port, string $expectedCwd): string
    {
        $marker = self::read($port);

        if ($marker === null) {
            return 'none';
        }

        $normalizedExpected = self::normalizePath($expectedCwd);
        $normalizedMarker   = $marker['cwd'];

        // CWD doesn't match - different application
        if ($normalizedExpected !== $normalizedMarker) {
            return 'mismatch';
        }

        // CWD matches - check if PID is still alive
        if (!self::isPidRunning($marker['pid'])) {
            // Our server died, marker is stale
            self::delete($port);

            return 'stale';
        }

        // Everything matches - safe to reuse
        return 'match';
    }

    /**
     * Get the CWD from a marker file (for error messages).
     */
    public static function getMarkerCwd(int $port): ?string
    {
        $marker = self::read($port);

        return $marker['cwd'] ?? null;
    }

    /**
     * Get the marker file path for a port.
     */
    public static function getMarkerPath(int $port): string
    {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR.self::MARKER_PREFIX.$port.self::MARKER_EXTENSION;
    }

    /**
     * Check if a process ID is still running.
     */
    private static function isPidRunning(int $pid): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            exec("tasklist /FI \"PID eq {$pid}\" 2>NUL", $output, $exitCode);

            return $exitCode === 0 && count($output) > 1;
        }

        // Unix: Send signal 0 to check if process exists
        return posix_kill($pid, 0);
    }

    /**
     * Normalize a path for comparison.
     */
    private static function normalizePath(string $path): string
    {
        $realpath = realpath($path);

        return $realpath !== false ? $realpath : $path;
    }
}
