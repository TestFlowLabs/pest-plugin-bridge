<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a port is already in use and cannot be reused.
 *
 * Provides context-specific error messages based on marker file verification:
 * - Different application: Another Bridge-started app is using the port
 * - Unknown process: No marker file, can't identify what's running
 * - Stale marker: Our server died, something else grabbed the port
 */
final class PortInUseException extends RuntimeException
{
    /**
     * Port is in use by a different Bridge-started application.
     *
     * The marker file indicates a different working directory,
     * meaning a different frontend project is running.
     */
    public static function differentApplication(int $port, string $url, string $existingCwd): self
    {
        $message = <<<MESSAGE
Port {$port} is in use by a different application.

You specified: {$url}
But port {$port} is being used by: {$existingCwd}

This happens when:
  - You have multiple frontend projects
  - Another project's dev server is still running
  - You switched projects without stopping the previous server

Solutions:
  1. Stop the other application's server
  2. Use a different port for this project
  3. If you're sure they're the same app, check your working directory config
MESSAGE;

        return new self($message);
    }

    /**
     * Port is in use by an unknown process (no marker file).
     *
     * Bridge can't verify what's running on this port.
     */
    public static function unknownProcess(int $port, string $url): self
    {
        $message = <<<MESSAGE
Port {$port} is in use by an unknown process.

The frontend server at {$url} cannot start because the port is occupied
by a process Bridge didn't start.

This usually means:
  - A dev server was started manually (not via Bridge)
  - A previous test run didn't clean up properly
  - Another application is using this port

Solutions:
  1. Stop the process using port {$port}:
     lsof -ti:{$port} | xargs kill -9

  2. Use a different port:
     Bridge::add('http://localhost:XXXX')

  3. If you started the frontend manually and trust it:
     ->trustExistingServer()

  4. For Vite: add --strictPort to fail fast:
     ->serve('npm run dev -- --strictPort', ...)
MESSAGE;

        return new self($message);
    }

    /**
     * Marker indicates our server died but something else grabbed the port.
     */
    public static function staleMarker(int $port, string $url): self
    {
        $message = <<<MESSAGE
Port {$port} has a stale marker - server died and port was reused.

The frontend server at {$url} was previously started by Bridge,
but the process died and something else is now using the port.

This usually means:
  - The frontend server crashed and another process grabbed the port
  - A new dev server was started manually after the old one died

Solutions:
  1. Stop whatever is using port {$port}:
     lsof -ti:{$port} | xargs kill -9

  2. Use a different port:
     Bridge::add('http://localhost:XXXX')
MESSAGE;

        return new self($message);
    }
}
