<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Http\FakeHttpClient;

describe('FakeHttpClient', function (): void {
    beforeEach(function (): void {
        $this->client = new FakeHttpClient();
    });

    describe('check()', function (): void {
        test('returns default 200 when no response configured', function (): void {
            expect($this->client->check('http://localhost:3000'))
                ->toBe(200);
        });

        test('returns configured response for specific URL', function (): void {
            $this->client->fakeCheck('http://localhost:3000', 404);

            expect($this->client->check('http://localhost:3000'))
                ->toBe(404);
        });

        test('can configure different responses for different URLs', function (): void {
            $this->client
                ->fakeCheck('http://localhost:3000', 200)
                ->fakeCheck('http://localhost:3001', 500);

            expect($this->client->check('http://localhost:3000'))->toBe(200);
            expect($this->client->check('http://localhost:3001'))->toBe(500);
        });

        test('can simulate connection failure with 0', function (): void {
            $this->client->fakeCheck('http://localhost:3000', 0);

            expect($this->client->check('http://localhost:3000'))
                ->toBe(0);
        });

        test('can change default response', function (): void {
            $this->client->setDefaultCheckResponse(503);

            expect($this->client->check('http://unconfigured-url.com'))
                ->toBe(503);
        });

        test('records request with method, url, and timeout', function (): void {
            $this->client->check('http://localhost:3000', timeout: 5);

            $requests = $this->client->getRequests();

            expect($requests)->toHaveCount(1);
            expect($requests[0])->toBe([
                'method'  => 'HEAD',
                'url'     => 'http://localhost:3000',
                'timeout' => 5,
            ]);
        });
    });

    describe('get()', function (): void {
        test('returns default empty string when no response configured', function (): void {
            expect($this->client->get('http://localhost:3000'))
                ->toBe('');
        });

        test('returns configured response for specific URL', function (): void {
            $this->client->fakeGet('http://localhost:3000', '<html>Hello</html>');

            expect($this->client->get('http://localhost:3000'))
                ->toBe('<html>Hello</html>');
        });

        test('can simulate failure with false', function (): void {
            $this->client->fakeGet('http://localhost:3000', false);

            expect($this->client->get('http://localhost:3000'))
                ->toBeFalse();
        });

        test('can change default response', function (): void {
            $this->client->setDefaultGetResponse('<default>');

            expect($this->client->get('http://unconfigured-url.com'))
                ->toBe('<default>');
        });

        test('records request with method, url, and timeout', function (): void {
            $this->client->get('http://localhost:3000', timeout: 15);

            $requests = $this->client->getRequests();

            expect($requests)->toHaveCount(1);
            expect($requests[0])->toBe([
                'method'  => 'GET',
                'url'     => 'http://localhost:3000',
                'timeout' => 15,
            ]);
        });
    });

    describe('request tracking', function (): void {
        test('wasRequested returns true for requested URL', function (): void {
            $this->client->check('http://localhost:3000');

            expect($this->client->wasRequested('http://localhost:3000'))
                ->toBeTrue();
        });

        test('wasRequested returns false for non-requested URL', function (): void {
            $this->client->check('http://localhost:3000');

            expect($this->client->wasRequested('http://localhost:9999'))
                ->toBeFalse();
        });

        test('wasRequested can filter by method', function (): void {
            $this->client->check('http://localhost:3000');
            $this->client->get('http://localhost:3000');

            expect($this->client->wasRequested('http://localhost:3000', 'HEAD'))
                ->toBeTrue();
            expect($this->client->wasRequested('http://localhost:3000', 'GET'))
                ->toBeTrue();
            expect($this->client->wasRequested('http://localhost:3000', 'POST'))
                ->toBeFalse();
        });

        test('requestCount counts requests correctly', function (): void {
            $this->client->check('http://localhost:3000');
            $this->client->check('http://localhost:3000');
            $this->client->get('http://localhost:3000');

            expect($this->client->requestCount('http://localhost:3000'))
                ->toBe(3);
            expect($this->client->requestCount('http://localhost:3000', 'HEAD'))
                ->toBe(2);
            expect($this->client->requestCount('http://localhost:3000', 'GET'))
                ->toBe(1);
        });

        test('clearRequests removes all recorded requests', function (): void {
            $this->client->check('http://localhost:3000');
            $this->client->get('http://localhost:3000');

            $this->client->clearRequests();

            expect($this->client->getRequests())->toBeEmpty();
            expect($this->client->wasRequested('http://localhost:3000'))->toBeFalse();
        });
    });

    describe('reset()', function (): void {
        test('clears all configured responses and requests', function (): void {
            $this->client
                ->fakeCheck('http://localhost:3000', 404)
                ->fakeGet('http://localhost:3000', 'content')
                ->setDefaultCheckResponse(503)
                ->setDefaultGetResponse('default');

            $this->client->check('http://localhost:3000');

            $this->client->reset();

            // Defaults restored
            expect($this->client->check('http://any-url.com'))->toBe(200);
            expect($this->client->get('http://any-url.com'))->toBe('');

            // Requests after reset (2: check + get from above assertions)
            expect($this->client->getRequests())->toHaveCount(2);
        });
    });
});
