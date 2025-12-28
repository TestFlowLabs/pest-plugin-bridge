<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

/**
 * Trait providing browser testing methods for external frontend applications.
 *
 * This trait extends Pest's browser testing capabilities to support
 * visiting external/detached frontend applications running on separate URLs.
 *
 * Includes BrowserMocking for intercepting browser-level HTTP requests.
 */
trait BridgeTrait
{
    use BrowserMocking;

    /**
     * Bridge to an external frontend path.
     *
     * Prepends the configured external base URL to the given path
     * and delegates to Pest's browser testing visit() method.
     *
     * This is the signature method of pest-plugin-bridge, enabling
     * browser testing of external/detached frontend applications.
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

        return $this->visit($fullUrl, $mergedOptions);
    }
}
