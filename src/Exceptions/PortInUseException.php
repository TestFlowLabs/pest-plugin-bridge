<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a port is already in use and cannot be reused.
 *
 * This exception provides helpful guidance for resolving port conflicts
 * in both local development and CI environments.
 */
final class PortInUseException extends RuntimeException
{
    public function __construct(int $port, string $url)
    {
        $message = <<<MESSAGE
Port {$port} is already in use.

The frontend server at {$url} cannot start because the port is occupied.

This usually means:
  • A previous test run didn't clean up properly
  • Another development server is using this port
  • A background process is holding the port

Options:
  1. Stop the process using port {$port}:
     lsof -ti:{$port} | xargs kill -9

  2. Use a different port:
     Bridge::add('http://localhost:XXXX')

  3. Reuse the existing server (if it's yours):
     ->reuseExistingServer()

  4. For Vite: add --strictPort to fail fast:
     ->serve('npm run dev -- --strictPort', ...)
MESSAGE;

        parent::__construct($message);
    }
}
