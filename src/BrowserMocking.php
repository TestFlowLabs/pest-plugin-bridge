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
            $context->addInitScript($this->generateMockScript());
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

        $script = <<<'MOCKSCRIPT'
(function() {
    const mocks = __MOCKS_PLACEHOLDER__;

    function matchUrl(pattern, url) {
        const escaped = pattern
            .replace(/[.+?^${}()|[\]]/g, '\\$&')
            .replace(/\*/g, '.*');
        const regex = new RegExp('^' + escaped + '$');
        return regex.test(url);
    }

    function findMock(url) {
        for (const [pattern, config] of Object.entries(mocks)) {
            if (matchUrl(pattern, url)) {
                return config;
            }
        }
        return null;
    }

    const originalFetch = window.fetch;
    window.fetch = async function(input, init) {
        const url = typeof input === 'string' ? input : input.url;
        const mock = findMock(url);

        if (mock) {
            console.log('[Bridge Mock] Intercepted fetch:', url);
            return new Response(
                JSON.stringify(mock.body || {}),
                {
                    status: mock.status || 200,
                    headers: new Headers({
                        'Content-Type': 'application/json',
                        ...(mock.headers || {})
                    })
                }
            );
        }

        return originalFetch.apply(this, arguments);
    };

    const originalXHROpen = XMLHttpRequest.prototype.open;
    const originalXHRSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function(method, url) {
        this._mockUrl = url;
        this._mockConfig = findMock(url);
        return originalXHROpen.apply(this, arguments);
    };

    XMLHttpRequest.prototype.send = function(body) {
        if (this._mockConfig) {
            console.log('[Bridge Mock] Intercepted XHR:', this._mockUrl);
            const mock = this._mockConfig;
            const self = this;

            Object.defineProperty(this, 'status', { value: mock.status || 200, writable: false });
            Object.defineProperty(this, 'statusText', { value: 'OK', writable: false });
            Object.defineProperty(this, 'responseText', { value: JSON.stringify(mock.body || {}), writable: false });
            Object.defineProperty(this, 'response', { value: JSON.stringify(mock.body || {}), writable: false });
            Object.defineProperty(this, 'readyState', { value: 4, writable: false });

            setTimeout(function() {
                if (self.onreadystatechange) self.onreadystatechange();
                if (self.onload) self.onload();
            }, 0);

            return;
        }

        return originalXHRSend.apply(this, arguments);
    };

    console.log('[Bridge Mock] Interceptors installed for', Object.keys(mocks).length, 'patterns');
})();
MOCKSCRIPT;

        return str_replace('__MOCKS_PLACEHOLDER__', $mocksJson, $script);
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
