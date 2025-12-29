<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\BrowserMockStore;

beforeEach(function (): void {
    BrowserMockStore::clear();
});

afterEach(function (): void {
    BrowserMockStore::clear();
});

describe('BrowserMockStore', function (): void {
    describe('set and get', function (): void {
        test('stores and retrieves mocks', function (): void {
            $mocks = [
                'https://api.example.com/*' => [
                    'status' => 200,
                    'body'   => ['message' => 'mocked'],
                ],
            ];

            BrowserMockStore::set($mocks);

            expect(BrowserMockStore::get())->toBe($mocks);
        });

        test('overwrites previous mocks', function (): void {
            BrowserMockStore::set(['https://old.com/*' => ['status' => 200]]);
            BrowserMockStore::set(['https://new.com/*' => ['status' => 201]]);

            expect(BrowserMockStore::get())->toBe(['https://new.com/*' => ['status' => 201]]);
        });

        test('stores mocks with all options', function (): void {
            $mocks = [
                'https://api.example.com/users' => [
                    'status'  => 201,
                    'body'    => ['id' => 1, 'name' => 'Test User'],
                    'headers' => ['X-Custom' => 'value'],
                ],
            ];

            BrowserMockStore::set($mocks);

            expect(BrowserMockStore::get())->toBe($mocks);
        });
    });

    describe('clear', function (): void {
        test('removes all mocks', function (): void {
            BrowserMockStore::set(['https://api.example.com/*' => ['status' => 200]]);
            BrowserMockStore::clear();

            expect(BrowserMockStore::get())->toBe([]);
        });

        test('can be called when empty', function (): void {
            BrowserMockStore::clear();
            BrowserMockStore::clear();

            expect(BrowserMockStore::get())->toBe([]);
        });
    });

    describe('hasMocks', function (): void {
        test('returns false when no mocks registered', function (): void {
            expect(BrowserMockStore::hasMocks())->toBeFalse();
        });

        test('returns true when mocks are registered', function (): void {
            BrowserMockStore::set(['https://api.example.com/*' => ['status' => 200]]);

            expect(BrowserMockStore::hasMocks())->toBeTrue();
        });

        test('returns false after clear', function (): void {
            BrowserMockStore::set(['https://api.example.com/*' => ['status' => 200]]);
            BrowserMockStore::clear();

            expect(BrowserMockStore::hasMocks())->toBeFalse();
        });
    });

    describe('count', function (): void {
        test('returns 0 when empty', function (): void {
            expect(BrowserMockStore::count())->toBe(0);
        });

        test('returns correct count', function (): void {
            BrowserMockStore::set([
                'https://api.example.com/users'    => ['status' => 200],
                'https://api.example.com/products' => ['status' => 200],
                'https://api.example.com/orders'   => ['status' => 200],
            ]);

            expect(BrowserMockStore::count())->toBe(3);
        });

        test('returns 0 after clear', function (): void {
            BrowserMockStore::set(['https://api.example.com/*' => ['status' => 200]]);
            BrowserMockStore::clear();

            expect(BrowserMockStore::count())->toBe(0);
        });
    });

    describe('multiple patterns', function (): void {
        test('stores multiple URL patterns', function (): void {
            $mocks = [
                'https://api.example.com/users/*'    => ['status' => 200, 'body' => ['users' => []]],
                'https://api.example.com/products/*' => ['status' => 200, 'body' => ['products' => []]],
                'https://external.api.com/*'         => ['status' => 500, 'body' => ['error' => 'Service unavailable']],
            ];

            BrowserMockStore::set($mocks);

            expect(BrowserMockStore::get())->toBe($mocks);
            expect(BrowserMockStore::count())->toBe(3);
        });
    });
});
