<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Bridge;
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
        $port         = 18765;

        // Configure Bridge with serve command
        Bridge::add("http://localhost:{$port}")
            ->serve(
                command: "php -S localhost:{$port} -t {$fixturesPath}",
                cwd: $fixturesPath
            )
            ->readyWhen('Development Server');

        // Start the server via FrontendManager
        FrontendManager::instance()->startAll();

        // Give server time to fully start
        usleep(500000);

        // Verify server is accessible
        $ch = curl_init("http://localhost:{$port}/index.html");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        expect($httpCode)->toBe(200);
        expect($response)->toContain('Welcome to Test App');

        // Stop the server
        FrontendManager::reset();

        // Give time for cleanup
        usleep(200000);
    })->skip(
        !function_exists('curl_init'),
        'curl extension required'
    );
});
