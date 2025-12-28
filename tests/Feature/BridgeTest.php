<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Bridge;

/*
 * Feature tests for Bridge port and edge case scenarios.
 *
 * These tests cover scenarios not tested in Unit tests:
 * - Different frontend framework ports
 * - Unicode and special character handling
 */
beforeEach(function (): void {
    Bridge::reset();
});

afterEach(function (): void {
    Bridge::reset();
});

describe('different frontend ports', function (): void {
    test('works with Vite default port 5173', function (): void {
        Bridge::add('http://localhost:5173');

        expect(Bridge::buildUrl('/app'))->toBe('http://localhost:5173/app');
    });

    test('works with Vue CLI default port 8080', function (): void {
        Bridge::add('http://localhost:8080');

        expect(Bridge::buildUrl('/app'))->toBe('http://localhost:8080/app');
    });

    test('works with Create React App default port 3000', function (): void {
        Bridge::add('http://localhost:3000');

        expect(Bridge::buildUrl('/app'))->toBe('http://localhost:3000/app');
    });

    test('works with Next.js default port 3000', function (): void {
        Bridge::add('http://localhost:3000');

        expect(Bridge::buildUrl('/app'))->toBe('http://localhost:3000/app');
    });

    test('works with custom domain', function (): void {
        Bridge::add('https://staging.myapp.com');

        expect(Bridge::buildUrl('/app'))->toBe('https://staging.myapp.com/app');
    });
});

describe('edge cases', function (): void {
    test('handles multiple consecutive slashes in path', function (): void {
        Bridge::add('http://localhost:5173');

        $url = Bridge::buildUrl('//double//slash//');

        expect($url)->toContain('localhost:5173/');
    });

    test('handles unicode characters in path', function (): void {
        Bridge::add('http://localhost:5173');

        $url = Bridge::buildUrl('/user/日本語');

        expect($url)->toBe('http://localhost:5173/user/日本語');
    });

    test('handles special characters in path', function (): void {
        Bridge::add('http://localhost:5173');

        $url = Bridge::buildUrl('/search?q=hello%20world');

        expect($url)->toBe('http://localhost:5173/search?q=hello%20world');
    });
});
