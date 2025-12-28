<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Bridge;
use TestFlowLabs\PestPluginBridge\FrontendServer;
use TestFlowLabs\PestPluginBridge\FrontendDefinition;
use TestFlowLabs\PestPluginBridge\Http\FakeHttpClient;

describe('FrontendServer HTTP behavior', function (): void {
    beforeEach(function (): void {
        Bridge::reset();
        $this->httpClient = new FakeHttpClient();
    });

    afterEach(function (): void {
        Bridge::reset();
    });

    describe('HTTP readiness check', function (): void {
        test('FrontendServer accepts FakeHttpClient for testing', function (): void {
            $fakeClient = new FakeHttpClient();
            $fakeClient->fakeCheck('http://localhost:3000', 200);
            $fakeClient->fakeGet('http://localhost:3000', '<html></html>');

            $definition = new FrontendDefinition('http://localhost:3000');

            // Verify FrontendServer can be constructed with FakeHttpClient
            $server = new FrontendServer($definition, $fakeClient);

            expect($server)->toBeInstanceOf(FrontendServer::class);
        });

        test('accepts custom HTTP client via constructor', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');
            $fakeClient = new FakeHttpClient();

            $server = new FrontendServer($definition, $fakeClient);

            expect($server)->toBeInstanceOf(FrontendServer::class);
        });

        test('uses CurlHttpClient by default when no client provided', function (): void {
            $definition = new FrontendDefinition('http://localhost:3000');

            // This should not throw - uses default CurlHttpClient
            $server = new FrontendServer($definition);

            expect($server)->toBeInstanceOf(FrontendServer::class);
        });
    });

    describe('FakeHttpClient integration', function (): void {
        test('can simulate successful server response', function (): void {
            $client = new FakeHttpClient();
            $client->fakeCheck('http://localhost:3000', 200);
            $client->fakeGet('http://localhost:3000', '<html><body>App</body></html>');

            expect($client->check('http://localhost:3000'))->toBe(200);
            expect($client->get('http://localhost:3000'))->toContain('App');
        });

        test('can simulate server not responding', function (): void {
            $client = new FakeHttpClient();
            $client->fakeCheck('http://localhost:3000', 0);

            expect($client->check('http://localhost:3000'))->toBe(0);
        });

        test('can simulate various HTTP status codes', function (): void {
            $client = new FakeHttpClient();

            $client->fakeCheck('http://localhost:3000/ok', 200);
            $client->fakeCheck('http://localhost:3000/redirect', 302);
            $client->fakeCheck('http://localhost:3000/not-found', 404);
            $client->fakeCheck('http://localhost:3000/error', 500);

            expect($client->check('http://localhost:3000/ok'))->toBe(200);
            expect($client->check('http://localhost:3000/redirect'))->toBe(302);
            expect($client->check('http://localhost:3000/not-found'))->toBe(404);
            expect($client->check('http://localhost:3000/error'))->toBe(500);
        });

        test('tracks all HTTP requests made', function (): void {
            $client = new FakeHttpClient();

            $client->check('http://localhost:3000');
            $client->check('http://localhost:3000');
            $client->get('http://localhost:3000');

            expect($client->requestCount('http://localhost:3000'))->toBe(3);
            expect($client->requestCount('http://localhost:3000', 'HEAD'))->toBe(2);
            expect($client->requestCount('http://localhost:3000', 'GET'))->toBe(1);
        });

        test('can verify warmup request was made', function (): void {
            $client = new FakeHttpClient();
            $client->fakeCheck('http://localhost:5173', 200);
            $client->fakeGet('http://localhost:5173', '<html>Vite App</html>');

            // Simulate the HTTP flow that happens in FrontendServer
            $httpCode = $client->check('http://localhost:5173');
            if ($httpCode > 0) {
                $client->get('http://localhost:5173');
            }

            expect($client->wasRequested('http://localhost:5173', 'HEAD'))->toBeTrue();
            expect($client->wasRequested('http://localhost:5173', 'GET'))->toBeTrue();
        });
    });

    describe('HTTP failure scenarios', function (): void {
        test('handles connection timeout gracefully', function (): void {
            $client = new FakeHttpClient();
            $client->setDefaultCheckResponse(0); // All connections fail

            // Simulate multiple polling attempts
            $attempts    = 0;
            $maxAttempts = 5;

            while ($attempts < $maxAttempts) {
                $result = $client->check('http://localhost:3000');
                if ($result > 0) {
                    break;
                }
                $attempts++;
            }

            expect($attempts)->toBe($maxAttempts);
            expect($client->requestCount('http://localhost:3000', 'HEAD'))->toBe(5);
        });

        test('handles GET request failure', function (): void {
            $client = new FakeHttpClient();
            $client->fakeGet('http://localhost:3000', false);

            $result = $client->get('http://localhost:3000');

            expect($result)->toBeFalse();
        });
    });
});
