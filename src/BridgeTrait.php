<?php

declare(strict_types=1);

namespace TestFlowLabs\PestPluginBridge;

/**
 * Trait providing browser testing methods for external frontend applications.
 *
 * This trait extends Pest's browser testing capabilities to support
 * visiting external/detached frontend applications running on separate URLs.
 */
trait BridgeTrait
{
    /**
     * Visit an external frontend path.
     *
     * Prepends the configured external base URL to the given path
     * and delegates to Pest's browser testing visit() method.
     *
     * @param  string  $path  The path to visit (e.g., '/dashboard', '/login')
     * @return mixed The browser page object from pest-plugin-browser
     */
    public function visitExternal(string $path = '/'): mixed
    {
        $fullUrl = Configuration::buildUrl($path);

        return $this->visit($fullUrl);
    }
}
