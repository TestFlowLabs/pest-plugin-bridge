<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Bridge;

/*
 * Feature tests for the Bridge functionality.
 *
 * These tests verify URL construction behavior that would be used
 * by the BridgeTrait when visiting external frontend applications.
 * No actual browser is required for these tests.
 */
beforeEach(function (): void {
    Bridge::reset();
    Bridge::add('http://localhost:5173');
});

afterEach(function (): void {
    Bridge::reset();
});

describe('external URL construction', function (): void {
    test('constructs correct URL for dashboard path', function (): void {
        $url = Bridge::buildUrl('/dashboard');

        expect($url)->toBe('http://localhost:5173/dashboard');
    });

    test('constructs correct URL for login path', function (): void {
        $url = Bridge::buildUrl('/login');

        expect($url)->toBe('http://localhost:5173/login');
    });

    test('handles paths with query parameters', function (): void {
        $url = Bridge::buildUrl('/search?q=test&page=1');

        expect($url)->toBe('http://localhost:5173/search?q=test&page=1');
    });

    test('handles paths with hash fragments', function (): void {
        $url = Bridge::buildUrl('/docs#installation');

        expect($url)->toBe('http://localhost:5173/docs#installation');
    });

    test('handles deeply nested paths', function (): void {
        $url = Bridge::buildUrl('/api/v1/users/123/profile');

        expect($url)->toBe('http://localhost:5173/api/v1/users/123/profile');
    });
});

describe('named frontends', function (): void {
    beforeEach(function (): void {
        Bridge::add('http://localhost:5174', 'admin');
        Bridge::add('http://localhost:5175', 'mobile');
    });

    test('builds URL with default frontend', function (): void {
        $url = Bridge::buildUrl('/dashboard');

        expect($url)->toBe('http://localhost:5173/dashboard');
    });

    test('builds URL with admin frontend', function (): void {
        $url = Bridge::buildUrl('/users', 'admin');

        expect($url)->toBe('http://localhost:5174/users');
    });

    test('builds URL with mobile frontend', function (): void {
        $url = Bridge::buildUrl('/app', 'mobile');

        expect($url)->toBe('http://localhost:5175/app');
    });

    test('each frontend maintains its own paths', function (): void {
        expect(Bridge::buildUrl('/dashboard'))->toBe('http://localhost:5173/dashboard');
        expect(Bridge::buildUrl('/dashboard', 'admin'))->toBe('http://localhost:5174/dashboard');
        expect(Bridge::buildUrl('/dashboard', 'mobile'))->toBe('http://localhost:5175/dashboard');
    });
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
        $url = Bridge::buildUrl('//double//slash//');

        expect($url)->toContain('localhost:5173/');
    });

    test('handles unicode characters in path', function (): void {
        $url = Bridge::buildUrl('/user/日本語');

        expect($url)->toBe('http://localhost:5173/user/日本語');
    });

    test('handles special characters in path', function (): void {
        $url = Bridge::buildUrl('/search?q=hello%20world');

        expect($url)->toBe('http://localhost:5173/search?q=hello%20world');
    });
});
