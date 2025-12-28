<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

use ReflectionClass;
use Pest\Browser\Api\Webpage;
use Pest\Browser\Enums\Device;
use Pest\Browser\ServerManager;
use Pest\Browser\Playwright\Page;
use Pest\Browser\Playwright\Client;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Playwright\InitScript;
use Pest\Browser\Playwright\Playwright;

/**
 * Provides browser-level HTTP mocking capabilities.
 *
 * This trait enables mocking of HTTP requests made directly from the browser
 * (frontend JavaScript → external API) by injecting fetch/XHR interceptors
 * via Playwright's addInitScript mechanism.
 *
 * @experimental This feature is experimental and may change.
 */
trait BrowserMocking
{
    /**
     * Stored browser mocks for the current test.
     *
     * @var array<string, array{status?: int, body?: mixed, headers?: array<string, string>}>
     */
    private static array $browserMocks = [];

    /**
     * Register browser-level HTTP mocks.
     *
     * These mocks intercept fetch() and XMLHttpRequest calls made by JavaScript
     * in the browser, before they reach the network.
     *
     * @param  array<string, array{status?: int, body?: mixed, headers?: array<string, string>}>  $mocks
     */
    public static function mockBrowser(array $mocks): void
    {
        self::$browserMocks = $mocks;
    }

    /**
     * Clear all browser mocks.
     */
    public static function clearBrowserMocks(): void
    {
        self::$browserMocks = [];
    }

    /**
     * Check if browser mocks are registered.
     */
    public static function hasBrowserMocks(): bool
    {
        return self::$browserMocks !== [];
    }

    /**
     * Navigate to a URL with browser mocking enabled.
     *
     * This method creates a new browser context with the mock script injected,
     * ensuring mocks are active before any page JavaScript runs.
     *
     * @param  array<string, mixed>  $options
     */
    public function bridgeWithMocks(string $path, ?string $frontend = null, array $options = []): AwaitableWebpage
    {
        // DEBUG: Check if mocks are registered
        $hasMocks = self::hasBrowserMocks();
        $mockCount = count(self::$browserMocks);
        file_put_contents('/tmp/bridge_mock_debug.log', sprintf(
            "[%s] bridgeWithMocks called - path: %s, hasMocks: %s, mockCount: %d, mocks: %s\n",
            date('Y-m-d H:i:s'),
            $path,
            $hasMocks ? 'true' : 'false',
            $mockCount,
            json_encode(self::$browserMocks)
        ), FILE_APPEND);

        // Ensure frontend servers are started (lazy initialization)
        FrontendManager::instance()->startAll();

        // Connect to Playwright
        Client::instance()->connectTo(
            ServerManager::instance()->playwright()->url(),
        );

        // Start Laravel server
        ServerManager::instance()->http()->bootstrap();

        // Create browser
        $browser = Playwright::browser(Playwright::defaultBrowserType())->launch();

        // Create context with options
        $context = $browser->newContext([
            'locale'      => 'en-US',
            'timezoneId'  => 'UTC',
            'colorScheme' => Playwright::defaultColorScheme()->value,
            ...Device::DESKTOP->context(),
            ...$options,
        ]);

        // Add Pest's default init script (for console log capture, etc.)
        $context->addInitScript(InitScript::get());

        // Add our mock interceptor script
        if (self::hasBrowserMocks()) {
            $mockScript = $this->generateMockScript();
            file_put_contents('/tmp/bridge_mock_debug.log', sprintf(
                "[%s] Adding init script (length: %d bytes)\nScript preview: %.500s...\n",
                date('Y-m-d H:i:s'),
                strlen($mockScript),
                $mockScript
            ), FILE_APPEND);
            $context->addInitScript($mockScript);
        } else {
            file_put_contents('/tmp/bridge_mock_debug.log', sprintf(
                "[%s] WARNING: No browser mocks registered, skipping init script\n",
                date('Y-m-d H:i:s')
            ), FILE_APPEND);
        }

        // Build URL and navigate
        $url = Bridge::buildUrl($path, $frontend);

        $page = $context->newPage();
        $page->goto($url, $options);

        return new AwaitableWebpage($page, $url);
    }

    /**
     * Apply browser mocks to an existing page by adding the script and reloading.
     *
     * Use this when you've already navigated and need to add mocks.
     * Note: This causes a page reload.
     */
    public function applyBrowserMocks(AwaitableWebpage|Webpage $webpage): AwaitableWebpage|Webpage
    {
        if (!self::hasBrowserMocks()) {
            return $webpage;
        }

        $page = $webpage instanceof AwaitableWebpage
            ? $webpage->page()
            : $this->getPageFromWebpage($webpage);

        // Add mock script to context
        $page->context()->addInitScript($this->generateMockScript());

        // Reload to apply
        $page->reload();

        return $webpage;
    }

    /**
     * Generate the JavaScript mock interceptor script.
     *
     * This script patches window.fetch and XMLHttpRequest to intercept
     * matching requests and return configured mock responses.
     */
    private function generateMockScript(): string
    {
        $mocksJson = json_encode(self::$browserMocks, JSON_THROW_ON_ERROR);

        // Escape special characters in JSON for JavaScript string embedding
        $escapedMocksJson = addslashes($mocksJson);

        // Simple debug script first to verify init script is running at all
        $script = <<<MOCKSCRIPT
// Bridge Mock Interceptor - Minimal Debug Version
console.log('[Bridge Mock] Init script executed!');

(function() {
    'use strict';

    // Parse mocks from escaped JSON string
    var mocks = JSON.parse('$escapedMocksJson');
    console.log('[Bridge Mock] Loaded ' + Object.keys(mocks).length + ' mock patterns');

    // Simple URL matching with wildcard support
    function matchUrl(pattern, url) {
        // Escape regex special chars except *
        var escaped = pattern.replace(/[.+?^\${}()|[\\]\\\\]/g, '\\\\$&');
        // Convert * to .*
        var regexPattern = '^' + escaped.replace(/\\*/g, '.*') + '\$';
        return new RegExp(regexPattern).test(url);
    }

    function findMock(url) {
        var patterns = Object.keys(mocks);
        for (var i = 0; i < patterns.length; i++) {
            if (matchUrl(patterns[i], url)) {
                console.log('[Bridge Mock] Match found for: ' + url);
                return mocks[patterns[i]];
            }
        }
        return null;
    }

    // Patch fetch
    var originalFetch = window.fetch;
    window.fetch = function(input, init) {
        var url = typeof input === 'string' ? input : input.url;
        console.log('[Bridge Mock] fetch() called with URL: ' + url);

        var mock = findMock(url);
        if (mock) {
            console.log('[Bridge Mock] Returning mock response for: ' + url);
            var body = JSON.stringify(mock.body || {});
            var status = mock.status || 200;
            return Promise.resolve(new Response(body, {
                status: status,
                headers: { 'Content-Type': 'application/json' }
            }));
        }

        return originalFetch.apply(this, arguments);
    };

    // Patch XMLHttpRequest
    var originalOpen = XMLHttpRequest.prototype.open;
    var originalSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function(method, url) {
        this.__mockUrl = url;
        this.__mockConfig = findMock(url);
        return originalOpen.apply(this, arguments);
    };

    XMLHttpRequest.prototype.send = function(body) {
        if (this.__mockConfig) {
            console.log('[Bridge Mock] XHR intercepted: ' + this.__mockUrl);
            var mock = this.__mockConfig;
            var xhr = this;

            Object.defineProperty(xhr, 'status', { value: mock.status || 200 });
            Object.defineProperty(xhr, 'statusText', { value: 'OK' });
            Object.defineProperty(xhr, 'responseText', { value: JSON.stringify(mock.body || {}) });
            Object.defineProperty(xhr, 'response', { value: JSON.stringify(mock.body || {}) });
            Object.defineProperty(xhr, 'readyState', { value: 4 });

            setTimeout(function() {
                if (xhr.onreadystatechange) xhr.onreadystatechange();
                if (xhr.onload) xhr.onload();
            }, 0);
            return;
        }
        return originalSend.apply(this, arguments);
    };

    console.log('[Bridge Mock] Interceptors installed successfully');
})();
MOCKSCRIPT;

        return $script;
    }

    /**
     * Get Page from Webpage using reflection (Webpage has private $page).
     */
    private function getPageFromWebpage(Webpage $webpage): Page
    {
        $reflection = new ReflectionClass($webpage);
        $property   = $reflection->getProperty('page');

        return $property->getValue($webpage);
    }
}
