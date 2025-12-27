<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Configuration;

beforeEach(function (): void {
    Configuration::reset();
    putenv('PEST_BRIDGE_EXTERNAL_URL');
});

afterEach(function (): void {
    Configuration::reset();
    putenv('PEST_BRIDGE_EXTERNAL_URL');
});

describe('setExternalUrl', function (): void {
    test('sets a valid URL', function (): void {
        Configuration::setExternalUrl('http://localhost:3000');

        expect(Configuration::getExternalUrl())->toBe('http://localhost:3000');
    });

    test('sets a valid URL with port', function (): void {
        Configuration::setExternalUrl('http://frontend.test:8080');

        expect(Configuration::getExternalUrl())->toBe('http://frontend.test:8080');
    });

    test('sets a valid HTTPS URL', function (): void {
        Configuration::setExternalUrl('https://app.example.com');

        expect(Configuration::getExternalUrl())->toBe('https://app.example.com');
    });

    test('throws exception for invalid URL', function (): void {
        Configuration::setExternalUrl('not-a-valid-url');
    })->throws(InvalidArgumentException::class, 'Invalid URL provided: not-a-valid-url');

    test('throws exception for empty URL', function (): void {
        Configuration::setExternalUrl('');
    })->throws(InvalidArgumentException::class);
});

describe('getExternalUrl', function (): void {
    test('returns programmatically set URL', function (): void {
        Configuration::setExternalUrl('http://localhost:5173');

        expect(Configuration::getExternalUrl())->toBe('http://localhost:5173');
    });

    test('reads from environment variable when not set programmatically', function (): void {
        putenv('PEST_BRIDGE_EXTERNAL_URL=http://env.frontend.test:8080');

        expect(Configuration::getExternalUrl())->toBe('http://env.frontend.test:8080');
    });

    test('programmatic URL takes precedence over environment variable', function (): void {
        putenv('PEST_BRIDGE_EXTERNAL_URL=http://env.test');
        Configuration::setExternalUrl('http://programmatic.test');

        expect(Configuration::getExternalUrl())->toBe('http://programmatic.test');
    });

    test('throws exception when no URL is configured', function (): void {
        Configuration::getExternalUrl();
    })->throws(InvalidArgumentException::class, 'External URL not configured');
});

describe('hasExternalUrl', function (): void {
    test('returns true when URL is set programmatically', function (): void {
        Configuration::setExternalUrl('http://localhost:3000');

        expect(Configuration::hasExternalUrl())->toBeTrue();
    });

    test('returns true when environment variable is set', function (): void {
        putenv('PEST_BRIDGE_EXTERNAL_URL=http://env.test');

        expect(Configuration::hasExternalUrl())->toBeTrue();
    });

    test('returns false when no URL is configured', function (): void {
        expect(Configuration::hasExternalUrl())->toBeFalse();
    });
});

describe('buildUrl', function (): void {
    beforeEach(function (): void {
        Configuration::setExternalUrl('http://localhost:5173');
    });

    test('builds URL with leading slash path', function (): void {
        expect(Configuration::buildUrl('/dashboard'))->toBe('http://localhost:5173/dashboard');
    });

    test('builds URL without leading slash path', function (): void {
        expect(Configuration::buildUrl('about'))->toBe('http://localhost:5173/about');
    });

    test('handles root path', function (): void {
        expect(Configuration::buildUrl('/'))->toBe('http://localhost:5173/');
    });

    test('handles empty path', function (): void {
        expect(Configuration::buildUrl(''))->toBe('http://localhost:5173/');
    });

    test('handles nested path', function (): void {
        expect(Configuration::buildUrl('/users/profile/settings'))->toBe('http://localhost:5173/users/profile/settings');
    });

    test('handles base URL with trailing slash', function (): void {
        Configuration::setExternalUrl('http://localhost:5173/');

        expect(Configuration::buildUrl('/dashboard'))->toBe('http://localhost:5173/dashboard');
    });
});

describe('reset', function (): void {
    test('clears the programmatically set URL', function (): void {
        Configuration::setExternalUrl('http://localhost:3000');
        Configuration::reset();

        expect(Configuration::hasExternalUrl())->toBeFalse();
    });
});
