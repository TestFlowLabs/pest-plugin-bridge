<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Bridge;

use function Tests\Helpers\getAvailablePort;
use function Tests\Helpers\waitForServerReady;

use TestFlowLabs\PestPluginBridge\FrontendManager;

/*
 * Integration tests for FrontendServer using PHP built-in server.
 *
 * These tests verify the serve() functionality works correctly
 * by using a simple PHP built-in server as a "frontend".
 */

beforeEach(function (): void {
    Bridge::reset();
    FrontendManager::reset();
});

afterEach(function (): void {
    FrontendManager::reset();
    Bridge::reset();
});

describe('FrontendServer Integration', function (): void {
    test('can start and stop a simple server', function (): void {
        $fixturesPath = __DIR__.'/../fixtures';
        $port         = getAvailablePort();

        // Configure Bridge with serve command
        Bridge::add("http://localhost:{$port}")
            ->serve(
                command: "php -S localhost:{$port} -t {$fixturesPath}",
                cwd: $fixturesPath
            )
            ->readyWhen('Development Server');

        // Start the server via FrontendManager
        FrontendManager::startAll();

        // Wait for server to be ready (with polling instead of fixed sleep)
        $url = "http://localhost:{$port}/index.html";
        expect(waitForServerReady($url, timeoutMs: 5000))->toBeTrue();

        // Verify server is accessible
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        expect($httpCode)->toBe(200);
        expect($response)->toContain('Welcome to Test App');

        // Stop the server
        FrontendManager::reset();
    })->skip(
        !function_exists('curl_init'),
        'curl extension required'
    );
});
