<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Configuration;

/**
 * Feature tests for the Bridge functionality.
 *
 * These tests verify URL construction behavior that would be used
 * by the BridgeTrait when visiting external frontend applications.
 * No actual browser is required for these tests.
 */

beforeEach(function (): void {
    Configuration::reset();
    Configuration::setExternalUrl('http://localhost:5173');
});

afterEach(function (): void {
    Configuration::reset();
});

describe('external URL construction', function (): void {
    test('constructs correct URL for dashboard path', function (): void {
        $url = Configuration::buildUrl('/dashboard');

        expect($url)->toBe('http://localhost:5173/dashboard');
    });

    test('constructs correct URL for login path', function (): void {
        $url = Configuration::buildUrl('/login');

        expect($url)->toBe('http://localhost:5173/login');
    });

    test('handles paths with query parameters', function (): void {
        $url = Configuration::buildUrl('/search?q=test&page=1');

        expect($url)->toBe('http://localhost:5173/search?q=test&page=1');
    });

    test('handles paths with hash fragments', function (): void {
        $url = Configuration::buildUrl('/docs#installation');

        expect($url)->toBe('http://localhost:5173/docs#installation');
    });

    test('handles deeply nested paths', function (): void {
        $url = Configuration::buildUrl('/api/v1/users/123/profile');

        expect($url)->toBe('http://localhost:5173/api/v1/users/123/profile');
    });
});

describe('different frontend ports', function (): void {
    test('works with Vite default port 5173', function (): void {
        Configuration::setExternalUrl('http://localhost:5173');

        expect(Configuration::buildUrl('/app'))->toBe('http://localhost:5173/app');
    });

    test('works with Vue CLI default port 8080', function (): void {
        Configuration::setExternalUrl('http://localhost:8080');

        expect(Configuration::buildUrl('/app'))->toBe('http://localhost:8080/app');
    });

    test('works with Create React App default port 3000', function (): void {
        Configuration::setExternalUrl('http://localhost:3000');

        expect(Configuration::buildUrl('/app'))->toBe('http://localhost:3000/app');
    });

    test('works with Next.js default port 3000', function (): void {
        Configuration::setExternalUrl('http://localhost:3000');

        expect(Configuration::buildUrl('/app'))->toBe('http://localhost:3000/app');
    });

    test('works with custom domain', function (): void {
        Configuration::setExternalUrl('https://staging.myapp.com');

        expect(Configuration::buildUrl('/app'))->toBe('https://staging.myapp.com/app');
    });
});

describe('edge cases', function (): void {
    test('handles multiple consecutive slashes in path', function (): void {
        $url = Configuration::buildUrl('//double//slash//');

        expect($url)->toContain('localhost:5173/');
    });

    test('handles unicode characters in path', function (): void {
        $url = Configuration::buildUrl('/user/日本語');

        expect($url)->toBe('http://localhost:5173/user/日本語');
    });

    test('handles special characters in path', function (): void {
        $url = Configuration::buildUrl('/search?q=hello%20world');

        expect($url)->toBe('http://localhost:5173/search?q=hello%20world');
    });
});
