<?php

declare(strict_types=1);

use function Tests\Helpers\withTestServer;

use TestFlowLabs\PestPluginBridge\Http\CurlHttpClient;
use TestFlowLabs\PestPluginBridge\Http\HttpClientInterface;

describe('CurlHttpClient', function (): void {
    test('implements HttpClientInterface', function (): void {
        $client = new CurlHttpClient();

        expect($client)->toBeInstanceOf(HttpClientInterface::class);
    });

    describe('check()', function (): void {
        test('returns 0 for non-existent URL', function (): void {
            $client = new CurlHttpClient();

            // Use a definitely-not-listening port
            $result = $client->check('http://127.0.0.1:59999', timeout: 1);

            expect($result)->toBe(0);
        });

        test('returns HTTP status code for responding server', function (): void {
            $fixturesPath = realpath(__DIR__.'/../../fixtures');

            if ($fixturesPath === false) {
                $this->markTestSkipped('Fixtures directory not found');
            }

            withTestServer($fixturesPath, function (int $port): void {
                $client = new CurlHttpClient();
                $result = $client->check("http://localhost:{$port}/index.html", timeout: 2);

                expect($result)->toBe(200);
            });
        })->skip(fn (): bool => !extension_loaded('curl'), 'cURL extension required');
    });

    describe('get()', function (): void {
        test('returns false for non-existent URL', function (): void {
            $client = new CurlHttpClient();

            // Use a definitely-not-listening port
            $result = $client->get('http://127.0.0.1:59999', timeout: 1);

            expect($result)->toBeFalse();
        });

        test('returns response body for responding server', function (): void {
            $fixturesPath = realpath(__DIR__.'/../../fixtures');

            if ($fixturesPath === false) {
                $this->markTestSkipped('Fixtures directory not found');
            }

            withTestServer($fixturesPath, function (int $port): void {
                $client = new CurlHttpClient();
                $result = $client->get("http://localhost:{$port}/index.html", timeout: 2);

                expect($result)->toBeString();
                expect($result)->toContain('Welcome to Test App');
            });
        })->skip(fn (): bool => !extension_loaded('curl'), 'cURL extension required');
    });
});
