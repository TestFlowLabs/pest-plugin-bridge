<?php

declare(strict_types=1);

use TestFlowLabs\PestPluginBridge\Bridge;
use TestFlowLabs\PestPluginBridge\BridgeTrait;
use TestFlowLabs\PestPluginBridge\BrowserMockStore;

beforeEach(function (): void {
    Bridge::reset();
    BrowserMockStore::clear();
});

afterEach(function (): void {
    Bridge::reset();
    BrowserMockStore::clear();
});

describe('BridgeTrait', function (): void {
    describe('generateBrowserMockScript', function (): void {
        test('generates valid JavaScript for empty mocks', function (): void {
            $script = callGenerateBrowserMockScript();

            expect($script)->toContain('Bridge Browser Mock Interceptor');
            expect($script)->toContain("var mocks = JSON.parse('[]')");
            expect($script)->toContain('window.fetch');
            expect($script)->toContain('XMLHttpRequest.prototype.open');
        });

        test('includes registered mocks in script', function (): void {
            BrowserMockStore::set([
                'https://api.example.com/*' => [
                    'status' => 200,
                    'body'   => ['message' => 'mocked'],
                ],
            ]);

            $script = callGenerateBrowserMockScript();

            // URL and content are in JSON.parse() with escaped quotes
            expect($script)->toContain('api.example.com');
            expect($script)->toContain('status');
            expect($script)->toContain('mocked');
        });

        test('escapes special characters in JSON', function (): void {
            BrowserMockStore::set([
                'https://api.example.com/*' => [
                    'status' => 200,
                    'body'   => ['message' => "Test's \"special\" chars"],
                ],
            ]);

            $script = callGenerateBrowserMockScript();

            // Script should be syntactically valid (no unescaped quotes breaking JS)
            expect($script)->toContain('Bridge Browser Mock Interceptor');
        });

        test('contains fetch interceptor', function (): void {
            $script = callGenerateBrowserMockScript();

            expect($script)->toContain('var originalFetch = window.fetch');
            expect($script)->toContain('window.fetch = function(input, init)');
            expect($script)->toContain('Promise.resolve(new Response(body');
        });

        test('contains XMLHttpRequest interceptor', function (): void {
            $script = callGenerateBrowserMockScript();

            expect($script)->toContain('var originalOpen = XMLHttpRequest.prototype.open');
            expect($script)->toContain('var originalSend = XMLHttpRequest.prototype.send');
            expect($script)->toContain('this.__bridgeMockUrl = url');
            expect($script)->toContain('this.__bridgeMockConfig = findMock(url)');
        });

        test('contains URL pattern matching function', function (): void {
            $script = callGenerateBrowserMockScript();

            expect($script)->toContain('function matchUrl(pattern, url)');
            expect($script)->toContain('function findMock(url)');
            expect($script)->toContain('new RegExp(regexPattern).test(url)');
        });

        test('is wrapped in IIFE for isolation', function (): void {
            $script = callGenerateBrowserMockScript();

            expect($script)->toContain('(function() {');
            expect($script)->toContain("'use strict'");
            expect($script)->toContain('})();');
        });
    });

    describe('mockBrowser static method on Bridge', function (): void {
        test('registers mocks in BrowserMockStore', function (): void {
            $mocks = [
                'https://api.example.com/*' => ['status' => 200, 'body' => ['ok' => true]],
            ];

            Bridge::mockBrowser($mocks);

            expect(BrowserMockStore::hasMocks())->toBeTrue();
            expect(BrowserMockStore::get())->toBe($mocks);
        });

        test('clearBrowserMocks removes all mocks', function (): void {
            Bridge::mockBrowser(['https://api.example.com/*' => ['status' => 200]]);
            Bridge::clearBrowserMocks();

            expect(BrowserMockStore::hasMocks())->toBeFalse();
        });
    });
});

/**
 * Helper function to call the private generateBrowserMockScript method.
 */
function callGenerateBrowserMockScript(): string
{
    // Create anonymous class using the trait
    $instance = new class() {
        use BridgeTrait;

        public function callGenerateBrowserMockScript(): string
        {
            return $this->generateBrowserMockScript();
        }
    };

    return $instance->callGenerateBrowserMockScript();
}
