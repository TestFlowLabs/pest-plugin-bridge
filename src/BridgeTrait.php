<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

use Pest\Browser\Enums\Device;
use Pest\Browser\ServerManager;
use Pest\Browser\Playwright\Client;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Playwright\InitScript;
use Pest\Browser\Playwright\Playwright;

/**
 * Trait providing browser testing methods for external frontend applications.
 *
 * This trait extends Pest's browser testing capabilities to support
 * visiting external/detached frontend applications running on separate URLs.
 *
 * Automatically applies browser mocks when registered via Bridge::mockBrowser().
 */
trait BridgeTrait
{
    /**
     * Bridge to an external frontend path.
     *
     * Prepends the configured external base URL to the given path
     * and navigates to the full URL. If browser mocks are registered
     * via Bridge::mockBrowser(), they are automatically applied.
     *
     * Automatically starts any configured frontend servers on first call.
     *
     * Uses a longer timeout (30s) by default to handle cold-start compilation
     * in development servers like Vite, which compile modules on first request.
     *
     * @param  string  $path  The path to visit (e.g., '/dashboard', '/login')
     * @param  string|null  $frontend  Named frontend or null for default
     * @param  array<string, mixed>  $options  Additional options for page.goto()
     *
     * @return mixed The browser page object from pest-plugin-browser
     */
    public function bridge(string $path = '/', ?string $frontend = null, array $options = []): mixed
    {
        // Ensure frontend servers are started (lazy initialization)
        FrontendManager::instance()->startAll();

        $fullUrl = Bridge::buildUrl($path, $frontend);

        // Use 30s timeout for cold-start scenarios (Vite compiles on first request)
        $defaultOptions = ['timeout' => 30000];
        $mergedOptions  = array_merge($defaultOptions, $options);

        // If browser mocks are registered, use custom context with init script
        if (BrowserMockStore::hasMocks()) {
            return $this->bridgeWithBrowserMocks($fullUrl, $mergedOptions);
        }

        return $this->visit($fullUrl, $mergedOptions);
    }

    /**
     * Navigate to a URL with browser mocking enabled.
     *
     * Creates a new browser context with the mock interceptor script injected,
     * ensuring mocks are active before any page JavaScript runs.
     *
     * @param  array<string, mixed>  $options
     */
    private function bridgeWithBrowserMocks(string $url, array $options = []): AwaitableWebpage
    {
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
        $context->addInitScript($this->generateBrowserMockScript());

        // Navigate
        $page = $context->newPage();
        $page->goto($url, $options);

        return new AwaitableWebpage($page, $url);
    }

    /**
     * Generate the JavaScript mock interceptor script.
     *
     * This script patches window.fetch and XMLHttpRequest to intercept
     * matching requests and return configured mock responses.
     */
    private function generateBrowserMockScript(): string
    {
        $mocksJson = json_encode(BrowserMockStore::get(), JSON_THROW_ON_ERROR);

        // Escape special characters in JSON for JavaScript string embedding
        $escapedMocksJson = addslashes($mocksJson);

        return <<<MOCKSCRIPT
// Bridge Browser Mock Interceptor
(function() {
    'use strict';

    var mocks = JSON.parse('{$escapedMocksJson}');

    function matchUrl(pattern, url) {
        var escaped = pattern.replace(/[.+?^\${}()|[\\]\\\\]/g, '\\\\$&');
        var regexPattern = '^' + escaped.replace(/\\*/g, '.*') + '\$';
        return new RegExp(regexPattern).test(url);
    }

    function findMock(url) {
        var patterns = Object.keys(mocks);
        for (var i = 0; i < patterns.length; i++) {
            if (matchUrl(patterns[i], url)) {
                return mocks[patterns[i]];
            }
        }
        return null;
    }

    // Patch fetch
    var originalFetch = window.fetch;
    window.fetch = function(input, init) {
        var url = typeof input === 'string' ? input : input.url;
        var mock = findMock(url);

        if (mock) {
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
        this.__bridgeMockUrl = url;
        this.__bridgeMockConfig = findMock(url);
        return originalOpen.apply(this, arguments);
    };

    XMLHttpRequest.prototype.send = function(body) {
        if (this.__bridgeMockConfig) {
            var mock = this.__bridgeMockConfig;
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
})();
MOCKSCRIPT;
    }
}
