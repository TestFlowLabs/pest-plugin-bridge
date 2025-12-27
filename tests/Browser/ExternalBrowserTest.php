<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Configuration;

/**
 * Integration tests for browser functionality.
 *
 * These tests use actual browser automation via Playwright
 * to verify the visitExternal() method works correctly.
 */

/**
 * Start a PHP built-in server to serve the fixtures directory.
 */
function startFixtureServer(int $port = 8765): int
{
    $fixturesPath = __DIR__.'/../fixtures';
    $command = sprintf(
        'php -S localhost:%d -t %s > /dev/null 2>&1 & echo $!',
        $port,
        escapeshellarg($fixturesPath)
    );

    $pid = shell_exec($command);

    if ($pid === null) {
        return 0;
    }

    // Wait for server to start
    usleep(500000); // 500ms

    return (int) trim($pid);
}

/**
 * Stop the fixture server by killing the process.
 */
function stopFixtureServer(int $pid): void
{
    if ($pid > 0) {
        exec("kill {$pid} 2>/dev/null");
    }
}

/**
 * Wait for the server to be ready.
 */
function waitForServer(string $url, int $timeoutSeconds = 5): bool
{
    $start = time();

    while (time() - $start < $timeoutSeconds) {
        $context = stream_context_create([
            'http' => ['timeout' => 1],
        ]);

        $result = @file_get_contents($url, false, $context);

        if ($result !== false) {
            return true;
        }

        usleep(100000); // 100ms
    }

    return false;
}

// Server configuration
$serverPort = 8765;
$serverPid = 0;

beforeAll(function () use (&$serverPid, $serverPort): void {
    $serverPid = startFixtureServer($serverPort);

    if ($serverPid === 0) {
        throw new RuntimeException('Failed to start fixture server');
    }

    $serverUrl = "http://localhost:{$serverPort}/index.html";

    if (! waitForServer($serverUrl)) {
        stopFixtureServer($serverPid);
        throw new RuntimeException('Fixture server did not start in time');
    }

    Configuration::setExternalUrl("http://localhost:{$serverPort}");
});

afterAll(function () use (&$serverPid): void {
    if ($serverPid > 0) {
        stopFixtureServer($serverPid);
    }

    Configuration::reset();
});

test('can visit external page and see content', function (): void {
    $this->visitExternal('/index.html')
        ->assertSee('Welcome to Test App');
});

test('can interact with form elements', function (): void {
    $this->visitExternal('/index.html')
        ->fill('[data-testid="email"]', 'test@example.com')
        ->fill('[data-testid="password"]', 'secret123')
        ->click('[data-testid="login-button"]')
        ->assertSee('Login successful');
});

test('can verify page title', function (): void {
    $this->visitExternal('/index.html')
        ->assertTitle('Test App');
});

test('can verify elements with data-testid', function (): void {
    $this->visitExternal('/index.html')
        ->assertSeeIn('[data-testid="title"]', 'Welcome to Test App')
        ->assertSeeIn('[data-testid="description"]', 'This is a simple test application');
});

test('shows error message when fields are empty', function (): void {
    $this->visitExternal('/index.html')
        ->click('[data-testid="login-button"]')
        ->assertSee('Please fill in all fields');
});
